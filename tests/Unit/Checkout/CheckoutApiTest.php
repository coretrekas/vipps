<?php

declare(strict_types=1);

namespace Coretrek\Vipps\Tests\Unit\Checkout;

use Coretrek\Vipps\Checkout\CheckoutApi;
use Coretrek\Vipps\Checkout\SessionBuilder;
use Coretrek\Vipps\VippsClient;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class CheckoutApiTest extends TestCase
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
            options: ['http_client' => $httpClient]
        );
    }

    public function testCreateSession(): void
    {
        $client = $this->createMockClient([
            new Response(200, [], json_encode(['access_token' => 'test-token'])),
            new Response(200, [], json_encode([
                'token' => 'session-token-123',
                'checkoutFrontendUrl' => 'https://checkout.vipps.no/session-token-123',
                'pollingUrl' => 'https://api.vipps.no/checkout/v3/session/ref-123',
            ])),
        ]);

        $checkout = new CheckoutApi($client);

        $result = $checkout->createSession([
            'type' => 'PAYMENT',
            'reference' => 'order-123',
            'transaction' => [
                'amount' => ['value' => 1000, 'currency' => 'NOK'],
                'reference' => 'order-123',
                'paymentDescription' => 'Test payment',
            ],
        ]);

        $this->assertArrayHasKey('token', $result);
        $this->assertEquals('session-token-123', $result['token']);
    }

    public function testCreatePaymentSession(): void
    {
        $client = $this->createMockClient([
            new Response(200, [], json_encode(['access_token' => 'test-token'])),
            new Response(200, [], json_encode([
                'token' => 'session-token-123',
                'checkoutFrontendUrl' => 'https://checkout.vipps.no/session-token-123',
            ])),
        ]);

        $checkout = new CheckoutApi($client);

        $result = $checkout->createPaymentSession(
            reference: 'order-123',
            amount: 1000,
            currency: 'NOK',
            options: ['paymentDescription' => 'Test payment']
        );

        $this->assertArrayHasKey('token', $result);
    }

    public function testCreateSubscriptionSession(): void
    {
        $client = $this->createMockClient([
            new Response(200, [], json_encode(['access_token' => 'test-token'])),
            new Response(200, [], json_encode([
                'token' => 'session-token-123',
                'checkoutFrontendUrl' => 'https://checkout.vipps.no/session-token-123',
            ])),
        ]);

        $checkout = new CheckoutApi($client);

        $result = $checkout->createSubscriptionSession(
            reference: 'sub-123',
            amount: 100,
            currency: 'NOK',
            subscription: [
                'productName' => 'Monthly Subscription',
                'amount' => ['value' => 1000, 'currency' => 'NOK'],
                'interval' => ['unit' => 'MONTH', 'count' => 1],
                'merchantAgreementUrl' => 'https://example.com/agreement',
            ]
        );

        $this->assertArrayHasKey('token', $result);
    }

    public function testGetSession(): void
    {
        $client = $this->createMockClient([
            new Response(200, [], json_encode(['access_token' => 'test-token'])),
            new Response(200, [], json_encode([
                'sessionId' => 'session-123',
                'sessionState' => 'PaymentInitiated',
                'paymentMethod' => 'WALLET',
            ])),
        ]);

        $checkout = new CheckoutApi($client);

        $result = $checkout->getSession('order-123');

        $this->assertArrayHasKey('sessionId', $result);
        $this->assertEquals('session-123', $result['sessionId']);
    }

    public function testUpdateSession(): void
    {
        $client = $this->createMockClient([
            new Response(200, [], json_encode(['access_token' => 'test-token'])),
            new Response(200, [], json_encode([])),
        ]);

        $checkout = new CheckoutApi($client);

        $result = $checkout->updateSession('order-123', [
            'transaction' => [
                'amount' => ['value' => 2000, 'currency' => 'NOK'],
            ],
        ]);

        $this->assertIsArray($result);
    }

    public function testExpireSession(): void
    {
        $client = $this->createMockClient([
            new Response(200, [], json_encode(['access_token' => 'test-token'])),
            new Response(200, [], json_encode([])),
        ]);

        $checkout = new CheckoutApi($client);

        $result = $checkout->expireSession('order-123');

        $this->assertIsArray($result);
    }

    public function testBuildPaymentSession(): void
    {
        $client = $this->createMockClient([]);
        $checkout = new CheckoutApi($client);

        $builder = $checkout->buildPaymentSession();

        $this->assertInstanceOf(SessionBuilder::class, $builder);
    }

    public function testBuildSubscriptionSession(): void
    {
        $client = $this->createMockClient([]);
        $checkout = new CheckoutApi($client);

        $builder = $checkout->buildSubscriptionSession();

        $this->assertInstanceOf(SessionBuilder::class, $builder);
    }
}
