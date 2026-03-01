<?php
namespace ReactSmith\InstagramFeed\Core;

use ReactSmith\InstagramFeed\Services;
use ReactSmith\InstagramFeed\Views;
use ReactSmith\InstagramFeed\Core\Config;

/**
 * Builder for managing the administrative menu and subpages.
 */
class AdminMenuBuilder{

    /**
     * Initializes the admin menu builder.
     *
     * @return void
     */
    public static function init() {
        \add_action('admin_menu', [self::class, 'register_pages']);
    }

    /**
     * Defines the configuration for the onboarding page.
     *
     * @return array The page configuration.
     */
    private static function onboard_page(): array {
        return [
           'onboard' => [
               'parent_slug' => 'edit.php?post_type=' . Config::POST_TYPE,
               'page_title'  => 'Setup Key',
               'menu_title'  => 'Setup Key',
               'capability'  => 'manage_options',
               'menu_slug'   => 'onboard',
               'callback'    => fn() => (new Views\OnboardPageView())->render(),
               'condition'   => true,
           ]
       ];
    }


    /**
     * Defines the configuration for the default administrative pages.
     *
     * @return array The pages configuration.
     */
    private static function default_pages(): array {
        return [
            'settings' => [
                'parent_slug' => 'edit.php?post_type=' . Config::POST_TYPE,
                'page_title'  => 'Settings',
                'menu_title'  => 'Settings',
                'capability'  => 'edit_posts',
                'menu_slug'   => 'settings',
                'callback'    => fn() => (new Views\SettingsPageView())->render(),
                'condition'   => true,
            ],
            'account-connection' => [
                'parent_slug' => 'edit.php?post_type=' . Config::POST_TYPE,
                'page_title'  => 'Account Connection',
                'menu_title'  => 'Account Connection',
                'capability'  => 'manage_options',
                'menu_slug'   => 'account-connection',
                'callback'    => function() {
                                     $view = new Views\AccountConnectionPageView();

                                     $app_id = Config::get_app_id();

                                     if ($app_id && Config::get_app_secret()) {
                                         $service = new Services\MetaOAuthService([
                                             'app_id'            => $app_id,
                                             'app_secret'        => Config::get_app_secret(true),
                                             'app_access_token'  => Config::get(Config::OPT_APP_ACCESS_TOKEN),
                                             'business_id'       => Config::get(Config::OPT_BUSINESS_ACCOUNT_ID),
                                             'business_id_token' => Config::get(Config::OPT_BUSINESS_ACCOUNT_ACCESS_TOKEN),
                                         ]);
                                         $view->set_meta_service($service);
                                     }

                                     $view->render();
                                 },
                'condition'   => true,
            ],
        ];
    }

    /**
     * Registers the administrative pages in WordPress.
     *
     * @return void
     */
    public static function register_pages(): void {
        $pages = defined('RS_SECRET_ENCRYPTION_KEY') ? self::default_pages() : self::onboard_page();

        foreach ($pages as $slug => $config) {
            if (!$config['condition']) continue;

            \add_submenu_page(
                $config['parent_slug'],
                $config['page_title'],
                $config['menu_title'],
                $config['capability'],
                $config['menu_slug'],
                $config['callback'],
            );
        }
    }

}