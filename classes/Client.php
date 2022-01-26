<?php

require_once 'Request.php';

class Client
{
    /**
     * Performs get authorization code from given oauth provider
     */
    public static function getAuthorization(): void
    {
        $baseUrl = get_option('open_oauth_authorization_endpoint');
        $queryParams = [
            'client_id' => get_option('open_oauth_client_id'),
            'scope' => get_option('open_oauth_client_scope'),
            'redirect_uri' => get_option('open_oauth_redirect_uri'),
            'response_type' => 'code',
            'state' => base64_encode(get_option('open_oauth_app_name')),
        ];

        header('Location: ' . $baseUrl . '?' . http_build_query($queryParams));
        exit;
    }

    /**
     * Performs get token from given oauth provider
     *
     * @param string $tokenEndpointUrl
     * @param string $redirectUri
     * @param string $clientId
     * @param string $clientSecret
     * @param string $code
     * @param string $grantType
     * @param string $scope
     * @return array
     */
    public static function getToken(
        string $tokenEndpointUrl,
        string $redirectUri,
        string $clientId,
        string $clientSecret,
        string $code,
        string $grantType = 'authorization_code',
        string $scope = 'openid profile'
    ): array {
        $authorization = base64_encode("$clientId:$clientSecret");
        $content = sprintf(
            'client_id=%s&client_secret=%s&code=%s&redirect_uri=%s&grant_type=%s&scope=%s',
            $clientId,
            $clientSecret,
            $code,
            $redirectUri,
            $grantType,
            rawurlencode($scope)
        );

        $response = wp_remote_post(
            $tokenEndpointUrl,
            [
                'method' => Request::METHOD_POST,
                'headers' => [
                    'Authorization' => 'Basic' . $authorization,
					'Accept: ' . Request::MIME_JSON,
                    'Content-Type: ' . Request::MIME_FORM_URLENCODED,
                ],
                'body' => $content,
                'data_format' => 'body'
            ]
        );

        return json_decode($response['body'], true);
    }

    /**
     * Performs get user information from given oauth provider
     *
     * @param string $userInfoUrl
     * @param string $accessToken
     * @return array
     */
  	public static function getUserInfo(
  	    string $userInfoUrl,
        string $accessToken
    ): array {
		$headers = [];
		$headers['Authorization'] = sprintf('Bearer %s', $accessToken);

		$response = wp_remote_post(
		    $userInfoUrl,
            [
                'method' => Request::METHOD_GET,
                'headers' => $headers,
                'cookies' => [],
            ]
        );

        return json_decode($response['body'],true);
	}
}
