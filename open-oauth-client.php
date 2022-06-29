<?php
/*
Plugin Name: Open OAuth Client
Plugin URI: https://github.com/wlalele/open-oauth-client
Description: Just a simple oauth client for WordPress
Author: Amine Dai
Version: 0.1.8
Author URI: https://github.com/wlalele
License: GPLv2
Text Domain: open-oauth-client
*/

require_once 'open-oauth-client.functions.php';
require_once 'classes/Controller.php';
require_once 'classes/Request.php';
require_once 'classes/Session.php';

if (!class_exists('open_oauth_client')) {
    class open_oauth_client
    {
        private $controller;

        public function __construct()
        {
            $this->controller = new Controller();
        }

        final public function init(): void
        {
            add_action('init', [$this, 'router']);
            add_action('admin_menu', [$this, 'addMenuPage']);
        }

        final public function addMenuPage(): void
        {
            add_menu_page(
                'Open OAuth Client',
                'Open OAuth',
                'manage_options',
                'open_oauth_client',
                'layout'
            );
        }

        final private function startsWith(string $haystack, string $needle): bool
        {
            $length = strlen($needle);
            return substr($haystack, 0, $length) === $needle;
        }

        final public function router(): void
        {
            $isLoginPage = $this->startsWith(Request::getUri(), '/wp-login');

            // don't do anything on wp-login, that's a no-go
            if ($isLoginPage) {
                return;
            }

            if (Request::getMethod() === Request::METHOD_POST) {
                $action = Request::getPostParameter('action');
                $configurationNonce = Request::getPostParameter('configuration_nonce');
                $attributesNonce = Request::getPostParameter('attributes_nonce');

                // edit configuration
                if (isset($action, $configurationNonce) && 'configuration' === $action && !empty($configurationNonce)) {
                    $this->controller->postConfiguration();
                }

                // edit attributes
                if (isset($action, $attributesNonce) && 'attributes' === $action && !empty($attributesNonce)) {
                    $this->controller->postAttributes();
                }

                return;
            }

            $isLoggedIn = is_user_logged_in();
            $debug = (bool) Request::getQueryParameter('debug');
            $auth = Request::getQueryParameter('auth');
            $code = Request::getQueryParameter('code');
            $state = Request::getQueryParameter('state');
            $error = Request::getQueryParameter('error');
            $errorDescription = Request::getQueryParameter('error_description');
            $isValidState = isset($state) && base64_decode($state) === get_option('open_oauth_app_name');
            $forceAuth = get_option('open_oauth_force_auth', false);

            if ($isLoggedIn && $debug === false && !isset($code) && !$isValidState) {
                return;
            }

            // error returned from authentication server
            if ($isValidState && isset($error)) {
                var_dump($error, $errorDescription);die;
            }

            // normal process of callback after authorization
            if ($isValidState && isset($code)) {
                $accessToken = $this->controller->retrieveAccessToken($code);

                if (isset($accessToken['error'])) {
                    //var_dump('accessToken', $accessToken); die;
                    header('Location: ' . home_url());
                    exit;
                }

                $userInfo = $this->controller->retrieveUserInfo($accessToken['access_token']);

                if (isset($userInfo['error'])) {
                    var_dump('userInfo', $userInfo); die;
                }

                if ($userInfo && '1' === get_option('open_oauth_debug')) {
                    echo '<pre>' . print_r($userInfo, true) . '</pre>';
                    update_option('open_oauth_debug', false);
                    exit;
                }

                if ($userInfo) {
                    $this->controller->processLogin($userInfo);
                }
            }

            if (!$forceAuth && $auth !== 'sso') {
                return;
            }

            // save referrer in session and
            // get authorization (fetch token from provider)
            Session::start();
            Session::set(Session::REFERRER_NAME, Request::getUri());

            $this->controller->getAuthorization();
        }
    }

    $plugin = new open_oauth_client();
    $plugin->init();
}
