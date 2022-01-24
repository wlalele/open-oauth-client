<?php

require_once 'Client.php';
require_once 'Request.php';

/**
 * Main controller
 * Containing all the actions that can be runned by the plugin
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
        $debug = Request::getQueryParameter('debug');
        update_option('open_oauth_debug', true === (bool) $debug);

        Client::getAuthorization();
    }

    /**
     * Get token, then fetches user information with it
     * @param string $code
     * @return array|null
     */
    final public function retrieveUserInfo(string $code): ?array
    {
        $token = Client::getToken(
            get_option('open_oauth_token_endpoint'),
            get_option('open_oauth_redirect_uri'),
            get_option('open_oauth_client_id'),
            get_option('open_oauth_client_secret'),
            $code
        );

        $accessToken = $token['access_token'] ?? null;

        if (!isset($accessToken)) {
            return null;
        }

        $userInfo = Client::getUserInfo(
            get_option('open_oauth_userinfo_endpoint'),
            $accessToken
        );

        if (!isset($userInfo) || !$userInfo) {
            return null;
        }

        if (isset($userInfo['error_description'])) {
            return null;
        }

        if (isset($userInfo['error'])) {
            return null;
        }

        return $userInfo;
    }

    /**
     * Process Login Action
     * Create or update wordpress user with given userInfo
     * Lastly, log the user that has been created/updated
     *
     * @param array $userInfo
     */
    final public function processLogin(array $userInfo): void
    {
        $userId = $this->createOrUpdateUser($userInfo);
        $this->loginUser($userId);
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
            'open_oauth_user_name' => 'user_name',
            'open_oauth_user_email' => 'user_email',
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

        $userId = $this->findUserIdByEmail($providerUserEmail);

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

        wp_redirect(home_url());
    }

    /**
     * Find user ID by email in wordpress database.
     *
     * @param string $email
     * @return string|null
     */
    private function findUserIdByEmail(string $email): ?string
    {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT ID FROM $wpdb->users WHERE user_email = %s;",
            $email
        );

        $userId = $wpdb->get_var($query);

        if ($userId) {
            return $userId;
        }

        return null;
    }
}
