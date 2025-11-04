<?php

declare(strict_types=1);

namespace Tests\Unit\EPayment;

use Coretrek\Vipps\EPayment\EPaymentApi;
use Coretrek\Vipps\EPayment\PaymentBuilder;
use Coretrek\Vipps\VippsClient;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class PaymentBuilderTest extends TestCase
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
        $api = new EPaymentApi($client);

        $builder = new PaymentBuilder($api);

        $builder
            ->amount(10000, 'NOK')
            ->reference('order-123')
            ->userFlow('WEB_REDIRECT')
            ->returnUrl('https://example.com/return')
            ->paymentDescription('Test payment')
            ->paymentMethod('WALLET')
            ->customerPhoneNumber('4712345678')
            ->customerInteraction('CUSTOMER_NOT_PRESENT')
            ->minimumUserAge(18);

        $data = $builder->getData();

        $this->assertEquals(10000, $data['amount']['value']);
        $this->assertEquals('NOK', $data['amount']['currency']);
        $this->assertEquals('order-123', $data['reference']);
        $this->assertEquals('WEB_REDIRECT', $data['userFlow']);
        $this->assertEquals('https://example.com/return', $data['returnUrl']);
        $this->assertEquals('Test payment', $data['paymentDescription']);
        $this->assertEquals('WALLET', $data['paymentMethod']['type']);
        $this->assertEquals('4712345678', $data['customer']['phoneNumber']);
        $this->assertEquals('CUSTOMER_NOT_PRESENT', $data['customerInteraction']);
        $this->assertEquals(18, $data['minimumUserAge']);
    }

    public function testBuilderWithPersonalQr(): void
    {
        $client = $this->createMockClient([]);
        $api = new EPaymentApi($client);

        $builder = new PaymentBuilder($api);
        $builder->customerPersonalQr('https://qr.vipps.no/p/4712345678');

        $data = $builder->getData();

        $this->assertEquals('https://qr.vipps.no/p/4712345678', $data['customer']['personalQr']);
    }

    public function testBuilderWithCustomerToken(): void
    {
        $client = $this->createMockClient([]);
        $api = new EPaymentApi($client);

        $builder = new PaymentBuilder($api);
        $builder->customerToken('token-123');

        $data = $builder->getData();

        $this->assertEquals('token-123', $data['customer']['customerToken']);
    }

    public function testBuilderWithQrFormat(): void
    {
        $client = $this->createMockClient([]);
        $api = new EPaymentApi($client);

        $builder = new PaymentBuilder($api);
        $builder->qrFormat('IMAGE/PNG', 512);

        $data = $builder->getData();

        $this->assertEquals('IMAGE/PNG', $data['qrFormat']['format']);
        $this->assertEquals(512, $data['qrFormat']['size']);
    }

    public function testBuilderWithReceipt(): void
    {
        $client = $this->createMockClient([]);
        $api = new EPaymentApi($client);

        $orderLines = [
            [
                'name' => 'Product 1',
                'id' => '123',
                'totalAmount' => 10000,
                'totalAmountExcludingTax' => 8000,
                'totalTaxAmount' => 2000,
            ],
        ];

        $bottomLine = [
            'currency' => 'NOK',
        ];

        $builder = new PaymentBuilder($api);
        $builder->receipt($orderLines, $bottomLine);

        $data = $builder->getData();

        $this->assertArrayHasKey('receipt', $data);
        $this->assertEquals($orderLines, $data['receipt']['orderLines']);
        $this->assertEquals($bottomLine, $data['receipt']['bottomLine']);
    }

    public function testBuilderWithMetadata(): void
    {
        $client = $this->createMockClient([]);
        $api = new EPaymentApi($client);

        $metadata = [
            'orderId' => '12345',
            'customerId' => '67890',
        ];

        $builder = new PaymentBuilder($api);
        $builder->metadata($metadata);

        $data = $builder->getData();

        $this->assertEquals($metadata, $data['metadata']);
    }

    public function testBuilderWithProfileScope(): void
    {
        $client = $this->createMockClient([]);
        $api = new EPaymentApi($client);

        $builder = new PaymentBuilder($api);
        $builder->profileScope('name email phoneNumber');

        $data = $builder->getData();

        $this->assertEquals('name email phoneNumber', $data['profile']['scope']);
    }

    public function testBuilderWithAirlineData(): void
    {
        $client = $this->createMockClient([]);
        $api = new EPaymentApi($client);

        $builder = new PaymentBuilder($api);
        $builder->airlineData(
            agencyInvoiceNumber: '123456',
            airlineCode: '074',
            airlineDesignatorCode: 'KL',
            passengerName: 'DOE/JOHN MR',
            ticketNumber: '123-1234567890'
        );

        $data = $builder->getData();

        $this->assertArrayHasKey('industryData', $data);
        $this->assertArrayHasKey('airlineData', $data['industryData']);
        $this->assertEquals('123456', $data['industryData']['airlineData']['agencyInvoiceNumber']);
        $this->assertEquals('074', $data['industryData']['airlineData']['airlineCode']);
        $this->assertEquals('KL', $data['industryData']['airlineData']['airlineDesignatorCode']);
        $this->assertEquals('DOE/JOHN MR', $data['industryData']['airlineData']['passengerName']);
        $this->assertEquals('123-1234567890', $data['industryData']['airlineData']['ticketNumber']);
    }

    public function testBuilderWithDynamicShipping(): void
    {
        $client = $this->createMockClient([]);
        $api = new EPaymentApi($client);

        $builder = new PaymentBuilder($api);
        $builder->dynamicShipping('https://example.com/shipping', 'auth-token-123');

        $data = $builder->getData();

        $this->assertArrayHasKey('shipping', $data);
        $this->assertArrayHasKey('dynamicOptions', $data['shipping']);
        $this->assertEquals('https://example.com/shipping', $data['shipping']['dynamicOptions']['callbackUrl']);
        $this->assertEquals('auth-token-123', $data['shipping']['dynamicOptions']['callbackAuthorizationToken']);
    }

    public function testBuilderWithFixedShipping(): void
    {
        $client = $this->createMockClient([]);
        $api = new EPaymentApi($client);

        $shippingGroups = [
            [
                'type' => 'HOME_DELIVERY',
                'brand' => 'POSTEN',
                'options' => [
                    [
                        'id' => 'posten-1',
                        'name' => 'Standard delivery',
                        'amount' => ['value' => 9900, 'currency' => 'NOK'],
                    ],
                ],
            ],
        ];

        $builder = new PaymentBuilder($api);
        $builder->fixedShipping($shippingGroups);

        $data = $builder->getData();

        $this->assertArrayHasKey('shipping', $data);
        $this->assertArrayHasKey('fixedOptions', $data['shipping']);
        $this->assertEquals($shippingGroups, $data['shipping']['fixedOptions']);
    }

    public function testBuilderSetsHeaders(): void
    {
        $client = $this->createMockClient([]);
        $api = new EPaymentApi($client);

        $builder = new PaymentBuilder($api);

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
            new Response(201, [], json_encode([
                'reference' => 'order-123',
                'redirectUrl' => 'https://landing.vipps.no?token=abc123',
            ])),
        ]);

        $api = new EPaymentApi($client);
        $builder = new PaymentBuilder($api);

        $result = $builder
            ->amount(10000, 'NOK')
            ->reference('order-123')
            ->userFlow('WEB_REDIRECT')
            ->returnUrl('https://example.com/return')
            ->paymentDescription('Test payment')
            ->paymentMethod('WALLET')
            ->create();

        $this->assertArrayHasKey('reference', $result);
        $this->assertEquals('order-123', $result['reference']);
    }
}
