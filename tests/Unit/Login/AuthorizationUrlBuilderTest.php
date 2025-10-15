<?php

declare(strict_types=1);

namespace Coretrek\Vipps\Tests\Unit\Login;

use Coretrek\Vipps\Exceptions\VippsException;
use Coretrek\Vipps\Login\AuthorizationUrlBuilder;
use Coretrek\Vipps\VippsClient;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use PHPUnit\Framework\TestCase;

class AuthorizationUrlBuilderTest extends TestCase
{
    private VippsClient $client;

    protected function setUp(): void
    {
        $mockHandler = new MockHandler();
        $handlerStack = HandlerStack::create($mockHandler);
        $httpClient = new Client(['handler' => $handlerStack]);

        $this->client = new VippsClient(
            clientId: 'test-client-id',
            clientSecret: 'test-client-secret',
            subscriptionKey: 'test-subscription-key',
            merchantSerialNumber: '123456',
            testMode: true,
            options: ['http_client' => $httpClient]
        );
    }

    public function testBuildBasicUrl(): void
    {
        $builder = new AuthorizationUrlBuilder($this->client);
        $url = $builder
            ->clientId('my-client-id')
            ->redirectUri('https://example.com/callback')
            ->build();

        $this->assertStringContainsString('https://apitest.vipps.no/access-management-1.0/access/oauth2/auth', $url);
        $this->assertStringContainsString('client_id=my-client-id', $url);
        $this->assertStringContainsString('redirect_uri=https%3A%2F%2Fexample.com%2Fcallback', $url);
        $this->assertStringContainsString('scope=openid', $url);
        $this->assertStringContainsString('response_type=code', $url);
    }

    public function testBuildWithScopes(): void
    {
        $builder = new AuthorizationUrlBuilder($this->client);
        $url = $builder
            ->clientId('my-client-id')
            ->redirectUri('https://example.com/callback')
            ->scope('openid name email phoneNumber')
            ->build();

        $this->assertStringContainsString('scope=openid+name+email+phoneNumber', $url);
    }

    public function testBuildWithScopesArray(): void
    {
        $builder = new AuthorizationUrlBuilder($this->client);
        $url = $builder
            ->clientId('my-client-id')
            ->redirectUri('https://example.com/callback')
            ->scope(['openid', 'name', 'email'])
            ->build();

        $this->assertStringContainsString('scope=openid+name+email', $url);
    }

    public function testBuildWithStateAndNonce(): void
    {
        $builder = new AuthorizationUrlBuilder($this->client);
        $url = $builder
            ->clientId('my-client-id')
            ->redirectUri('https://example.com/callback')
            ->state('test-state-123')
            ->nonce('test-nonce-456')
            ->build();

        $this->assertStringContainsString('state=test-state-123', $url);
        $this->assertStringContainsString('nonce=test-nonce-456', $url);
    }

    public function testBuildWithPkce(): void
    {
        $builder = new AuthorizationUrlBuilder($this->client);
        $codeVerifier = 'test-code-verifier-1234567890-abcdefghijklmnopqrstuvwxyz';
        $url = $builder
            ->clientId('my-client-id')
            ->redirectUri('https://example.com/callback')
            ->pkce($codeVerifier, 'S256')
            ->build();

        $this->assertStringContainsString('code_challenge=', $url);
        $this->assertStringContainsString('code_challenge_method=S256', $url);
    }

    public function testBuildWithPkcePlain(): void
    {
        $builder = new AuthorizationUrlBuilder($this->client);
        $codeVerifier = 'test-code-verifier';
        $url = $builder
            ->clientId('my-client-id')
            ->redirectUri('https://example.com/callback')
            ->pkce($codeVerifier, 'plain')
            ->build();

        $this->assertStringContainsString('code_challenge=test-code-verifier', $url);
        $this->assertStringContainsString('code_challenge_method=plain', $url);
    }

    public function testBuildWithLoginHint(): void
    {
        $builder = new AuthorizationUrlBuilder($this->client);
        $url = $builder
            ->clientId('my-client-id')
            ->redirectUri('https://example.com/callback')
            ->loginHint('4712345678')
            ->build();

        $this->assertStringContainsString('login_hint=4712345678', $url);
    }

    public function testBuildWithPrompt(): void
    {
        $builder = new AuthorizationUrlBuilder($this->client);
        $url = $builder
            ->clientId('my-client-id')
            ->redirectUri('https://example.com/callback')
            ->prompt('login')
            ->build();

        $this->assertStringContainsString('prompt=login', $url);
    }

    public function testBuildWithUiLocales(): void
    {
        $builder = new AuthorizationUrlBuilder($this->client);
        $url = $builder
            ->clientId('my-client-id')
            ->redirectUri('https://example.com/callback')
            ->uiLocales('nb no en')
            ->build();

        $this->assertStringContainsString('ui_locales=nb+no+en', $url);
    }

