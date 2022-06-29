<?php

require_once 'Client.php';
require_once 'Request.php';
require_once 'Session.php';

/**
 * Main controller
 * Containing all the actions that can be run by the plugin
 *
 * @todo move wordpress actions elsewhere
 */
class Controller
{
    /**
     * Get Authorization Action
     */
    final public function getAuthorization(): void
    {
        $debug = (bool) Request::getQueryParameter('debug');
        update_option('open_oauth_debug', true === $debug);

        Client::getAuthorization();
    }

    /**
     * Get access token from code
     * @param string $code
     * @return array|null
     */
    final public function retrieveAccessToken(string $code): ?array
    {
        return Client::getToken(
            get_option('open_oauth_token_endpoint'),
            get_option('open_oauth_redirect_uri'),
            get_option('open_oauth_client_id'),
            get_option('open_oauth_client_secret'),
            $code
        );
    }

    /**
     * Get user information with access token
     * @param string $accessToken
     * @return array|null
     */
    final public function retrieveUserInfo(string $accessToken): ?array
    {
        return Client::getUserInfo(
            get_option('open_oauth_userinfo_endpoint'),
            $accessToken
        );
    }

    final public function getReferrerUrl(): string
    {
        $url = home_url() . '?no-ref';

        Session::start();
        if (Session::has(Session::REFERRER_NAME)) {
            $url = home_url() . Session::get(Session::REFERRER_NAME);
            Session::unset();
        }

        return $url;
    }

    /**
     * Process Login Action
     * Create or update WordPress user with given userInfo
     * Lastly, log the user that has been created/updated
     *
     * @param array $userInfo
     */
    final public function processLogin(array $userInfo): void
    {
        $userId = $this->createOrUpdateUser($userInfo);
        $this->loginUser($userId);

        header('Location: ' . $this->getReferrerUrl());
        exit;
    }

    /**
     * Post Configuration Action
     */
    final public function postConfiguration(): void
    {
        $configurationOptions = [
            'open_oauth_app_name' => 'app_name',
            'open_oauth_redirect_uri' => 'redirect_uri',
            'open_oauth_client_id' => 'client_id',
            'open_oauth_client_secret' => 'client_secret',
            'open_oauth_client_scope' => 'client_scope',
            'open_oauth_authorization_endpoint' => 'authorization_endpoint',
            'open_oauth_token_endpoint' => 'token_endpoint',
            'open_oauth_userinfo_endpoint' => 'userinfo_endpoint',
            'open_oauth_force_auth' => 'force_auth',
        ];

        $this->updateOptions($configurationOptions);
    }

    /**
     * Post Attributes Action
     */
    final public function postAttributes(): void
    {
        $attributesOptions = [
            'open_oauth_user_uid' => 'user_uid',
            'open_oauth_user_email' => 'user_email',
            'open_oauth_user_name' => 'user_name',
            'open_oauth_user_family_name' => 'user_family_name',
            'open_oauth_user_display_name' => 'user_display_name',
        ];

        $this->updateOptions($attributesOptions);
    }

    /**
     * Run multiple update option function on array keys and values.
     *
     * @param array $options
     */
    private function updateOptions(array $options): void
    {
        $post = Request::getPostParameters();

        foreach ($options as $option => $fieldName) {
            update_option($option, isset($post[$fieldName]) ? sanitize_text_field($post[$fieldName]) : '');
        }
    }

    /**
     * Create or update user with provider user information.
     *
     * @param array $providerUserInfo
     * @return int|WP_Error
     */
    private function createOrUpdateUser(array $providerUserInfo): int
    {
        $providerUserUid = $providerUserInfo[get_option('open_oauth_user_uid', 'uid')];
        $providerUserEmail = $providerUserInfo[get_option('open_oauth_user_email', 'mail')];
        $providerUserGivenName = $providerUserInfo[get_option('open_oauth_user_name', 'givenName')];
        $providerUserFamilyName = $providerUserInfo[get_option('open_oauth_user_family_name', 'familyName')];
        $providerUserDisplayName = $providerUserInfo[get_option('open_oauth_user_display_name', 'displayName')];

        $userInfo = [];
        $userInfo['user_login'] = $providerUserUid;
        $userInfo['user_email'] = $providerUserEmail;
        $userInfo['first_name'] = $providerUserGivenName;
        $userInfo['last_name'] = $providerUserFamilyName;
        $userInfo['display_name'] = $providerUserDisplayName;

        // try to find userId with same uid
        $userId = $this->findUserIdBy('user_login', $providerUserUid);
        // @todo
        // we might want to get rid of that search by email
        // i keep it for consistency and backwards compatibility
        if (!$userId) {
            // if not found, try with the email address
            $userId = $this->findUserIdBy('user_email', $providerUserEmail);
        }

        // if we really cannot find a user in database
        // perform the user creation process
        if (!$userId) {
            $userInfo['user_pass'] = wp_generate_password(12, false);
            $userIdentifier = wp_insert_user($userInfo);

            if (is_int($userIdentifier)) {
                return $userIdentifier;
            }

            // in case of error, you'll get the info with that
            var_dump($userIdentifier);die;
        }

        $userInfo['ID'] = $userId;
        return wp_update_user($userInfo);
    }

    /**
     * Login user by its user identifier.
     *
     * @param int $userId
     */
    private function loginUser(int $userId): void
    {
        wp_set_current_user($userId);
        wp_set_auth_cookie($userId);

        $user = get_user_by('ID', $userId);

        do_action('wp_login', $user->user_login, $user);
    }

    /**
     * Find user ID by key with value in wordpress database.
     *
     * @param string $key
     * @param string $value
     * @return string|null
     */
    private function findUserIdBy(string $key, string $value): ?string
    {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT ID FROM $wpdb->users WHERE $key = %s;",
            $value
        );

        $userId = $wpdb->get_var($query);

        if ($userId) {
            return $userId;
        }

        return null;
    }
}
