<?php

declare(strict_types=1);

namespace Coretrek\Vipps\Login;

use Coretrek\Vipps\Exceptions\VippsException;
use Coretrek\Vipps\VippsClient;

/**
 * Vipps Login API
 *
 * Enables users to log in to a service using their Vipps or MobilePay credentials.
 * Implements OpenID Connect (OIDC) authentication flow.
 *
 * @see https://developer.vippsmobilepay.com/docs/APIs/login-api/
 */
class LoginApi
{
    private const BASE_PATH = '/access-management-1.0/access';
    private const USERINFO_PATH = '/vipps-userinfo-api/userinfo';
    private const CIBA_PATH = '/vipps-login-ciba/api';

    public function __construct(
        private readonly VippsClient $client
    ) {
    }

    /**
     * Get OpenID Connect configuration
     *
     * Retrieves the OpenID Connect discovery document containing
     * endpoint URLs and supported features.
     *
     * @return array<string, mixed> OpenID configuration
     * @throws VippsException
     */
    public function getOpenIdConfiguration(): array
    {
        return $this->client->request(
            'GET',
            self::BASE_PATH . '/.well-known/openid-configuration'
        );
    }

    /**
     * Get JSON Web Key Set (JWKS)
     *
     * Returns the public keys used to verify ID tokens and access tokens.
     *
     * @return array<string, mixed> JWKS document
     * @throws VippsException
     */
    public function getJwks(): array
    {
        return $this->client->request(
            'GET',
            self::BASE_PATH . '/.well-known/jwks.json'
        );
    }

    /**
     * Build authorization URL
     *
     * Creates a URL builder for constructing OAuth 2.0 authorization requests.
     *
     * @return AuthorizationUrlBuilder
     */
    public function buildAuthorizationUrl(): AuthorizationUrlBuilder
    {
        return new AuthorizationUrlBuilder($this->client);
    }

    /**
     * Exchange authorization code for tokens
     *
     * Exchanges an authorization code received from the OAuth callback
     * for access and ID tokens.
     *
     * @param string $code Authorization code from callback
     * @param string $redirectUri Redirect URI (must match the one used in authorization)
     * @param array<string, mixed> $options Additional options
     *   - code_verifier: PKCE code verifier (required if PKCE was used)
     *   - client_id: Client ID (required if using client_secret_post auth)
     *   - client_secret: Client secret (required if using client_secret_post auth)
     * @return array<string, mixed> Token response with access_token, id_token, etc.
     * @throws VippsException
     */
    public function exchangeCodeForTokens(
        string $code,
        string $redirectUri,
        array $options = []
    ): array {
        $data = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $redirectUri,
        ];

        if (isset($options['code_verifier'])) {
            $data['code_verifier'] = $options['code_verifier'];
        }

        if (isset($options['client_id'])) {
            $data['client_id'] = $options['client_id'];
        }

        if (isset($options['client_secret'])) {
            $data['client_secret'] = $options['client_secret'];
        }

        return $this->client->request(
            'POST',
            self::BASE_PATH . '/oauth2/token',
            [
                'form_params' => $data,
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
            ]
        );
    }

    /**
     * Get user information
     *
     * Retrieves user information using an access token.
     * The information returned depends on the scopes granted.
     *
     * @param string $accessToken Access token from token exchange
     * @return array<string, mixed> User information
     * @throws VippsException
     */
    public function getUserInfo(string $accessToken): array
    {
        try {
            $response = $this->client->getHttpClient()->request(
                'GET',
                self::USERINFO_PATH . '/',
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $accessToken,
                        'Merchant-Serial-Number' => $this->client->getMerchantSerialNumber(),
                    ],
                ]
            );

            $body = (string) $response->getBody();
            $data = json_decode($body, true);

            if (!is_array($data)) {
                throw new VippsException('Invalid userinfo response');
            }

            return $data;
        } catch (\Exception $e) {
            if ($e instanceof VippsException) {
                throw $e;
            }

            throw new VippsException('Failed to get user info: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Check if user exists (CIBA)
     *
     * Checks if a user exists before initiating a CIBA authentication.
     * Used for merchant-initiated login flows.
     *
     * @param string $loginHint User identifier (phone number or sub)
     * @return array<string, mixed> Response with 'exists' boolean
     * @throws VippsException
     */
    public function checkUserExists(string $loginHint): array
    {
        return $this->client->request(
            'POST',
            self::CIBA_PATH . '/v1/user-exists',
            [
                'form_params' => [
                    'loginHint' => $loginHint,
                ],
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
            ]
        );
    }

    /**
     * Initiate CIBA authentication
     *
     * Starts a Client Initiated Backchannel Authentication (CIBA) flow.
     * This is used for merchant-initiated login where the user authenticates
     * in their Vipps app without being redirected.
     *
     * @param string $loginHint User identifier (phone number or sub)
     * @param array<string, mixed> $options Additional options
     *   - scope: Space-separated list of scopes (default: 'openid')
     *   - bindingMessage: Message shown to user in app
     *   - requested_expiry: Expiration time in seconds (60-900)
     * @return array<string, mixed> Response with auth_req_id, expires_in, interval
     * @throws VippsException
     */
    public function initiateCibaAuth(string $loginHint, array $options = []): array
    {
        $data = [
            'loginHint' => $loginHint,
            'scope' => $options['scope'] ?? 'openid',
        ];

        if (isset($options['bindingMessage'])) {
            $data['bindingMessage'] = $options['bindingMessage'];
        }

        if (isset($options['requested_expiry'])) {
            $data['requested_expiry'] = $options['requested_expiry'];
        }

        return $this->client->request(
            'POST',
            self::CIBA_PATH . '/backchannel/authentication',
            [
                'form_params' => $data,
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
            ]
        );
    }

    /**
     * Poll for CIBA token
     *
     * Polls the token endpoint to check if the user has completed
     * authentication in the CIBA flow.
     *
     * @param string $authReqId Authentication request ID from initiateCibaAuth
     * @return array<string, mixed> Token response or error
     * @throws VippsException
     */
    public function pollCibaToken(string $authReqId): array
    {
        return $this->client->request(
            'POST',
            self::BASE_PATH . '/oauth2/token',
            [
                'form_params' => [
                    'grant_type' => 'urn:openid:params:grant-type:ciba',
                    'auth_req_id' => $authReqId,
                ],
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
            ]
        );
    }
}
