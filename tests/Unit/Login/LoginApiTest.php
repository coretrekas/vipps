<?php

declare(strict_types=1);

namespace Coretrek\Vipps\Tests\Unit\Login;

use Coretrek\Vipps\Login\AuthorizationUrlBuilder;
use Coretrek\Vipps\VippsClient;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class LoginApiTest extends TestCase
{
    private VippsClient $client;
    private MockHandler $mockHandler;

    protected function setUp(): void
    {
        $this->mockHandler = new MockHandler();
        $handlerStack = HandlerStack::create($this->mockHandler);
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

    public function testGetOpenIdConfiguration(): void
    {
        $expectedConfig = [
            'issuer' => 'https://apitest.vipps.no/access-management-1.0/access/',
            'authorization_endpoint' => 'https://apitest.vipps.no/access-management-1.0/access/oauth2/auth',
            'token_endpoint' => 'https://apitest.vipps.no/access-management-1.0/access/oauth2/token',
            'jwks_uri' => 'https://apitest.vipps.no/access-management-1.0/access/.well-known/jwks.json',
            'scopes_supported' => ['openid', 'name', 'email', 'phoneNumber', 'address', 'birthDate'],
        ];

        $this->mockHandler->append(
            new Response(200, [], json_encode(['access_token' => 'test-token', 'expires_in' => 3600])),
            new Response(200, [], json_encode($expectedConfig))
        );

        $loginApi = $this->client->login();
        $config = $loginApi->getOpenIdConfiguration();

        $this->assertEquals($expectedConfig, $config);
    }

    public function testGetJwks(): void
    {
        $expectedJwks = [
            'keys' => [
                [
                    'kty' => 'RSA',
                    'use' => 'sig',
                    'kid' => '1603dfe0af8f4596',
                    'alg' => 'RS256',
                    'n' => 'test-modulus',
                    'e' => 'AQAB',
                ],
            ],
        ];

        $this->mockHandler->append(
            new Response(200, [], json_encode(['access_token' => 'test-token', 'expires_in' => 3600])),
            new Response(200, [], json_encode($expectedJwks))
        );

        $loginApi = $this->client->login();
        $jwks = $loginApi->getJwks();

        $this->assertEquals($expectedJwks, $jwks);
    }

    public function testBuildAuthorizationUrl(): void
    {
        $loginApi = $this->client->login();
        $builder = $loginApi->buildAuthorizationUrl();

        $this->assertInstanceOf(AuthorizationUrlBuilder::class, $builder);
    }

    public function testExchangeCodeForTokens(): void
    {
        $expectedTokens = [
            'access_token' => 'test-access-token',
            'id_token' => 'test-id-token',
            'token_type' => 'bearer',
            'expires_in' => 3600,
            'scope' => 'openid name email',
        ];

        $this->mockHandler->append(
            new Response(200, [], json_encode(['access_token' => 'test-token', 'expires_in' => 3600])),
            new Response(200, [], json_encode($expectedTokens))
        );

        $loginApi = $this->client->login();
        $tokens = $loginApi->exchangeCodeForTokens(
            code: 'test-auth-code',
            redirectUri: 'https://example.com/callback'
        );

        $this->assertEquals($expectedTokens, $tokens);
    }

    public function testExchangeCodeForTokensWithPkce(): void
    {
        $expectedTokens = [
            'access_token' => 'test-access-token',
            'id_token' => 'test-id-token',
            'token_type' => 'bearer',
            'expires_in' => 3600,
        ];

        $this->mockHandler->append(
            new Response(200, [], json_encode(['access_token' => 'test-token', 'expires_in' => 3600])),
            new Response(200, [], json_encode($expectedTokens))
        );

        $loginApi = $this->client->login();
        $tokens = $loginApi->exchangeCodeForTokens(
            code: 'test-auth-code',
            redirectUri: 'https://example.com/callback',
            options: ['code_verifier' => 'test-code-verifier']
        );

        $this->assertEquals($expectedTokens, $tokens);
    }

    public function testGetUserInfo(): void
    {
        $expectedUserInfo = [
            'sub' => 'c06c4afe-d9e1-4c5d-939a-177d752a0944',
            'name' => 'Ada Lovelace',
            'given_name' => 'Ada',
            'family_name' => 'Lovelace',
            'email' => 'ada@example.com',
            'email_verified' => true,
            'phone_number' => '4712345678',
            'phone_number_verified' => true,
            'address' => [
                'street_address' => 'Robert Levins gate 5',
                'postal_code' => '0154',
                'region' => 'Oslo',
                'country' => 'NO',
            ],
        ];

        $this->mockHandler->append(
            new Response(200, [], json_encode($expectedUserInfo))
        );

        $loginApi = $this->client->login();
        $userInfo = $loginApi->getUserInfo('test-access-token');

        $this->assertEquals($expectedUserInfo, $userInfo);
    }

    public function testCheckUserExists(): void
    {
        $expectedResponse = ['exists' => true];

        $this->mockHandler->append(
            new Response(200, [], json_encode(['access_token' => 'test-token', 'expires_in' => 3600])),
            new Response(200, [], json_encode($expectedResponse))
        );

        $loginApi = $this->client->login();
        $result = $loginApi->checkUserExists('4712345678');

        $this->assertEquals($expectedResponse, $result);
        $this->assertTrue($result['exists']);
    }

    public function testInitiateCibaAuth(): void
    {
        $expectedResponse = [
            'auth_req_id' => 'test-auth-req-id',
            'expires_in' => 300,
            'interval' => 5,
        ];

        $this->mockHandler->append(
            new Response(200, [], json_encode(['access_token' => 'test-token', 'expires_in' => 3600])),
            new Response(200, [], json_encode($expectedResponse))
        );

        $loginApi = $this->client->login();
        $result = $loginApi->initiateCibaAuth(
            loginHint: '4712345678',
            options: [
                'scope' => 'openid name email',
                'bindingMessage' => 'Login to Example App',
                'requested_expiry' => 300,
            ]
        );

        $this->assertEquals($expectedResponse, $result);
        $this->assertEquals('test-auth-req-id', $result['auth_req_id']);
    }

    public function testPollCibaToken(): void
    {
        $expectedTokens = [
            'access_token' => 'test-access-token',
            'id_token' => 'test-id-token',
            'token_type' => 'bearer',
            'expires_in' => 3600,
        ];

        $this->mockHandler->append(
            new Response(200, [], json_encode(['access_token' => 'test-token', 'expires_in' => 3600])),
            new Response(200, [], json_encode($expectedTokens))
        );

        $loginApi = $this->client->login();
        $tokens = $loginApi->pollCibaToken('test-auth-req-id');

        $this->assertEquals($expectedTokens, $tokens);
    }
}
