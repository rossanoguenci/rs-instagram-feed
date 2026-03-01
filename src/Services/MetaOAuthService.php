<?php
namespace ReactSmith\InstagramFeed\Services;

use ReactSmith\InstagramFeed\Controllers\NotificationController;
use ReactSmith\Core\Debug;

/**
 * Service class for handling Meta OAuth and API requests.
 */
class MetaOAuthService{

    /** @var string Protocol for API requests. */
    private const PROTOCOL = "https";

    /** @var string Host URL for Meta API. */
    private const HOST_URL = "graph.facebook.com";

    /** @var string Meta API version. */
    private const VERSION = "v25.0";

    /** @var array Fields to retrieve for Instagram media. */
    private const IG_MEDIA_FIELDS = [
            'id',
            'alt_text',
            'caption',
            'media_type',
            'media_url',
            'shortcode',
            'thumbnail_url',
            'permalink',
            'timestamp',
    ];

    /** @var string Meta App ID. */
    private string $app_id;

    /** @var string Meta App Secret. */
    private string $app_secret;

    /** @var string|null Meta App access token. */
    private ?string $app_access_token;

    /** @var string|null Instagram Business ID. */
    private ?string $business_id;

    /** @var string|null Instagram Business ID access token. */
    private ?string $business_id_token;

    /**
     * MetaOAuthService constructor.
     *
     * @param array{
     *   app_id: string,
     *   app_secret: string,
     *   app_access_token?: string|null,
     *   business_id?: string|null,
     *   business_id_token?: string|null
     * } $options Service configuration options.
     * @throws \InvalidArgumentException If required options are missing or invalid.
     */
    public function __construct(array $options) {
        $this->parse_options($options);
    }

    /**
     * Parses and validates service options.
     *
     * @param array $options Configuration options.
     * @return void
     * @throws \InvalidArgumentException If required options are missing.
     */
    private function parse_options(array $options): void {
        // Required parameters validation
        if (empty($options['app_id']) || !is_string($options['app_id'])) {
            throw new \InvalidArgumentException('MetaOAuthService: "app_id" is required and must be a string.');
        }
        if (empty($options['app_secret']) || !is_string($options['app_secret'])) {
            throw new \InvalidArgumentException('MetaOAuthService: "app_secret" is required and must be a string.');
        }

        // Assignment with type casting/verification
        $this->app_id       = $options['app_id'];
        $this->app_secret   = $options['app_secret'];
        $this->app_access_token = isset($options['app_access_token']) ? (string)$options['app_access_token'] : null;
        $this->business_id  = isset($options['business_id']) ? (string)$options['business_id'] : null;
        $this->business_id_token  = isset($options['business_id_token']) ? (string)$options['business_id_token'] : null;
    }

    /**
     * Generates the base URL for Meta API requests.
     *
     * @param string $path Optional API path.
     * @return string The full API URL.
     */
    private static function base_URL(string $path = '/'): string {
        return \trailingslashit(self::PROTOCOL . "://" . self::HOST_URL . "/" . self::VERSION) . \ltrim($path, '/');
    }

    /**
     * Returns the redirect URI for OAuth authentication.
     *
     * @return string The redirect URI.
     */
    private static function redirect_uri(): string{
        return \site_url().'/wp-admin/edit.php?post_type=instagram-feed&page=account-connection&meta_login=1';
    } //todo: this might be improved

    /**
     * Performs a remote GET request to the Meta API.
     *
     * @param string $url The full URL to request.
     * @return array|null The decoded response data or null on failure.
     */
    private static function request(string $url): array|null {
        if (empty($url)) return null;

        $response = \wp_remote_get($url, [
            'timeout' => 15, // Defensive: Prevent long hangs
        ]);

        // Handle WordPress/Network errors
        if (\is_wp_error($response)) {
            $message = 'Meta API Network Error: ' . $response->get_error_message();

            NotificationController::add($message, 'error');
            Debug::log($message);
            return null;
        }

        $body = \wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        // Handle JSON decoding errors
        if (json_last_error() !== JSON_ERROR_NONE) {
            $message = 'Meta API JSON Decode Error: ' . json_last_error_msg();

            NotificationController::add($message, 'error');
            Debug::log($message);

            return null;
        }

        // Handle Meta API level errors (e.g., OAuthException)
        if (isset($data['error'])) {
            $error_message = $data['error']['message'] ?? __('Unknown error','reactsmith');
            $message = "Meta API Error Response: $error_message";

            NotificationController::add($message, 'error');
            Debug::log(print_r($data['error'],true));

            return null;
        }

        return isset($data['data']) ? $data['data'] : $data;
    }

