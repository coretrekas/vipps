<?php

declare(strict_types=1);

namespace Coretrek\Vipps\Tests\Unit;

use Coretrek\Vipps\Checkout\CheckoutApi;
use Coretrek\Vipps\Exceptions\VippsException;
use Coretrek\Vipps\Recurring\RecurringApi;
use Coretrek\Vipps\VippsClient;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class VippsClientTest extends TestCase
{
    private function createMockClient(array $responses): VippsClient
    {
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);
        $httpClient = new Client(['handler' => $handlerStack]);

        return new VippsClient(
            clientId: 'test-client-id',
            clientSecret: 'test-client-secret',
            subscriptionKey: 'test-subscription-key',
            merchantSerialNumber: '123456',
            testMode: true,
            options: ['http_client' => $httpClient, 'logger' => new NullLogger()]
        );
    }

    public function testConstructor(): void
    {
        $client = new VippsClient(
            clientId: 'test-client-id',
            clientSecret: 'test-client-secret',
            subscriptionKey: 'test-subscription-key',
            merchantSerialNumber: '123456',
            testMode: true
        );

        $this->assertInstanceOf(VippsClient::class, $client);
        $this->assertTrue($client->isTestMode());
        $this->assertEquals('123456', $client->getMerchantSerialNumber());
        $this->assertEquals('test-subscription-key', $client->getSubscriptionKey());
    }

    public function testGetBaseUrlInTestMode(): void
    {
        $client = new VippsClient(
            clientId: 'test-client-id',
            clientSecret: 'test-client-secret',
            subscriptionKey: 'test-subscription-key',
            merchantSerialNumber: '123456',
            testMode: true
        );

        $this->assertEquals('https://apitest.vipps.no', $client->getBaseUrl());
    }

    public function testGetBaseUrlInProductionMode(): void
    {
        $client = new VippsClient(
            clientId: 'test-client-id',
            clientSecret: 'test-client-secret',
            subscriptionKey: 'test-subscription-key',
            merchantSerialNumber: '123456',
            testMode: false
        );

        $this->assertEquals('https://api.vipps.no', $client->getBaseUrl());
    }

    public function testCheckoutApiInstance(): void
    {
        $client = new VippsClient(
            clientId: 'test-client-id',
            clientSecret: 'test-client-secret',
            subscriptionKey: 'test-subscription-key',
            merchantSerialNumber: '123456'
        );

        $checkout = $client->checkout();
        $this->assertInstanceOf(CheckoutApi::class, $checkout);
    }

    public function testRecurringApiInstance(): void
    {
        $client = new VippsClient(
            clientId: 'test-client-id',
            clientSecret: 'test-client-secret',
            subscriptionKey: 'test-subscription-key',
            merchantSerialNumber: '123456'
        );

        $recurring = $client->recurring();
        $this->assertInstanceOf(RecurringApi::class, $recurring);
    }

    public function testGetAccessToken(): void
    {
        $client = $this->createMockClient([
            new Response(200, [], json_encode(['access_token' => 'test-token-123'])),
        ]);

        $token = $client->getAccessToken();
        $this->assertEquals('test-token-123', $token);
    }

    public function testGetAccessTokenCaching(): void
    {
        $client = $this->createMockClient([
            new Response(200, [], json_encode(['access_token' => 'test-token-123'])),
        ]);

        $token1 = $client->getAccessToken();
        $token2 = $client->getAccessToken();

        $this->assertEquals($token1, $token2);
    }

    public function testGetAccessTokenThrowsExceptionOnFailure(): void
    {
        $this->expectException(VippsException::class);
        $this->expectExceptionMessage('Failed to fetch access token');

        $client = $this->createMockClient([
            new RequestException(
                'Error',
                new Request('POST', '/accesstoken/get'),
                new Response(401, [], json_encode(['error' => 'Unauthorized']))
            ),
        ]);

        $client->getAccessToken();
    }

    public function testRequest(): void
    {
        $client = $this->createMockClient([
            new Response(200, [], json_encode(['access_token' => 'test-token'])),
            new Response(200, [], json_encode(['data' => 'test-data'])),
        ]);

        $result = $client->request('GET', '/test/endpoint');

        $this->assertEquals(['data' => 'test-data'], $result);
    }

    public function testRequestWithEmptyResponse(): void
    {
        $client = $this->createMockClient([
            new Response(200, [], json_encode(['access_token' => 'test-token'])),
            new Response(204, []),
        ]);

        $result = $client->request('DELETE', '/test/endpoint');

        $this->assertEquals([], $result);
    }

    public function testRequestThrowsExceptionOnError(): void
    {
        $this->expectException(VippsException::class);

        $client = $this->createMockClient([
            new Response(200, [], json_encode(['access_token' => 'test-token'])),
            new RequestException(
                'Error',
                new Request('GET', '/test/endpoint'),
                new Response(404, [], json_encode(['title' => 'Not Found']))
            ),
        ]);

        $client->request('GET', '/test/endpoint');
    }
}
