<?php
namespace ReactSmith\InstagramFeed\Controllers;

use ReactSmith\InstagramFeed\Abstracts\AbstractAdminController;
use ReactSmith\InstagramFeed\Core\Config;
use ReactSmith\Core\Debug;

/**
 * Controller for managing the Instagram feed, including fetching, upserting, and cleaning up posts.
 */
class FeedController extends AbstractAdminController {

    /**
     * Constructor.
     */
    public function __construct(){
        parent::__construct();
        $this->init_meta_service();
    }

    /**
     * Upserts an Instagram post into the WordPress custom post type.
     *
     * @param array $ig_post Data returned from Meta API (id, caption, media_url, etc.)
     */
    private function upsert_post(array $ig_post): void{
        $media_id = $ig_post['id'];

        // 1. Check if a post with this IG Media ID already exists
        $existing_posts = \get_posts([
            'post_type'  => 'instagram-feed',
            'meta_query' => [
                [
                    'key'   => 'id',
                    'value' => $media_id,
                ]
            ],
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'post_status'    => 'any',
        ]);

        $post_id = !empty($existing_posts) ? $existing_posts[0] : 0;

        // 2. Prepare post data
        $post_data = [
            'post_title'   => !empty($ig_post['caption']) ? \wp_trim_words($ig_post['caption'], 10) : 'Instagram Post ' . $media_id,
            'post_status'  => 'publish',
            'post_type'    => 'instagram-feed',
            'post_date'    => date('Y-m-d H:i:s', strtotime($ig_post['timestamp'])),
        ];

        if ($post_id) {
            // Update existing post
            $post_data['ID'] = $post_id;
            \wp_update_post($post_data);
        } else {
            // Create new post
            $post_id = \wp_insert_post($post_data);
        }

        // 3. Update Custom Fields (Meta)
        if (!$post_id || \is_wp_error($post_id)) {
            $message = 'Error FeedController - upsert_post() - $post_id -> ' . $post_id->get_error_message();
            Debug::log($message);
            return;
        }

        foreach($this->meta_service->mediaFieldsIG() as $field){
            $flag = \update_field($field, $ig_post[$field] ?? '', $post_id);

            if($flag != true){
                $message = "Error FeedController - upsert_post() - update_field() -> " . print_r($flag,true);
                Debug::log($message);
            }

        }

        // 4. Insert featured image
        if (!\has_post_thumbnail($post_id)) {
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';

            $image_url =
                $ig_post['media_type'] === 'VIDEO' ?
                ($ig_post['thumbnail_url'] ?? '') :
                ($ig_post['media_url'] ?? '')
            ;

            if (!empty($image_url)) {
                $attachment_id = \media_sideload_image($image_url, $post_id, null, 'id');

                if (\is_wp_error($attachment_id)) {
                    $message = 'Error FeedController - upsert_post() - $attachment_id -> ' . $attachment_id->get_error_message();
                    Debug::log($message);
                    return;
                }

                \set_post_thumbnail($post_id, $attachment_id);
            }
        }

    }

    /**
     * Deletes a post and its featured image from the WordPress database.
     *
     * @param int $post_id The ID of the post to delete.
     * @param bool $force_delete Whether to bypass the trash and force deletion.
     * @return bool True on success, false on failure.
     */
    private static function delete_post(int $post_id, bool $force_delete = false ): bool {
        if(empty($post_id)) return false;

        $attachment_id = \get_post_thumbnail_id($post_id);

        if ($attachment_id) {
            \wp_delete_attachment($attachment_id, $force_delete);
        }

        $del_flag = \wp_delete_post($post_id, $force_delete);

        return !empty($del_flag);
    }

    /**
     * Deletes all posts from the feed.
     *
     * @return void
     */
    public static function dropFeed(): void {
        $posts = \get_posts([
            'post_type'   => Config::POST_TYPE,
            'numberposts' => -1,
            'post_status' => 'any',
            'fields'      => 'ids',
        ]);

        foreach ($posts as $post_id) {
            self::delete_post($post_id, true);
        }

        NotificationController::add('FeedController - All posts were deleted.','success');
    }

    /**
     * Updates the feed with the latest posts from Instagram.
     *
     * @return void
     */
    public function updateFeed(): void {
        if (!$this->has_connection()) {
            $message = 'Error FeedController - updateFeed() - meta_service had no connection.';

            NotificationController::add($message,'error');
            Debug::log($message);

            return;
        }

        $limit = Config::get(Config::OPT_MAX_POSTS_NUMBER, false, 6);

        $latest_posts = $this->meta_service->retrieveLatestPosts($limit);

        if (empty($latest_posts) || !is_array($latest_posts)) {
            $message = 'Error FeedController - updateFeed() - latest_posts was empty or not an array.';
            Debug::log($message);
            return;
        }

        $should_update = true;

        // Guard: check if the newest post has the same timestamp as the last sync
        // 1. Retrieve the last added post from the database to check its timestamp
        $last_stored_posts = \get_posts([
            'post_type'      => Config::POST_TYPE,
            'posts_per_page' => 1,
            'post_status'    => 'any',
            'orderby'        => 'date',
            'order'          => 'DESC',
        ]);

        // Count stored posts
        $stored_count = (int) \wp_count_posts(Config::POST_TYPE)->publish;

        if (!empty($last_stored_posts) && $stored_count >= count($latest_posts)) {
            $last_post_id = $last_stored_posts[0]->ID;
            $last_post_timestamp_raw = \get_field('timestamp', $last_post_id);
            $last_post_timestamp = $last_post_timestamp_raw ? strtotime($last_post_timestamp_raw) : 0;

            $newest_api_post_timestamp = strtotime($latest_posts[0]['timestamp'] ?? '');

            // If newest API post matches DB, we don't need to run the loop
            if ($newest_api_post_timestamp && $newest_api_post_timestamp === $last_post_timestamp) {
                $should_update = false;

                Debug::log('Info - FeedController - updateFeed() - No updating needed (latest post already exists).');
            }
        }

        // Run upsert loop only if needed (or if DB is empty)
        if ($should_update) {
            foreach ($latest_posts as $post) {
                $this->upsert_post($post);
            }
        }

        Config::set(Config::OPT_CRON_LAST_SYNC, time());
    }

    /**
     * Cleans up old posts that exceed the maximum number allowed.
     *
     * @return void
     */
    public static function cleanOldPosts(): void {
        $max_posts = (int) Config::get(Config::OPT_MAX_POSTS_NUMBER, false, 6);

        $all_posts = \get_posts([
            'post_type'      => Config::POST_TYPE,
            'post_status'    => 'any',
            'numberposts'    => -1,
            'orderby'        => 'date',
            'order'          => 'DESC', // Newest first
            'fields'         => 'ids',
        ]);

        if (count($all_posts) > $max_posts) {
            $posts_to_delete = array_slice($all_posts, $max_posts);
            foreach ($posts_to_delete as $post_id) {
                self::delete_post($post_id);
            }
        }
    }

    /**
     * Public method to check if the connection is established.
     *
     * @return bool True if connection is ok, false otherwise.
     */
    public function hasConnection(): bool{
        return $this->has_connection();
    }

}
