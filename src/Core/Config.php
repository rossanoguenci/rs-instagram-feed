<?php
namespace ReactSmith\InstagramFeed\Core;

use ReactSmith\InstagramFeed\Core\SecretManager;
use ReactSmith\Core\Debug;

/**
 * Configuration manager for the plugin.
 * Handles getting, setting, and deleting WordPress options with optional encryption.
 */
class Config {

    const POST_TYPE = 'instagram-feed';

    // Constants for option keys to avoid typos
    const OPT_APP_ID = 'rs_instagram_feed_app_id';
    const OPT_APP_SECRET = 'rs_instagram_feed_app_secret';
    const OPT_APP_ACCESS_TOKEN = 'rs_instagram_feed_app_access_token';
    const OPT_APP_TOKEN_EXPIRY = 'rs_instagram_feed_app_access_token_expiry_seconds';
    const OPT_BUSINESS_ACCOUNT_ID = 'rs_instagram_feed_business_account_id';
    const OPT_BUSINESS_ACCOUNT_ACCESS_TOKEN = 'rs_instagram_feed_business_account_access_token';
    const OPT_BUSINESS_ACCOUNT_ACCESS_TOKEN_EXPIRY = 'rs_instagram_feed_business_account_access_token_expiry_seconds';
    const OPT_CRON_HOOK = 'rs_instagram_feed_cron_hook';
    const OPT_CRON_AUTO_SYNC = 'rs_instagram_feed_cron_auto_sync';
    const OPT_CRON_LAST_SYNC = 'rs_instagram_feed_cron_last_sync';
    const OPT_CRON_SYNC_INTERVAL = 'rs_instagram_feed_cron_sync_interval';
    const OPT_CLEAN_CRON_HOOK = 'rs_instagram_feed_clean_cron_hook';
    const OPT_MAX_POSTS_NUMBER = 'rs_instagram_feed_max_posts_number';
    const OPT_MESSAGES = 'rs_instagram_feed_messages';

    const OPT_LIST = [
        self::OPT_APP_ID,
        self::OPT_APP_SECRET,
        self::OPT_APP_ACCESS_TOKEN,
        self::OPT_APP_TOKEN_EXPIRY,
        self::OPT_BUSINESS_ACCOUNT_ID,
        self::OPT_BUSINESS_ACCOUNT_ACCESS_TOKEN,
        self::OPT_BUSINESS_ACCOUNT_ACCESS_TOKEN_EXPIRY,
        self::OPT_CRON_AUTO_SYNC,
        self::OPT_CRON_LAST_SYNC,
        self::OPT_CRON_SYNC_INTERVAL,
        self::OPT_MAX_POSTS_NUMBER,
        self::OPT_MESSAGES,
    ];

    const OPT_CONST_LIST = [
        self::OPT_CRON_HOOK,
        self::OPT_CLEAN_CRON_HOOK,
    ];


    /**
     * Get a setting with a default value.
     *
     * @param string $option The option key.
     * @param bool $decrypt Whether to decrypt the value.
     * @param mixed $default_value The default value if the option is not set.
     * @return mixed The option value.
     */
    public static function get(string $option, bool $decrypt = false, mixed $default_value = null): mixed {

        $value = \get_option($option, $default_value);

        if(!empty($value) && $decrypt === true){
            $value = SecretManager::decrypt($value);
        }

        return $value;
    }

    /**
     * Set a setting and update the database.
     *
     * @param string $option The option key.
     * @param mixed $value The value to set.
     * @param bool $encrypt Whether to encrypt the value.
     * @return bool True on success, false on failure.
     */
    public static function set(string $option, mixed $value, bool $encrypt = false): bool {

        $payload = is_string($value) ? \sanitize_text_field($value) : $value;

        if($encrypt === true){
             $payload = SecretManager::encrypt($payload);
        }

        return \update_option($option, $payload);
    }

    /**
     * Delete a setting.
     *
     * @param string $key The option key.
     * @return bool True on success, false on failure.
     */
    public static function delete(string $key): bool {
        return \delete_option($key);
    }

    /**
     * Delete all app settings.
     *
     * @param bool $with_return Whether to return the status of each deletion.
     * @return array The list of deleted options and their status if $with_return is true.
     */
    public static function clear_all(bool $with_return = false): array {
        $return = [];

        foreach(self::OPT_LIST as $option){
            $return[$option] = self::delete($option) ? 'deleted' : 'not deleted';
        }

        return $with_return ? $return : [];
    }

    /**
     * Specific getter for App ID.
     *
     * @return string|null The App ID or null if not set.
     */
    public static function get_app_id(): ?string {
        return self::get(self::OPT_APP_ID);
    }

    /**
     * Specific getter for App Secret.
     *
     * @param bool $decrypted Whether to return the decrypted secret or just a boolean indicating if it's set.
     * @return string|bool The decrypted secret, or true if set, or null/false if not.
     */
    public static function get_app_secret(bool $decrypted = false): string|bool {
        return $decrypted ? self::get(self::OPT_APP_SECRET, true, false) : !empty(self::get(self::OPT_APP_SECRET));
    }

    /**
     * Gets the page title for the admin menu.
     *
     * @return string The page title.
     */
    public static function get_page_title(): string {
        return __('Instagram Feed', 'reactsmith');
    }

    /**
     * Retrieves a list of tokens and their expiration status.
     *
     * @return array The token expiration list.
     */
    public static function tokenExpirationList(): array{
        $expires_at_list = [
            self::OPT_APP_TOKEN_EXPIRY => self::get(self::OPT_APP_TOKEN_EXPIRY),
            self::OPT_BUSINESS_ACCOUNT_ACCESS_TOKEN_EXPIRY => self::get(self::OPT_BUSINESS_ACCOUNT_ACCESS_TOKEN_EXPIRY),
        ];

        $return_list = [];

        foreach( $expires_at_list as $key => $value ){

            $match_result = match(true){
                $value === null => 'Token not set',
                $value === '0' => 'Long-life token',
                time() >= (int)$value => 'Token expired',
                time() < (int)$value => date('r', $value),
                default => 'N/A',
            };

            $return_list[$key] = [
                'token_expiry_seconds' => $value,
                'token_expiry_at' => $match_result,
                'is_token_expired' => $value === '0' ? false : (time() > (int)$value),
            ];

        }

        return $return_list;
    }
}