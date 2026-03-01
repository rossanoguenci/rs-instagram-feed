<?php
namespace ReactSmith\InstagramFeed\Core;

use const ReactSmith\InstagramFeed\RS_INSTAGRAM_FEED_ROOT;

/**
 * Manager for handling Smart Custom Fields (SCF) / ACF JSON loading paths.
 */
class SCFManager {

    /**
     * Initializes the SCF manager.
     *
     * @return void
     */
    public static function init(){
        self::load_json();
    }

    /**
     * Adds the plugin's JSON directories to the ACF/SCF load paths.
     *
     * @return void
     */
    public static function load_json(){
        \add_filter('acf/settings/load_json', function( $paths ) {
            // Add the main scf-json directory
            $paths[] = RS_INSTAGRAM_FEED_ROOT . '/scf-json';

            // Add all directories within assets/components/
            $plugin_component_paths = glob(RS_INSTAGRAM_FEED_ROOT . '/assets/components/*', GLOB_ONLYDIR);

            if (is_array($plugin_component_paths)) {
                $paths = array_merge($paths, $plugin_component_paths);
            }

            return $paths;
        });
    }

}