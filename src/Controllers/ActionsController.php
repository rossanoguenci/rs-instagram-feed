<?php
namespace ReactSmith\InstagramFeed\Controllers;

use ReactSmith\InstagramFeed\Core\CronManager;
use ReactSmith\InstagramFeed\Controllers\NotificationController;

/**
 * Controller for handling various actions and routing requests.
 */
class ActionsController {

    /** @var array The registry that stores [ 'action_name' => [object, 'method'] ] */
    private static array $actions_registry = [];

    /**
     * Initializes the controller and registers actions.
     *
     * @return void
     */
    public static function init() {

        //This is more a Cron class management
        self::register_action('manual_sync', [new CronManager(), 'scheduleSingleEvent']);
        self::register_action('pause_sync', [new CronManager(), 'stopSync']);
        self::register_action('start_sync', [new CronManager(), 'startSync']);

        self::register_action('drop_feed', [namespace\FeedController::class, 'dropFeed']);
        self::register_action('revoke_access', [new AdminController(), 'handle_oauth_revoke_access']);
        self::register_action('clear_connection', [new AdminController(), 'handle_clear_connection']);
    }

    /**
     * Public method to register an action handler
     */
    public static function register_action(string $action_name, callable $callback): void {
        self::$actions_registry[$action_name] = $callback;
    }

    /**
     * Handles incoming GET and POST requests.
     *
     * @return void
     */
    public static function handle_request() {
        if (empty(self::$actions_registry)) {
            self::init();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            self::route_post_actions();
        }

        if (isset($_GET['action']) && !isset($_GET['post'])) { //if post -> editing post
            self::route_get_actions();
        }

        if (isset($_GET['code'])) {

            if (!isset($_GET['state']) || !\wp_verify_nonce($_GET['state'], 'rs_instagram_feed_meta_oauth_state')) {
                NotificationController::add('Nonce was not valid (or not set) for state parameter.', 'error');
            }else{
                (new AdminController())->handle_oauth_callback();
            }

        }

    }

    /**
     * Routes POST actions based on the request.
     *
     * @return void
     */
    private static function route_post_actions() {

        if (isset($_POST['rs_instagram_feed_save_credentials'])) {
            if (\check_admin_referer('rs_instagram_feed_settings_action', 'rs_instagram_feed_settings_nonce') === 1) {
                AdminController::handle_save_credentials();
            } else {
                NotificationController::add('Nonce was not valid for saving credentials.', 'error');
            }
        } else if(isset($_POST['rs_instagram_feed_max_posts_number_save'])){
            if (\check_admin_referer('rs_instagram_feed_max_posts_number', 'rs_instagram_feed_settings_nonce') === 1) {
                AdminController::handle_save_options();
            } else {
                NotificationController::add('Nonce was not valid for saving the max_posts_number parameter.', 'error');
            }
        }

    }

    /**
     * Routes GET actions based on the request.
     *
     * @return void
     */
    private static function route_get_actions() {
        if (!isset($_GET['rs_nonce']) || !\check_admin_referer('rs_instagram_feed_action', 'rs_nonce')) {
            NotificationController::add('Nonce was not valid.', 'error');
            return;
        }

        $action = \sanitize_text_field($_GET['action']);

        // Check if the action is registered
        if (isset(self::$actions_registry[$action])) {
            call_user_func(self::$actions_registry[$action]);
        }
    }
}