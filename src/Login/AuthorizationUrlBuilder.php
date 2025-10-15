<?php

declare(strict_types=1);

namespace Coretrek\Vipps\Login;

use Coretrek\Vipps\Exceptions\VippsException;
use Coretrek\Vipps\VippsClient;

/**
 * Authorization URL Builder
 *
 * Fluent builder for constructing OAuth 2.0 authorization URLs
 * for the Vipps Login flow.
 */
class AuthorizationUrlBuilder
{
    private string $clientId;
    private string $redirectUri;
    private string $scope = 'openid';
    private string $responseType = 'code';
    private ?string $state = null;
    private ?string $nonce = null;
    private ?string $codeChallenge = null;
    private ?string $codeChallengeMethod = null;
    private ?string $loginHint = null;
    private ?string $prompt = null;
    private ?string $uiLocales = null;
    private ?int $maxAge = null;
    private ?string $acrValues = null;

    public function __construct(
        private readonly VippsClient $client
    ) {
    }

    /**
     * Set client ID
     *
     * @param string $clientId OAuth client ID
     * @return self
     */
    public function clientId(string $clientId): self
    {
        $this->clientId = $clientId;

        return $this;
    }

    /**
     * Set redirect URI
     *
     * The URI where the user will be redirected after authentication.
     * Must be registered in the Vipps portal.
     *
     * @param string $redirectUri Redirect URI
     * @return self
     */
    public function redirectUri(string $redirectUri): self
    {
        $this->redirectUri = $redirectUri;

        return $this;
    }

    /**
     * Set OAuth scopes
     *
     * Space-separated list of scopes. Common scopes:
     * - openid: Required for OpenID Connect
     * - name: User's full name
     * - email: User's email address
     * - phoneNumber: User's phone number
     * - address: User's address
     * - birthDate: User's birth date
     * - nin: National identity number
     *
     * @param string|array<string> $scopes Scopes as string or array
     * @return self
     */
    public function scope(string|array $scopes): self
    {
        $this->scope = is_array($scopes) ? implode(' ', $scopes) : $scopes;

        return $this;
    }

    /**
     * Set state parameter
     *
     * Opaque value used to maintain state between request and callback.
     * Recommended for CSRF protection.
     *
     * @param string $state State value
     * @return self
     */
    public function state(string $state): self
    {
        $this->state = $state;

        return $this;
    }

    /**
     * Set nonce parameter
     *
     * String value used to associate a client session with an ID token.
     * Recommended for replay attack protection.
     *
     * @param string $nonce Nonce value
     * @return self
     */
    public function nonce(string $nonce): self
    {
        $this->nonce = $nonce;

        return $this;
    }

    /**
     * Enable PKCE (Proof Key for Code Exchange)
     *
     * Generates code challenge for PKCE flow. The code verifier
     * must be stored and used when exchanging the code for tokens.
     *
     * @param string $codeVerifier Code verifier (43-128 characters)
     * @param string $method Challenge method ('S256' or 'plain')
     * @return self
     */
    public function pkce(string $codeVerifier, string $method = 'S256'): self
    {
        $this->codeChallengeMethod = $method;

        if ($method === 'S256') {
            $this->codeChallenge = rtrim(
                strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'),
                '='
            );
        } else {
            $this->codeChallenge = $codeVerifier;
        }

        return $this;
    }

    /**
     * Set login hint
     *
     * Hint to the authorization server about the user's identity.
     * Can be phone number or sub (user ID).
     *
     * @param string $loginHint Login hint
     * @return self
     */
    public function loginHint(string $loginHint): self
    {
        $this->loginHint = $loginHint;

        return $this;
    }

    /**
     * Set prompt parameter
     *
     * Controls the authentication UI behavior:
     * - login: Force re-authentication
     * - consent: Force consent screen
     * - none: No UI, fail if interaction required
     *
     * @param string $prompt Prompt value
     * @return self
     */
    public function prompt(string $prompt): self
    {
        $this->prompt = $prompt;

        return $this;
    }

    /**
     * Set UI locales
     *
     * Preferred languages for the UI (space-separated list).
     * Example: 'nb no en'
     *
     * @param string|array<string> $locales Locales as string or array
     * @return self
     */
    public function uiLocales(string|array $locales): self
    {
        $this->uiLocales = is_array($locales) ? implode(' ', $locales) : $locales;

        return $this;
    }

    /**
     * Set maximum authentication age
     *
     * Maximum time in seconds since the user last authenticated.
     * If exceeded, user must re-authenticate.
     *
     * @param int $maxAge Maximum age in seconds
     * @return self
     */
    public function maxAge(int $maxAge): self
    {
        $this->maxAge = $maxAge;

        return $this;
    }

    /**
     * Set ACR values
     *
     * Requested Authentication Context Class Reference values.
     * Space-separated list of ACR values in order of preference.
     *
     * @param string|array<string> $acrValues ACR values as string or array
     * @return self
     */
    public function acrValues(string|array $acrValues): self
    {
        $this->acrValues = is_array($acrValues) ? implode(' ', $acrValues) : $acrValues;

        return $this;
    }

    /**
     * Build the authorization URL
     *
     * @return string Complete authorization URL
     * @throws VippsException If required parameters are missing
     */
    public function build(): string
    {
        if (empty($this->clientId)) {
            throw new VippsException('Client ID is required');
        }

        if (empty($this->redirectUri)) {
            throw new VippsException('Redirect URI is required');
        }

        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'scope' => $this->scope,
            'response_type' => $this->responseType,
        ];

        if ($this->state !== null) {
            $params['state'] = $this->state;
        }

        if ($this->nonce !== null) {
            $params['nonce'] = $this->nonce;
        }

        if ($this->codeChallenge !== null) {
            $params['code_challenge'] = $this->codeChallenge;
            $params['code_challenge_method'] = $this->codeChallengeMethod;
        }

        if ($this->loginHint !== null) {
            $params['login_hint'] = $this->loginHint;
        }

        if ($this->prompt !== null) {
            $params['prompt'] = $this->prompt;
        }

        if ($this->uiLocales !== null) {
            $params['ui_locales'] = $this->uiLocales;
        }

        if ($this->maxAge !== null) {
            $params['max_age'] = (string) $this->maxAge;
        }

        if ($this->acrValues !== null) {
            $params['acr_values'] = $this->acrValues;
        }

        $baseUrl = $this->client->getBaseUrl();
        $authPath = '/access-management-1.0/access/oauth2/auth';

        return $baseUrl . $authPath . '?' . http_build_query($params);
    }

    /**
     * Generate a random state value
     *
     * @param int $length Length of the state value (default: 32)
     * @return string Random state value
     */
    public static function generateState(int $length = 32): string
    {
        return bin2hex(random_bytes($length / 2));
    }

    /**
     * Generate a random nonce value
     *
     * @param int $length Length of the nonce value (default: 32)
     * @return string Random nonce value
     */
    public static function generateNonce(int $length = 32): string
    {
        return bin2hex(random_bytes($length / 2));
    }

    /**
     * Generate a PKCE code verifier
     *
     * @param int $length Length of the verifier (43-128 characters)
     * @return string Random code verifier
     */
    public static function generateCodeVerifier(int $length = 64): string
    {
        if ($length < 43 || $length > 128) {
            throw new VippsException('Code verifier length must be between 43 and 128 characters');
        }

        $bytes = random_bytes((int) ceil($length * 3 / 4));

        return substr(
            rtrim(strtr(base64_encode($bytes), '+/', '-_'), '='),
            0,
            $length
        );
    }
}
