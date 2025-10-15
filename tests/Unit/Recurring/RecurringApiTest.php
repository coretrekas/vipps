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

class RecurringApiTest extends TestCase
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

    public function testListAgreements(): void
    {
        $client = $this->createMockClient([
            new Response(200, [], json_encode(['access_token' => 'test-token'])),
            new Response(200, [], json_encode([
                'agreements' => [
                    [
                        'id' => 'agr_123',
                        'status' => 'ACTIVE',
                        'productName' => 'Test Subscription',
                    ],
                ],
            ])),
        ]);

        $recurring = new RecurringApi($client);

        $result = $recurring->listAgreements(['status' => 'ACTIVE']);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('agreements', $result);
        $this->assertCount(1, $result['agreements']);
        $this->assertEquals('agr_123', $result['agreements'][0]['id']);
    }

    public function testCreateAgreement(): void
    {
        $client = $this->createMockClient([
            new Response(200, [], json_encode(['access_token' => 'test-token'])),
            new Response(201, [], json_encode([
                'agreementId' => 'agr_123',
                'vippsConfirmationUrl' => 'https://api.vipps.no/v2/register/U6JUjQXq8HQmmV',
            ])),
        ]);

        $recurring = new RecurringApi($client);

        $result = $recurring->createAgreement([
            'pricing' => [
                'type' => 'LEGACY',
                'amount' => 2500,
                'currency' => 'NOK',
            ],
            'interval' => [
                'unit' => 'MONTH',
                'count' => 1,
            ],
            'merchantRedirectUrl' => 'https://example.com/redirect',
            'merchantAgreementUrl' => 'https://example.com/agreement',
            'phoneNumber' => '4712345678',
            'productName' => 'Test Subscription',
        ]);

        $this->assertArrayHasKey('agreementId', $result);
        $this->assertEquals('agr_123', $result['agreementId']);
    }

    public function testGetAgreement(): void
    {
        $client = $this->createMockClient([
            new Response(200, [], json_encode(['access_token' => 'test-token'])),
            new Response(200, [], json_encode([
                'id' => 'agr_123',
                'status' => 'ACTIVE',
                'productName' => 'Test Subscription',
            ])),
        ]);

        $recurring = new RecurringApi($client);

        $result = $recurring->getAgreement('agr_123');

        $this->assertEquals('agr_123', $result['id']);
        $this->assertEquals('ACTIVE', $result['status']);
    }

    public function testUpdateAgreement(): void
    {
        $client = $this->createMockClient([
            new Response(200, [], json_encode(['access_token' => 'test-token'])),
            new Response(204, []),
        ]);

        $recurring = new RecurringApi($client);

        $result = $recurring->updateAgreement('agr_123', [
            'status' => 'STOPPED',
        ]);

        $this->assertIsArray($result);
    }

    public function testListCharges(): void
    {
        $client = $this->createMockClient([
            new Response(200, [], json_encode(['access_token' => 'test-token'])),
            new Response(200, [], json_encode([
                'charges' => [
                    [
                        'id' => 'chr_123',
                        'status' => 'CHARGED',
                        'amount' => 2500,
                    ],
                ],
            ])),
        ]);

        $recurring = new RecurringApi($client);

        $result = $recurring->listCharges('agr_123');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('charges', $result);
        $this->assertCount(1, $result['charges']);
        $this->assertEquals('chr_123', $result['charges'][0]['id']);
    }

    public function testCreateCharge(): void
    {
        $client = $this->createMockClient([
            new Response(200, [], json_encode(['access_token' => 'test-token'])),
            new Response(201, [], json_encode([
                'chargeId' => 'chr_123',
            ])),
        ]);

        $recurring = new RecurringApi($client);

        $result = $recurring->createCharge('agr_123', [
            'amount' => 2500,
            'transactionType' => 'DIRECT_CAPTURE',
            'description' => 'Monthly charge',
            'due' => '2024-01-01',
            'retryDays' => 5,
            'type' => 'RECURRING',
        ]);

        $this->assertArrayHasKey('chargeId', $result);
        $this->assertEquals('chr_123', $result['chargeId']);
    }

    public function testGetCharge(): void
    {
        $client = $this->createMockClient([
            new Response(200, [], json_encode(['access_token' => 'test-token'])),
            new Response(200, [], json_encode([
                'id' => 'chr_123',
                'status' => 'CHARGED',
                'amount' => 2500,
            ])),
        ]);

        $recurring = new RecurringApi($client);

        $result = $recurring->getCharge('agr_123', 'chr_123');

        $this->assertEquals('chr_123', $result['id']);
        $this->assertEquals('CHARGED', $result['status']);
    }

    public function testCancelCharge(): void
    {
        $client = $this->createMockClient([
            new Response(200, [], json_encode(['access_token' => 'test-token'])),
            new Response(204, []),
        ]);

        $recurring = new RecurringApi($client);

        $result = $recurring->cancelCharge('agr_123', 'chr_123');

        $this->assertIsArray($result);
    }

    public function testCaptureCharge(): void
    {
        $client = $this->createMockClient([
            new Response(200, [], json_encode(['access_token' => 'test-token'])),
            new Response(204, []),
        ]);

        $recurring = new RecurringApi($client);

        $result = $recurring->captureCharge('agr_123', 'chr_123', [
            'amount' => 2500,
        ]);

        $this->assertIsArray($result);
    }

    public function testRefundCharge(): void
    {
        $client = $this->createMockClient([
            new Response(200, [], json_encode(['access_token' => 'test-token'])),
            new Response(204, []),
        ]);

        $recurring = new RecurringApi($client);

        $result = $recurring->refundCharge('agr_123', 'chr_123', [
            'amount' => 2500,
            'description' => 'Refund',
        ]);

        $this->assertIsArray($result);
    }

    public function testBuildAgreement(): void
    {
        $client = $this->createMockClient([]);
        $recurring = new RecurringApi($client);

        $builder = $recurring->buildAgreement();

        $this->assertInstanceOf(AgreementBuilder::class, $builder);
    }
}
