<?php
/*
Plugin Name: Open OAuth Client
Plugin URI: https://github.com/wlalele/open-oauth-client
Description: Just a simple oauth client for wordpress
Author: Amine Dai
Version: 0.1.2
Author URI: https://github.com/wlalele
License: GPLv2
Text Domain: open-oauth-client
*/

require_once 'open-oauth-client.functions.php';
require_once 'classes/Controller.php';
require_once 'classes/Request.php';

if (!class_exists('open_oauth_client')) {
    class open_oauth_client
    {
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

        final private function startsWith($haystack, $needle)
        {
            $length = strlen($needle);
            return substr($haystack, 0, $length) === $needle;
        }

        final public function router(): void
        {
            $controller = new Controller();
            $auth = Request::getQueryParameter('auth');
            $debug = (bool) Request::getQueryParameter('debug');
            $code = Request::getQueryParameter('code');
            $action = Request::getPostParameter('action');
            $configurationNonce = Request::getPostParameter('configuration_nonce');
            $attributesNonce = Request::getPostParameter('attributes_nonce');
            $isLoggedIn = is_user_logged_in();
            $forceAuth = get_option('open_oauth_force_auth', false);
            $isLoginPage = $this->startsWith(Request::getUri(), '/wp-login');

            if (($forceAuth || 'sso' === $auth) && (true === $debug || !$isLoggedIn) && !isset($code) && !$isLoginPage) {
                $controller->getAuthorization();
            }

            if (isset($code)) {
                $controller->processLogin($code);
            }

            if (isset($action, $configurationNonce) && 'configuration' === $action && !empty($configurationNonce)) {
                $controller->postConfiguration();
            }

            if (isset($action, $attributesNonce) && 'attributes' === $action && !empty($attributesNonce)) {
                $controller->postAttributes();
            }
        }
    }

    $plugin = new open_oauth_client();
    $plugin->init();
}
