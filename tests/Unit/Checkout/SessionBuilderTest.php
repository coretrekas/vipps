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

class SessionBuilderTest extends TestCase
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

    public function testBuilderCreatesCorrectData(): void
    {
        $client = $this->createMockClient([]);
        $api = new CheckoutApi($client);

        $builder = new SessionBuilder($api, 'PAYMENT');

        $builder
            ->reference('order-123')
            ->transaction(1000, 'NOK', 'order-123', 'Test payment')
            ->merchantInfo(
                'https://example.com/callback',
                'https://example.com/return',
                'https://example.com/terms',
                'auth-token-123'
            )
            ->prefillCustomer([
                'firstName' => 'John',
                'lastName' => 'Doe',
                'email' => 'john@example.com',
            ])
            ->customerInteraction('CUSTOMER_NOT_PRESENT')
            ->elements('Full')
            ->countries(['NO', 'SE', 'DK']);

        $data = $builder->getData();

        $this->assertEquals('PAYMENT', $data['type']);
        $this->assertEquals('order-123', $data['reference']);
        $this->assertEquals(1000, $data['transaction']['amount']['value']);
        $this->assertEquals('NOK', $data['transaction']['amount']['currency']);
        $this->assertEquals('https://example.com/callback', $data['merchantInfo']['callbackUrl']);
        $this->assertEquals('John', $data['prefillCustomer']['firstName']);
        $this->assertEquals('CUSTOMER_NOT_PRESENT', $data['configuration']['customerInteraction']);
        $this->assertEquals('Full', $data['configuration']['elements']);
        $this->assertEquals(['NO', 'SE', 'DK'], $data['configuration']['countries']['supported']);
    }

    public function testBuilderSetsHeaders(): void
    {
        $client = $this->createMockClient([]);
        $api = new CheckoutApi($client);

        $builder = new SessionBuilder($api, 'PAYMENT');

        $builder
            ->idempotencyKey('idempotency-123')
            ->systemInfo('woocommerce', '5.4', 'vipps-woocommerce', '1.2.1')
            ->header('Custom-Header', 'custom-value');

        $headers = $builder->getHeaders();

        $this->assertEquals('idempotency-123', $headers['Idempotency-Key']);
        $this->assertEquals('woocommerce', $headers['Vipps-System-Name']);
        $this->assertEquals('5.4', $headers['Vipps-System-Version']);
        $this->assertEquals('vipps-woocommerce', $headers['Vipps-System-Plugin-Name']);
        $this->assertEquals('1.2.1', $headers['Vipps-System-Plugin-Version']);
        $this->assertEquals('custom-value', $headers['Custom-Header']);
    }

    public function testBuilderCreate(): void
    {
        $client = $this->createMockClient([
            new Response(200, [], json_encode(['access_token' => 'test-token'])),
            new Response(200, [], json_encode([
                'token' => 'session-token-123',
                'checkoutFrontendUrl' => 'https://checkout.vipps.no/session-token-123',
            ])),
        ]);

        $api = new CheckoutApi($client);
        $builder = new SessionBuilder($api, 'PAYMENT');

        $result = $builder
            ->reference('order-123')
            ->transaction(1000, 'NOK', 'order-123', 'Test payment')
            ->merchantInfo(
                'https://example.com/callback',
                'https://example.com/return',
                'https://example.com/terms'
            )
            ->create();

        $this->assertArrayHasKey('token', $result);
        $this->assertEquals('session-token-123', $result['token']);
    }

    public function testBuilderWithLogistics(): void
    {
        $client = $this->createMockClient([]);
        $api = new CheckoutApi($client);

        $builder = new SessionBuilder($api, 'PAYMENT');

        $builder->logistics([
            'fixedOptions' => [
                [
                    'brand' => 'POSTEN',
                    'amount' => ['value' => 300, 'currency' => 'NOK'],
                    'id' => 'posten-1',
                    'priority' => 1,
                    'isDefault' => true,
                    'description' => 'Home delivery',
                ],
            ],
        ]);

        $data = $builder->getData();

        $this->assertArrayHasKey('logistics', $data);
        $this->assertArrayHasKey('fixedOptions', $data['logistics']);
        $this->assertEquals('POSTEN', $data['logistics']['fixedOptions'][0]['brand']);
    }

    public function testBuilderWithSubscription(): void
    {
        $client = $this->createMockClient([]);
        $api = new CheckoutApi($client);

        $builder = new SessionBuilder($api, 'SUBSCRIPTION');

        $builder->subscription([
            'productName' => 'Monthly Subscription',
            'amount' => ['value' => 1000, 'currency' => 'NOK'],
            'interval' => ['unit' => 'MONTH', 'count' => 1],
            'merchantAgreementUrl' => 'https://example.com/agreement',
        ]);

        $data = $builder->getData();

        $this->assertArrayHasKey('subscription', $data);
        $this->assertEquals('Monthly Subscription', $data['subscription']['productName']);
    }
}