    /**
     * Checks the validity of an access token.
     *
     * @param string $input_token The token to debug. Defaults to app access token.
     * @return array|bool The token debug info or false on failure.
     */
    private function debug_token(string $input_token=''): array|bool{
        if(empty($input_token) && empty($this->app_access_token)) return false;

        $data =[
           'input_token' => $input_token ?: $this->app_access_token,
           'access_token' => "$this->app_id|$this->app_secret",
       ];

        $request_url = \add_query_arg($data, self::base_URL('/debug_token?'));
//         $request_url = self::base_URL('/debug_token?').http_build_query($data);
        $api_response = self::request($request_url);

        //this is already displayed in request()
//         NotificationController::add(print_r($api_response,true), 'info');

       return $api_response ?? false;
    }

    /**
     * Generates the login URL for Meta OAuth.
     *
     * @return string The OAuth login URL.
     */
    public function loginURL(): string{
        if(empty($this->app_id)) return '';

        $data = [
            'client_id' => $this->app_id,
            'display' => 'page',
            'extras' => '{"setup":{"channel":"IG_API_ONBOARDING"}}',
            'redirect_uri' => self::redirect_uri(),
            'response_type' => 'code',
            'scope' => 'instagram_basic,instagram_content_publish',
            'state'         => \wp_create_nonce('rs_instagram_feed_meta_oauth_state'),
        ];

        return "https://www.facebook.com/dialog/oauth?". http_build_query($data); //do not use \add_query_arg()
    }

    /**
     * Revokes access by clearing tokens.
     *
     * @return void
     */
    public function revokeAccess(): void{
        $this->app_access_token = null;
        $this->business_id_token = null;
    }

    /**
     * Checks if the app access token is valid.
     *
     * @return bool|null True if valid, false if invalid, null if not set.
     */
    public function isAppAccessTokenValid(): bool | null{
        if(!$this->isAppAccessTokenSet()) return null;

        $response = $this->debug_token(); //default
        return isset($response['is_valid'])? (bool)$response['is_valid'] : null;
    }

    /**
     * Checks if the business access token is valid.
     *
     * @return bool|null True if valid, false if invalid, null if not set.
     */
    public function isBusinessAccessTokenValid(): bool | null{
        if(!$this->isBusinessTokenSet()) return null;

        $response = $this->debug_token($this->business_id_token);
        return isset($response['is_valid'])? (bool)$response['is_valid'] : null;
    }

    /**
     * Checks if the app access token is set.
     *
     * @return bool True if set.
     */
    public function isAppAccessTokenSet(): bool{
        return !empty($this->app_access_token);
    }

    /**
     * Checks if the business token is set.
     *
     * @return bool True if set.
     */
    public function isBusinessTokenSet(): bool{
        return !empty($this->business_id_token);
    }

    /**
     * Exchanges an authorization code for an access token.
     *
     * @param string $code The authorization code from Meta.
     * @return array|false|null The token response or false/null on failure.
     */
    public function exchangeCodeForAccessToken(string $code): array|false|null {
        if(empty($code)) return null;

        $url = self::base_URL('/oauth/access_token?') . http_build_query([
            'client_id' => $this->app_id,
            'client_secret' => $this->app_secret,
            'code' => $code,
            'redirect_uri' => self::redirect_uri(),
        ]);

        return self::request($url);
    }

    /**
     * Retrieves the Instagram Business Account details.
     *
     * Returns an array containing: [ id, name, access_token, instagram_business_account => [id] ]
     *
     * @return array|null The business account data or null on failure.
     */
    public function retrieveInstagramBusinessAccount(): array|null{
        if(!$this->isAppAccessTokenSet() || !$this->isAppAccessTokenValid()) return null;

        $data = [
           'fields' => 'id,name,access_token,instagram_business_account',
           'access_token' => $this->app_access_token,
        ];

        $request_url = self::base_URL('/me/accounts?').http_build_query($data);

        return self::request($request_url)[0] ?? null;
    }

    /**
     * Returns the fields for Instagram media.
     *
     * @param bool $into_string Whether to return as a comma-separated string.
     * @return array|string The media fields.
     */
    public function mediaFieldsIG(bool $into_string = false): array|string{
        return $into_string ? implode(',', self::IG_MEDIA_FIELDS) : self::IG_MEDIA_FIELDS;
    }


    /**
     * Retrieves the latest posts from the Instagram Business Account.
     *
     * @param int $limit The number of posts to retrieve.
     * @return array|null The posts data or null on failure.
     */
    public function retrieveLatestPosts(int $limit = 6): array|null{
        if(
            !$this->isBusinessTokenSet() ||
            empty($this->business_id)
        ) return null;

        $request_url = self::base_URL("/$this->business_id/media?").http_build_query([
            'fields' => $this->mediaFieldsIG(true),
            'limit' => $limit,
            'access_token' => $this->business_id_token,
        ]);

        return self::request($request_url);
    }

}