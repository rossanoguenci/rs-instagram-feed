<?php
namespace ReactSmith\InstagramFeed\Core;

use ReactSmith\InstagramFeed\Controllers;
use ReactSmith\InstagramFeed\Controllers\NotificationController;
use ReactSmith\InstagramFeed\Controllers\ActionsController;

/**
 * Main plugin manager responsible for initialization, requirements checking, and onboarding.
 */
final class PluginManager {

    /** @var array List of plugin requirements. */
    private array $requirements = [];

    /** @var string|null The plugin file path. */
    private ?string $plugin_file;

    /** @var string|null The plugin folder path. */
    private ?string $plugin_folder;

    /**
     * Constructor.
     *
     * @param array $params Initialization parameters including 'plugin-file' and 'plugin-folder'.
     */
    public function __construct(array $params) {
        $this->plugin_file = $params['plugin-file'] ?? null;
        $this->plugin_folder = $params['plugin-folder'] ?? null;
    }

    /**
     * Initializes the plugin by checking requirements and setting up hooks.
     *
     * @return void
     */
    public function init(): void {
        $check = $this->check_all_requirements();

        if (is_string($check)) {
            $this->handle_failure($check);
            return;
        }

        $this->updateChecker();

        \add_action('plugins_loaded', [SCFManager::class, 'init']);

        \add_action('init', [NotificationController::class, 'init']);
        \add_action('init', [AdminMenuBuilder::class, 'init']);
        \add_action('init', [CronManager::class, 'init']);
        \add_action('after_setup_theme', [Component::class, 'init'], 5);

        \add_action('admin_init', [Controllers\ActionsController::class, 'handle_request']);
        \add_action('admin_init', [$this,'onboard'], 1);

    }

    /**
     * Handles onboarding redirection if the encryption key is not set.
     *
     * @return void
     */
    public function onboard(): void {
        if (defined('RS_SECRET_ENCRYPTION_KEY')) { return; }
        if (!is_admin()) { return; }

        // Get the current screen if available
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;

        // Check if we are already on the onboard page to avoid infinite loops
        if (
            isset($_GET['page']) && $_GET['page'] === 'onboard' &&
            isset($_GET['post_type']) && $_GET['post_type'] === Config::POST_TYPE
        ) {
            return;
        }

        // Only trigger redirection if we are on the instagram-feed post type listing or related pages
        // This prevents redirection on other pages like media, users, etc.
        if (isset($_GET['post_type']) && $_GET['post_type'] === Config::POST_TYPE) {
            \wp_safe_redirect(\admin_url('edit.php?post_type=' . Config::POST_TYPE . '&page=onboard'));
            exit;
        }
    }

    /**
     * Adds a requirement to the plugin's requirement list.
     *
     * @param array{
     *     title: string,
     *     message: string,
     *     callable: callable
     * } $requirement The requirement configuration.
     * @return void
     */
    public function addRequirement(array $requirement): void {
        $this->requirements[] = $requirement;
    }

    /**
     * Checks if all registered requirements are met.
     *
     * @return true|string True if all met, or the error message of the first failure.
     */
    private function check_all_requirements(): true|string {
        foreach ($this->requirements as $requirement) {
            if (!$requirement['callable']()) {
                return $requirement['message'];
            }
        }
        return true;
    }

    /**
     * Handles a requirement check failure by displaying a notice and deactivating the plugin.
     *
     * @param string $message The failure message.
     * @return void
     */
    private function handle_failure($message): void {
        \add_action('admin_notices', function () use ($message) {
            echo '<div class="notice notice-error"><p>' . \esc_html($message) . '</p></div>';
        });

        // Deactivate the plugin
        if (!function_exists('deactivate_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        \deactivate_plugins($this->plugin_folder);

        // Prevent further execution in the current request
        if (isset($_GET['activate'])) {
            unset($_GET['activate']);
        }
    }


    /**
     * Logic to run on plugin activation.
     */
    public function activate(): void {
        // Currently empty
    }

    /**
     * Logic to run on plugin deactivation.
     */
    public function deactivate(): void {
        if (class_exists(__NAMESPACE__ . '\\CronManager')) {
            CronManager::stopSync();
        }
    }

    /**
     * Logic to run on plugin uninstallation.
     */
    public static function uninstall(): void {
        // Ensure we are in the process of uninstalling
        if (!defined('WP_UNINSTALL_PLUGIN')) {
            return;
        }

        // Clear all plugin-specific options
        if (class_exists(__NAMESPACE__ . '\\Config')) {
            Config::clear_all();
        }

    }

    public function updateChecker(): void{
        if(in_array(\wp_get_environment_type(), ['local', 'development'])){ return; }

        $updateChecker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
            'https://github.com/rossanoguenci/rs-instagram-feed/',
            __FILE__,
            'rs-instagram-feed'
        );

        $updateChecker->getVcsApi()->enableReleaseAssets();

    }
}