<?php

namespace ReactSmith\InstagramFeed\Abstracts;

use const ReactSmith\InstagramFeed\RS_INSTAGRAM_FEED_ROOT;
use ReactSmith\InstagramFeed\Core\Config;
use \ReactSmith\InstagramFeed\Services\MetaOAuthService;

/**
 * Abstract class for Page Views.
 */
abstract class AbstractPageView {
    /** @var string|null Menu slug */
    protected ?string $menu_slug = null;

    /** @var string|null Path to templates */
    protected ?string $templates_path = null;

    /** @var MetaOAuthService|null Meta OAuth service instance */
    protected ?MetaOAuthService $meta_service = null;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->templates_path = RS_INSTAGRAM_FEED_ROOT.'/templates';
    }

    /**
     * Sets the Meta OAuth service instance.
     *
     * @param MetaOAuthService $service The Meta OAuth service instance.
     * @return void
     */
    public function set_meta_service(MetaOAuthService $service): void {
        $this->meta_service = $service;
    }

    /**
     * Checks if the Meta connection is established and token is not expired.
     *
     * @return bool True if connection is ok, false otherwise.
     */
    protected function has_connection(): bool{
        $token_flag = (bool) Config::get(Config::OPT_BUSINESS_ACCOUNT_ACCESS_TOKEN);

        if(!$token_flag){
            return false;
        }

        $exp = (int) Config::get(Config::OPT_BUSINESS_ACCOUNT_ACCESS_TOKEN_EXPIRY);

        if($exp != 0 && time() > $exp){
            return false;
        }

        return true;
    }

    /**
     * Gets common data needed for admin views.
     *
     * @return array The common view data.
     */
    protected function get_common_view_data(): array {
        $data = [
            'app_id'          => Config::get_app_id(),
            'app_secret'      => Config::get_app_secret() ? "***" : "",
            'has_credentials' => !empty(Config::get_app_id()) && Config::get_app_secret(),
            'is_app_token_set' => !empty(Config::get(Config::OPT_APP_ACCESS_TOKEN)),
            'has_connection' => $this->has_connection(),
        ];

        return $data;
    }

    /**
     * Generates the URL for the current admin page with optional parameters.
     *
     * @param array $params Optional parameters to append to the URL.
     * @return string The admin page URL.
     */
    protected function this_admin_page_url(array $params = []): string {
        if (empty($this->menu_slug)) {
            return '';
        }

        $base_params = [
            'post_type' => Config::POST_TYPE,
            'page'      => $this->menu_slug,
        ];

        return \add_query_arg(array_merge($base_params, $params), \admin_url('edit.php'));
    }

    /**
     * Generates an action URL for admin pages.
     *
     * @param string $action The action name.
     * @param array $extra_params Extra parameters to append to the URL.
     * @return string The generated action URL.
     */
    protected function generate_action_url(string $action, array $extra_params = []): string {
        $params = array_merge([
            'action'   => $action,
            'rs_nonce' => \wp_create_nonce('rs_instagram_feed_action'),
            'redirect_uri' => urlencode($this->this_admin_page_url()),
        ], $extra_params);

        return $this->this_admin_page_url($params);
    }

    /**
     * Renders the page view.
     *
     * @return void
     */
    abstract public function render(): void;
}