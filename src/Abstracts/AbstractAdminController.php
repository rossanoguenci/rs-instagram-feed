<?php

namespace ReactSmith\InstagramFeed\Abstracts;

use ReactSmith\InstagramFeed\Core\Config;
use ReactSmith\InstagramFeed\Services\MetaOAuthService;

/**
 * Abstract class for Admin Controllers.
 */
abstract class AbstractAdminController {
    /** @var MetaOAuthService|null Meta OAuth service instance */
    protected ?MetaOAuthService $meta_service = null;

    /**
     * Constructor.
     */
    public function __construct() {}

    /**
     * Safely redirects to a given URL or to the redirect_uri in request.
     *
     * @param string|null $url The URL to redirect to.
     * @return void
     */
    protected static function safe_redirect(?string $url = null): void {
        if (empty($url)) {
            $url = $_GET['redirect_uri']
                ?? $_POST['redirect_uri']
                ?? '';
        }

        if (empty($url)) {
            return;
        }

        $url = urldecode($url);
        $url = \esc_url_raw($url);

        \wp_safe_redirect($url);
        exit;
    }

    /**
     * Checks if the Meta connection is established and tokens are set.
     *
     * @return bool True if connection is ok, false otherwise.
     */
    protected function has_connection(): bool {
        if(!$this->meta_service){
            $this->init_meta_service();
        }

        $has_service         = $this->meta_service instanceof MetaOAuthService;
        $has_app_access      = $has_service && $this->meta_service->isAppAccessTokenSet();
        $has_business_token  = $has_service && $this->meta_service->isBusinessTokenSet();

        $ok = $has_service && $has_app_access && $has_business_token;

        if (!$ok) {
            $data_to_log = [
                'message'                  => 'InstagramFeed: has_connection() failed',
                'has_service'              => $has_service,
                'has_app_access'           => $has_app_access,
                'has_business_token'       => $has_business_token,
                'config_app_id_set'        => (bool) Config::get_app_id(),
                'config_app_secret_set'    => (bool) Config::get_app_secret(),
                'config_business_id_set'   => (bool) Config::get(Config::OPT_BUSINESS_ACCOUNT_ID),
                'config_app_access_token'  => (bool) Config::get(Config::OPT_APP_ACCESS_TOKEN),
                'config_business_id_token' => (bool) Config::get(Config::OPT_BUSINESS_ACCOUNT_ACCESS_TOKEN),
            ];

            error_log(var_export($data_to_log, true));
        }

        return $ok;
    }

    /**
     * Initializes the Meta service with the current configuration.
     *
     * @return void
     */
    protected function init_meta_service(): void {
        $app_id = Config::get_app_id();
        $app_secret_flag = Config::get_app_secret();

        if (!$app_id || !$app_secret_flag) { return; }

        $this->meta_service = new MetaOAuthService([
           'app_id'       => $app_id,
           'app_secret'   => Config::get_app_secret(true),
           'app_access_token' => Config::get(Config::OPT_APP_ACCESS_TOKEN, true),
           'business_id'  => Config::get(Config::OPT_BUSINESS_ACCOUNT_ID),
           'business_id_token' => Config::get(Config::OPT_BUSINESS_ACCOUNT_ACCESS_TOKEN, true),
        ]);
    }

    /**
     * Refreshes the Meta service.
     *
     * @return void
     */
    protected function refresh_meta_service(): void {
        $this->init_meta_service();
    }

}