    public function testBuildWithUiLocalesArray(): void
    {
        $builder = new AuthorizationUrlBuilder($this->client);
        $url = $builder
            ->clientId('my-client-id')
            ->redirectUri('https://example.com/callback')
            ->uiLocales(['nb', 'no', 'en'])
            ->build();

        $this->assertStringContainsString('ui_locales=nb+no+en', $url);
    }

    public function testBuildWithMaxAge(): void
    {
        $builder = new AuthorizationUrlBuilder($this->client);
        $url = $builder
            ->clientId('my-client-id')
            ->redirectUri('https://example.com/callback')
            ->maxAge(3600)
            ->build();

        $this->assertStringContainsString('max_age=3600', $url);
    }

    public function testBuildWithAcrValues(): void
    {
        $builder = new AuthorizationUrlBuilder($this->client);
        $url = $builder
            ->clientId('my-client-id')
            ->redirectUri('https://example.com/callback')
            ->acrValues('urn:vipps:acr:otp urn:vipps:acr:biometric')
            ->build();

        $this->assertStringContainsString('acr_values=', $url);
    }

    public function testBuildThrowsExceptionWithoutClientId(): void
    {
        $this->expectException(VippsException::class);
        $this->expectExceptionMessage('Client ID is required');

        $builder = new AuthorizationUrlBuilder($this->client);
        $builder
            ->redirectUri('https://example.com/callback')
            ->build();
    }

    public function testBuildThrowsExceptionWithoutRedirectUri(): void
    {
        $this->expectException(VippsException::class);
        $this->expectExceptionMessage('Redirect URI is required');

        $builder = new AuthorizationUrlBuilder($this->client);
        $builder
            ->clientId('my-client-id')
            ->build();
    }

    public function testGenerateState(): void
    {
        $state = AuthorizationUrlBuilder::generateState();

        $this->assertEquals(32, strlen($state));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $state);
    }

    public function testGenerateStateWithCustomLength(): void
    {
        $state = AuthorizationUrlBuilder::generateState(64);

        $this->assertEquals(64, strlen($state));
    }

    public function testGenerateNonce(): void
    {
        $nonce = AuthorizationUrlBuilder::generateNonce();

        $this->assertEquals(32, strlen($nonce));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $nonce);
    }

    public function testGenerateCodeVerifier(): void
    {
        $verifier = AuthorizationUrlBuilder::generateCodeVerifier();

        $this->assertEquals(64, strlen($verifier));
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]+$/', $verifier);
    }

    public function testGenerateCodeVerifierWithCustomLength(): void
    {
        $verifier = AuthorizationUrlBuilder::generateCodeVerifier(100);

        $this->assertEquals(100, strlen($verifier));
    }

    public function testGenerateCodeVerifierThrowsExceptionForShortLength(): void
    {
        $this->expectException(VippsException::class);
        $this->expectExceptionMessage('Code verifier length must be between 43 and 128 characters');

        AuthorizationUrlBuilder::generateCodeVerifier(42);
    }

    public function testGenerateCodeVerifierThrowsExceptionForLongLength(): void
    {
        $this->expectException(VippsException::class);
        $this->expectExceptionMessage('Code verifier length must be between 43 and 128 characters');

        AuthorizationUrlBuilder::generateCodeVerifier(129);
    }

    public function testCompleteFlowWithAllParameters(): void
    {
        $builder = new AuthorizationUrlBuilder($this->client);
        $url = $builder
            ->clientId('my-client-id')
            ->redirectUri('https://example.com/callback')
            ->scope(['openid', 'name', 'email', 'phoneNumber', 'address'])
            ->state('test-state')
            ->nonce('test-nonce')
            ->pkce('test-code-verifier-1234567890-abcdefghijklmnopqrstuvwxyz', 'S256')
            ->loginHint('4712345678')
            ->prompt('consent')
            ->uiLocales(['nb', 'en'])
            ->maxAge(7200)
            ->acrValues('urn:vipps:acr:biometric')
            ->build();

        $this->assertStringContainsString('client_id=my-client-id', $url);
        $this->assertStringContainsString('redirect_uri=', $url);
        $this->assertStringContainsString('scope=openid+name+email+phoneNumber+address', $url);
        $this->assertStringContainsString('state=test-state', $url);
        $this->assertStringContainsString('nonce=test-nonce', $url);
        $this->assertStringContainsString('code_challenge=', $url);
        $this->assertStringContainsString('login_hint=4712345678', $url);
        $this->assertStringContainsString('prompt=consent', $url);
        $this->assertStringContainsString('ui_locales=nb+en', $url);
        $this->assertStringContainsString('max_age=7200', $url);
        $this->assertStringContainsString('acr_values=', $url);
    }
}
