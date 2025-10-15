<?php

declare(strict_types=1);

namespace Coretrek\Vipps\Tests\Unit\Recurring;

use Coretrek\Vipps\Recurring\AgreementBuilder;
use Coretrek\Vipps\Recurring\RecurringApi;
use Coretrek\Vipps\VippsClient;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class AgreementBuilderTest extends TestCase
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

    public function testBuilderWithLegacyPricing(): void
    {
        $client = $this->createMockClient([]);
        $api = new RecurringApi($client);

        $builder = new AgreementBuilder($api);

        $builder
            ->legacyPricing(2500, 'NOK')
            ->interval('MONTH', 1)
            ->product('Test Subscription', 'A test subscription')
            ->merchantUrls('https://example.com/redirect', 'https://example.com/agreement')
            ->phoneNumber('4712345678');

        $data = $builder->getData();

        $this->assertEquals('LEGACY', $data['pricing']['type']);
        $this->assertEquals(2500, $data['pricing']['amount']);
        $this->assertEquals('NOK', $data['pricing']['currency']);
        $this->assertEquals('MONTH', $data['interval']['unit']);
        $this->assertEquals(1, $data['interval']['count']);
        $this->assertEquals('Test Subscription', $data['productName']);
        $this->assertEquals('A test subscription', $data['productDescription']);
    }

    public function testBuilderWithVariablePricing(): void
    {
        $client = $this->createMockClient([]);
        $api = new RecurringApi($client);

        $builder = new AgreementBuilder($api);

        $builder->variablePricing(3000, 'NOK');

        $data = $builder->getData();

        $this->assertEquals('VARIABLE', $data['pricing']['type']);
        $this->assertEquals(3000, $data['pricing']['suggestedMaxAmount']);
    }

    public function testBuilderWithFlexiblePricing(): void
    {
        $client = $this->createMockClient([]);
        $api = new RecurringApi($client);

        $builder = new AgreementBuilder($api);

        $builder->flexiblePricing('EUR');

        $data = $builder->getData();

        $this->assertEquals('FLEXIBLE', $data['pricing']['type']);
        $this->assertEquals('EUR', $data['pricing']['currency']);
    }

    public function testBuilderWithInitialCharge(): void
    {
        $client = $this->createMockClient([]);
        $api = new RecurringApi($client);

        $builder = new AgreementBuilder($api);

        $builder->initialCharge(100, 'NOK', 'Initial charge', 'DIRECT_CAPTURE');

        $data = $builder->getData();

        $this->assertArrayHasKey('initialCharge', $data);
        $this->assertEquals(100, $data['initialCharge']['amount']);
        $this->assertEquals('NOK', $data['initialCharge']['currency']);
        $this->assertEquals('Initial charge', $data['initialCharge']['description']);
        $this->assertEquals('DIRECT_CAPTURE', $data['initialCharge']['transactionType']);
    }

    public function testBuilderWithPriceCampaign(): void
    {
        $client = $this->createMockClient([]);
        $api = new RecurringApi($client);

        $builder = new AgreementBuilder($api);

        $builder->priceCampaign(100, '2024-12-31T23:59:59Z');

        $data = $builder->getData();

        $this->assertEquals('PRICE_CAMPAIGN', $data['campaign']['type']);
        $this->assertEquals(100, $data['campaign']['price']);
        $this->assertEquals('2024-12-31T23:59:59Z', $data['campaign']['end']);
    }

    public function testBuilderWithPeriodCampaign(): void
    {
        $client = $this->createMockClient([]);
        $api = new RecurringApi($client);

        $builder = new AgreementBuilder($api);

        $builder->periodCampaign(100, 'WEEK', 4);

        $data = $builder->getData();

        $this->assertEquals('PERIOD_CAMPAIGN', $data['campaign']['type']);
        $this->assertEquals(100, $data['campaign']['price']);
        $this->assertEquals('WEEK', $data['campaign']['period']['unit']);
        $this->assertEquals(4, $data['campaign']['period']['count']);
    }

    public function testBuilderWithEventCampaign(): void
    {
        $client = $this->createMockClient([]);
        $api = new RecurringApi($client);

        $builder = new AgreementBuilder($api);

        $builder->eventCampaign(100, '2024-12-25T00:00:00Z', 'until Christmas');

        $data = $builder->getData();

        $this->assertEquals('EVENT_CAMPAIGN', $data['campaign']['type']);
        $this->assertEquals(100, $data['campaign']['price']);
        $this->assertEquals('2024-12-25T00:00:00Z', $data['campaign']['eventDate']);
        $this->assertEquals('until Christmas', $data['campaign']['eventText']);
    }

    public function testBuilderWithScope(): void
    {
        $client = $this->createMockClient([]);
        $api = new RecurringApi($client);

        $builder = new AgreementBuilder($api);

        $builder->scope('address name email birthDate phoneNumber');

        $data = $builder->getData();

        $this->assertEquals('address name email birthDate phoneNumber', $data['scope']);
    }

    public function testBuilderWithAppFlow(): void
    {
        $client = $this->createMockClient([]);
        $api = new RecurringApi($client);

        $builder = new AgreementBuilder($api);

        $builder->isApp(true);

        $data = $builder->getData();

        $this->assertTrue($data['isApp']);
    }

    public function testBuilderWithSkipLandingPage(): void
    {
        $client = $this->createMockClient([]);
        $api = new RecurringApi($client);

        $builder = new AgreementBuilder($api);

        $builder->skipLandingPage(true);

        $data = $builder->getData();

        $this->assertTrue($data['skipLandingPage']);
    }

    public function testBuilderWithExternalId(): void
    {
        $client = $this->createMockClient([]);
        $api = new RecurringApi($client);

        $builder = new AgreementBuilder($api);

        $builder->externalId('external-123');

        $data = $builder->getData();

        $this->assertEquals('external-123', $data['externalId']);
    }

    public function testBuilderCreate(): void
    {
        $client = $this->createMockClient([
            new Response(200, [], json_encode(['access_token' => 'test-token'])),
            new Response(201, [], json_encode([
                'agreementId' => 'agr_123',
                'vippsConfirmationUrl' => 'https://api.vipps.no/v2/register/U6JUjQXq8HQmmV',
            ])),
        ]);

        $api = new RecurringApi($client);
        $builder = new AgreementBuilder($api);

        $result = $builder
            ->legacyPricing(2500, 'NOK')
            ->interval('MONTH', 1)
            ->product('Test Subscription')
            ->merchantUrls('https://example.com/redirect', 'https://example.com/agreement')
            ->phoneNumber('4712345678')
            ->create();

        $this->assertArrayHasKey('agreementId', $result);
        $this->assertEquals('agr_123', $result['agreementId']);
    }
}
