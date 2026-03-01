<?php
/**
 * Plugin Name: ReactSmith Instagram Feed
 * Plugin URI: https://github.com/rossanoguenci/rs-instagram-feed
 * Update URI: https://github.com/rossanoguenci/rs-instagram-feed
 * Description: Retrieves Instagram posts for the ReactSmith theme.
 * Version: 1.0.0
 * Author: Rossano Guenci
 * Author URI: https://rossanoguenci.co.uk
 * License: MIT
 */

namespace ReactSmith\InstagramFeed;

use ReactSmith\InstagramFeed\Core\PluginManager;

if (!defined('ABSPATH')) exit;

const RS_INSTAGRAM_FEED_ROOT = __DIR__;

/* Autoload and class inits */
require_once RS_INSTAGRAM_FEED_ROOT . '/vendor/autoload.php';

$pluginManager = new PluginManager([
    'plugin-file' => __FILE__,
    'plugin-folder' => __DIR__,
]);

\register_activation_hook(__FILE__, [$pluginManager, 'activate']);
\register_deactivation_hook(__FILE__, [$pluginManager, 'deactivate']);
\register_uninstall_hook(__FILE__, [PluginManager::class, 'uninstall']);


$requirements = [
    [
        'title' => 'ReactSmith Theme Active',
        'message' => __('Instagram Feed requires the ReactSmith theme.', 'reactsmith'),
        'callable' => fn() => \wp_get_theme()->get('TextDomain') === 'reactsmith',
    ],
];

foreach($requirements as $req){
    $pluginManager->addRequirement($req);
}

$pluginManager->init();