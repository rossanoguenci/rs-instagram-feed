<?php

namespace ReactSmith\InstagramFeed\Core;

use ReactSmith\Core\Component as ReactSmithComponent;
use ReactSmith\Core\Partial;

/**
 * Adapted Component class for ReactSmith Instagram Feed plugin.
 * Extends the theme's Core Component to allow loading blocks from the plugin directory.
 */
class Component extends ReactSmithComponent {
    /**
     * Constructor.
     *
     * Overridden to use the plugin's component path.
     *
     * @param string $name The Component's name.
     * @param array $args The arguments to pass to the Component.
     */
    public function __construct(public readonly string $name, $args = []) {
        // Define the base path relative to this plugin's assets
        $base = \ReactSmith\InstagramFeed\RS_INSTAGRAM_FEED_ROOT . '/assets';

        // We bypass the parent constructor to set the correct path for plugin components
        Partial::__construct("$base/components/$name", $args);
    }

    /**
     * Initialise class to set up hooks and filters for plugin components.
     *
     * Note: This runs alongside the theme's Component::init().
     */
    public static function init(): void {
        \add_action('after_setup_theme', [__CLASS__, 'load_component_functions'], 20);
        \add_action('after_setup_theme', [__CLASS__, 'load_component_hooks'], 20);

        \add_action('acf/init', [__CLASS__, 'load_component_blocks']);

        \add_filter('acf/settings/load_json', [__CLASS__, 'load_block_field_group_json']);

        // Enqueue scripts and styles from the plugin
        \add_action('reactsmith/partial/before', [__CLASS__, 'enqueue_scripts'], 10, 3);
        \add_action('reactsmith/partial/before', [__CLASS__, 'enqueue_styles'], 10, 3);
    }

    /**
     * Load all plugin components' functions.php files.
     *
     * @return void
     */
    public static function load_component_functions(): void {
        $files = glob(\ReactSmith\InstagramFeed\RS_INSTAGRAM_FEED_ROOT . '/assets/components/*/functions.php', GLOB_BRACE);
        self::require_files($files);
    }

    /**
     * Load all plugin components' hooks.php files.
     *
     * @return void
     */
    public static function load_component_hooks(): void {
        $files = glob(\ReactSmith\InstagramFeed\RS_INSTAGRAM_FEED_ROOT . '/assets/components/*/hooks.php', GLOB_BRACE);
        self::require_files($files);
    }

    /**
     * Load all plugin component's block.json files to register their ACF blocks.
     *
     * @return void
     */
    public static function load_component_blocks(): void {
        $block_json_files = glob(\ReactSmith\InstagramFeed\RS_INSTAGRAM_FEED_ROOT . '/assets/components/*/block.json');

        foreach ($block_json_files as $file_path) {
            \register_block_type($file_path);
        }
    }

    /**
     * Helper to require files with a filter.
     *
     * @param array $files List of file paths to require.
     * @return void
     */
    private static function require_files(array $files): void {
        $files = \apply_filters('reactsmith/instagram_feed/components/require_files', $files);

        foreach ($files as $file) {
            if (file_exists($file)) {
                require_once $file;
            }
        }
    }

    /**
     * Load ACF block field groups from plugin components' directories.
     */
    /* public static function load_block_field_group_json(array $paths): array {
        $plugin_paths = glob(\ReactSmith\InstagramFeed\RS_INSTAGRAM_FEED_ROOT . '/assets/components *//*', GLOB_ONLYDIR);
        return array_merge($paths, $plugin_paths);
    } */

    /**
     * Enqueue a component's block.js file from the plugin.
     *
     * @param string $path The path to the component.
     * @param array $args The arguments passed to the component.
     * @param ReactSmithComponent $component The component instance.
     * @return void
     */
    public static function enqueue_scripts(string $path, array $args, ReactSmithComponent $component): void {
        // Only handle components that belong to this plugin
        if ($component instanceof self) {
            self::enqueue_script_by_filename($component->name);
        }
    }

    /**
     * Logic for script enqueuing from plugin assets.
     *
     * @param string $name The component name.
     * @param string $script The script filename (default 'block').
     * @return void
     */
    public static function enqueue_script_by_filename(string $name, string $script = 'block'): void {
        $plugin_url = \plugin_dir_url(\ReactSmith\InstagramFeed\RS_INSTAGRAM_FEED_ROOT . '/rs-instagram-feed.php');
        $js_relative_path = "assets/components/$name/scripts/$script.js";
        $js_full_path = \ReactSmith\InstagramFeed\RS_INSTAGRAM_FEED_ROOT . '/' . $js_relative_path;

        if (!file_exists($js_full_path)) {
            return;
        }

        \wp_enqueue_script(
            "rs-instagram-$name-scripts",
            $plugin_url . $js_relative_path,
            \apply_filters("reactsmith/partial/$name/enqueue_script_dependencies", []),
            \apply_filters("reactsmith/partial/$name/enqueue_script_in_footer", false),
            true
        );
    }

    /**
     * Enqueue a component's block.css file from the plugin.
     *
     * @param string $path The path to the component.
     * @param array $args The arguments passed to the component.
     * @param ReactSmithComponent $component The component instance.
     * @return void
     */
    public static function enqueue_styles(string $path, array $args, ReactSmithComponent $component): void {
        // Only handle components that belong to this plugin
        if ($component instanceof self) {
            self::enqueue_style_by_filename($component->name);
        }
    }

    /**
     * Logic for style enqueuing from plugin assets.
     *
     * @param string $name The component name.
     * @param string $style The style filename (default 'block').
     * @return void
     */
    public static function enqueue_style_by_filename(string $name, string $style = 'block'): void {
        $plugin_url = \plugin_dir_url(\ReactSmith\InstagramFeed\RS_INSTAGRAM_FEED_ROOT . '/rs-instagram-feed.php');
        $css_relative_path = "assets/components/$name/styles/$style.css";
        $css_full_path = \ReactSmith\InstagramFeed\RS_INSTAGRAM_FEED_ROOT . '/' . $css_relative_path;

        if (!file_exists($css_full_path)) {
            return;
        }

        \wp_enqueue_style(
            "rs-instagram-$name-styles",
            $plugin_url . $css_relative_path,
            \apply_filters("reactsmith/partial/$name/enqueue_style_dependencies", [])
        );
    }

    /**
     * ACF block render callback.
     *
     * @param array $block The block settings and attributes.
     * @param string $content The block inner HTML (empty).
     * @param bool $is_preview True during AJAX preview.
     * @param int $post_id The post ID this block is saved to.
     * @return void
     */
    public static function acf_render_callback(array $block, string $content = '', bool $is_preview = false, int $post_id = 0): void {
        // Generate args using the logic from the theme's parent class
        $args = parent::generate_args_from_block($block, \get_fields(), $content, $is_preview, $post_id);

        // Use static::get so it instantiates the Plugin's Component class, not the Theme's
        echo static::get(str_replace('acf/', '', $block['name']), $args);
    }
}