<?php
namespace ReactSmith\InstagramFeed\Components\Feed;

use ReactSmith\InstagramFeed\Core\Config;

function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'classes' => [],
        'stack' => [],
        'cards_style' => 'full',
        'list_attributes' => [
                    'class' => ['rs-card-stack__list'],
        ],
    ], $args);

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'rs-card-stack',
        'wp-block',
    ], $args['classes']);

    // -------------------------------------------------------------------------
    // Adjust stack output.
    // -------------------------------------------------------------------------
    $posts = \get_posts([
        'post_type'      => Config::POST_TYPE,
        'posts_per_page' => (int) $args['max_posts_number'],
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
    ]);

    foreach ($posts as $post) {
        $image_url = \get_the_post_thumbnail_url($post->ID, 'large');

        if(!$image_url) continue;

        $permalink = \get_field('permalink', $post->ID);

        $args['stack'][] = [
            'id' => $post->post_name,
            'image_url' => $image_url,
            'title' => $post->post_title,
            'link' => $permalink,
        ];
    }


    // -------------------------------------------------------------------------
    // Compute list layout classes based on style.
    // -------------------------------------------------------------------------
    $list_classes = $args['list_attributes']['class'] ?? ['rs-card-stack__list'];

    if (in_array($args['cards_style'] ?? '', ['full', 'info'])) {
        $list_classes[] = 'rs-card-stack__list--grid';
    }

    if (in_array($args['cards_style'] ?? '', ['info'])) {
        $list_classes[] = 'rs-card-stack__list--grid-2';
    }

    $args['list_attributes']['class'] = array_values(array_unique($list_classes));

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
