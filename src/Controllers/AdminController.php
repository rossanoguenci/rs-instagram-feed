<?php
namespace ReactSmith\InstagramFeed\Controllers;

use ReactSmith\InstagramFeed\Abstracts\AbstractAdminController;
use ReactSmith\InstagramFeed\Core\Config;
use ReactSmith\InstagramFeed\Core\CronManager;

/**
 * Controller for administrative tasks such as saving credentials and handling OAuth callbacks.
 */
class AdminController extends AbstractAdminController {

    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Handles saving credentials from the settings page.
     *
     * @return void
     */
    public static function handle_save_credentials() {
        $new_app_id = isset($_POST[Config::OPT_APP_ID]) ? \sanitize_text_field($_POST[Config::OPT_APP_ID]) : '';
        $new_app_secret = isset($_POST[Config::OPT_APP_SECRET]) ? \sanitize_text_field($_POST[Config::OPT_APP_SECRET]) : '';

        $old_app_id = Config::get_app_id();
        $has_changes = false;

        // 1. Validate and Check App ID
        if (!empty($new_app_id)) {
            if (!ctype_digit($new_app_id)) {
                NotificationController::add('App ID must contain only numbers.', 'error');
                return;
            }
            if ($new_app_id !== $old_app_id) {
                Config::set(Config::OPT_APP_ID, $new_app_id);
                $has_changes = true;
            }
        }

        // 2. Validate and Check App Secret
        if (!empty($new_app_secret) && $new_app_secret !== '***') {
            if (!ctype_alnum($new_app_secret)) {
                NotificationController::add('App Secret must contain only letters and numbers.', 'error');
                return;
            }
            Config::set(Config::OPT_APP_SECRET, $new_app_secret, true);
            $has_changes = true;
        }

        // 3. Finalize and Redirect
        if ($has_changes) {
            NotificationController::add('Settings updated successfully.', 'success');
        } else {
            NotificationController::add('No changes detected.', 'info');
        }

        static::safe_redirect();
    }

    /**
     * Handles the OAuth callback from Meta.
     *
     * @return void
     */
    public function handle_oauth_callback() {
        $redirect_uri = \site_url().'/wp-admin/edit.php?post_type=instagram-feed&page=account-connection';

        if (isset($_GET['error_code'])) {
            $message = "FB Login - Error " . esc_html($_GET['error_code']) . ": " . esc_html($_GET['error_reason']);

            NotificationController::add($message, 'error');
            error_log($message);

            $this->safe_redirect($redirect_uri);

        }

        $this->init_meta_service();

        if(!$this->meta_service) {
            NotificationController::add('Meta Service not available.', 'error');
            $this->safe_redirect($redirect_uri);
        }

        // Exchanging code for access token
        $response = $this->meta_service->exchangeCodeForAccessToken($_GET['code']);

        if (!$response || !isset($response['access_token'])) {
            $message = 'Account not connected, error in exchanging codes.';
            NotificationController::add($message, 'error');

            error_log($message);
            error_log($response);

            $this->safe_redirect($redirect_uri);
        }

        $app_access_token = $response['access_token'];

        // Saving app access_token and expiration
        Config::set(Config::OPT_APP_ACCESS_TOKEN, $response['access_token'], true);

        $app_expires_in = isset($response['expires_in']) ? time() + intval($response['expires_in']) : 0;
        Config::set(Config::OPT_APP_TOKEN_EXPIRY, $app_expires_in);

        $this->refresh_meta_service();

        //Retrieving IG business account info
        $business_account = $this->meta_service->retrieveInstagramBusinessAccount();

        if(!$business_account || !isset($business_account['instagram_business_account']['id'])){
            $message = 'Account not connected, error in retrieving business account info.';
            NotificationController::add($message, 'error');
            error_log($message);

            $this->safe_redirect($redirect_uri);
        }

        // Saving IG business account info and token
        Config::set(Config::OPT_BUSINESS_ACCOUNT_ID, $business_account['instagram_business_account']['id']);
        Config::set(Config::OPT_BUSINESS_ACCOUNT_ACCESS_TOKEN, $business_account['access_token'], true);


        // Saving token expiration
        $business_expires_in = isset($response['expires_in']) ? time() + intval($response['expires_in']) : 0;
        Config::set(Config::OPT_BUSINESS_ACCOUNT_ACCESS_TOKEN_EXPIRY, $business_expires_in);


        //final stage
        $this->refresh_meta_service();
        CronManager::initScheduleCronOptions();
        NotificationController::add('Account connected successfully.', 'success');

        $this->safe_redirect($redirect_uri);
    }

    /**
     * Handles revoking OAuth access.
     *
     * @return void
     */
    public function handle_oauth_revoke_access(): void{
        $message = 'Error while revoking access.';
        $type = 'error';

        if(
            Config::delete(Config::OPT_APP_ACCESS_TOKEN) &&
            Config::delete(Config::OPT_APP_TOKEN_EXPIRY) &&
            Config::delete(Config::OPT_BUSINESS_ACCOUNT_ID) &&
            Config::delete(Config::OPT_BUSINESS_ACCOUNT_ACCESS_TOKEN) &&
            Config::delete(Config::OPT_BUSINESS_ACCOUNT_ACCESS_TOKEN_EXPIRY)
        ){
            $this->meta_service->revokeAccess(); // it does absolutely nothing for now

            $message = 'Access revoked successfully.';
            $type = 'success';
        }


        NotificationController::add($message, $type);

        $this->safe_redirect();

    }

    /**
     * Clears all connection settings and tokens.
     *
     * @return void
     */
    public function handle_clear_connection(): void {
        $results = Config::clear_all(true);

        $message = empty($results) ? 'Nothing was cleared.' : 'All settings and tokens have been removed.';
        $type = empty($results) ? 'warning' : 'success';

        NotificationController::add($message,$type);

        static::safe_redirect();
    }

}