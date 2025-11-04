<?php

declare(strict_types=1);

namespace Tests\Unit\EPayment;

use Coretrek\Vipps\EPayment\EPaymentApi;
use Coretrek\Vipps\VippsClient;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class EPaymentApiTest extends TestCase
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

    public function testCreatePayment(): void
    {
        $client = $this->createMockClient([
            new Response(200, [], json_encode(['access_token' => 'test-token'])),
            new Response(201, [], json_encode([
                'reference' => 'order-123',
                'redirectUrl' => 'https://landing.vipps.no?token=abc123',
            ])),
        ]);

        $epayment = new EPaymentApi($client);

        $result = $epayment->createPayment([
            'amount' => [
                'value' => 10000,
                'currency' => 'NOK',
            ],
            'paymentMethod' => [
                'type' => 'WALLET',
            ],
            'reference' => 'order-123',
            'userFlow' => 'WEB_REDIRECT',
            'returnUrl' => 'https://example.com/return',
            'paymentDescription' => 'Test payment',
        ]);

        $this->assertArrayHasKey('reference', $result);
        $this->assertEquals('order-123', $result['reference']);
        $this->assertArrayHasKey('redirectUrl', $result);
    }

    public function testGetPayment(): void
    {
        $client = $this->createMockClient([
            new Response(200, [], json_encode(['access_token' => 'test-token'])),
            new Response(200, [], json_encode([
                'reference' => 'order-123',
                'state' => 'AUTHORIZED',
                'amount' => [
                    'value' => 10000,
                    'currency' => 'NOK',
                ],
                'aggregate' => [
                    'authorizedAmount' => ['value' => 10000, 'currency' => 'NOK'],
                    'cancelledAmount' => ['value' => 0, 'currency' => 'NOK'],
                    'capturedAmount' => ['value' => 0, 'currency' => 'NOK'],
                    'refundedAmount' => ['value' => 0, 'currency' => 'NOK'],
                ],
            ])),
        ]);

        $epayment = new EPaymentApi($client);
        $result = $epayment->getPayment('order-123');

        $this->assertArrayHasKey('reference', $result);
        $this->assertEquals('order-123', $result['reference']);
        $this->assertEquals('AUTHORIZED', $result['state']);
    }

    public function testGetPaymentEventLog(): void
    {
        $client = $this->createMockClient([
            new Response(200, [], json_encode(['access_token' => 'test-token'])),
            new Response(200, [], json_encode([
                [
                    'name' => 'CREATED',
                    'amount' => ['value' => 10000, 'currency' => 'NOK'],
                    'timestamp' => '2024-01-01T12:00:00Z',
                    'success' => true,
                ],
                [
                    'name' => 'AUTHORIZED',
                    'amount' => ['value' => 10000, 'currency' => 'NOK'],
                    'timestamp' => '2024-01-01T12:05:00Z',
                    'success' => true,
                ],
            ])),
        ]);

        $epayment = new EPaymentApi($client);
        $result = $epayment->getPaymentEventLog('order-123');

        $this->assertIsArray($result);
        $this->assertCount(2, $result);

        // PHPStan doesn't know this is a list array, but we verify it with assertions
        /** @var array<int, array<string, mixed>> $result */
        $this->assertEquals('CREATED', $result[0]['name']);
        $this->assertEquals('AUTHORIZED', $result[1]['name']);
    }

    public function testCancelPayment(): void
    {
        $client = $this->createMockClient([
            new Response(200, [], json_encode(['access_token' => 'test-token'])),
            new Response(200, [], json_encode([
                'reference' => 'order-123',
                'state' => 'TERMINATED',
                'amount' => ['value' => 10000, 'currency' => 'NOK'],
                'aggregate' => [
                    'authorizedAmount' => ['value' => 10000, 'currency' => 'NOK'],
                    'cancelledAmount' => ['value' => 10000, 'currency' => 'NOK'],
                    'capturedAmount' => ['value' => 0, 'currency' => 'NOK'],
                    'refundedAmount' => ['value' => 0, 'currency' => 'NOK'],
                ],
            ])),
        ]);

        $epayment = new EPaymentApi($client);
        $result = $epayment->cancelPayment('order-123');

        $this->assertArrayHasKey('reference', $result);
        $this->assertEquals('TERMINATED', $result['state']);
    }

    public function testCapturePayment(): void
    {
        $client = $this->createMockClient([
            new Response(200, [], json_encode(['access_token' => 'test-token'])),
            new Response(200, [], json_encode([
                'reference' => 'order-123',
                'state' => 'AUTHORIZED',
                'amount' => ['value' => 10000, 'currency' => 'NOK'],
                'aggregate' => [
                    'authorizedAmount' => ['value' => 10000, 'currency' => 'NOK'],
                    'cancelledAmount' => ['value' => 0, 'currency' => 'NOK'],
                    'capturedAmount' => ['value' => 10000, 'currency' => 'NOK'],
                    'refundedAmount' => ['value' => 0, 'currency' => 'NOK'],
                ],
            ])),
        ]);

        $epayment = new EPaymentApi($client);
        $result = $epayment->capturePayment('order-123', [
            'modificationAmount' => [
                'value' => 10000,
                'currency' => 'NOK',
            ],
        ]);

        $this->assertArrayHasKey('reference', $result);
        $this->assertEquals(10000, $result['aggregate']['capturedAmount']['value']);
    }

    public function testRefundPayment(): void
    {
        $client = $this->createMockClient([
            new Response(200, [], json_encode(['access_token' => 'test-token'])),
            new Response(200, [], json_encode([
                'reference' => 'order-123',
                'state' => 'AUTHORIZED',
                'amount' => ['value' => 10000, 'currency' => 'NOK'],
                'aggregate' => [
                    'authorizedAmount' => ['value' => 10000, 'currency' => 'NOK'],
                    'cancelledAmount' => ['value' => 0, 'currency' => 'NOK'],
                    'capturedAmount' => ['value' => 10000, 'currency' => 'NOK'],
                    'refundedAmount' => ['value' => 5000, 'currency' => 'NOK'],
                ],
            ])),
        ]);

        $epayment = new EPaymentApi($client);
        $result = $epayment->refundPayment('order-123', [
            'modificationAmount' => [
                'value' => 5000,
                'currency' => 'NOK',
            ],
        ]);

        $this->assertArrayHasKey('reference', $result);
        $this->assertEquals(5000, $result['aggregate']['refundedAmount']['value']);
    }

    public function testForceApprove(): void
    {
        $client = $this->createMockClient([
            new Response(200, [], json_encode(['access_token' => 'test-token'])),
            new Response(200, [], json_encode([])),
        ]);

        $epayment = new EPaymentApi($client);
        $result = $epayment->forceApprove('order-123', [
            'customer' => [
                'phoneNumber' => '4712345678',
            ],
        ]);

        $this->assertIsArray($result);
    }

    public function testCreateSimplePayment(): void
    {
        $client = $this->createMockClient([
            new Response(200, [], json_encode(['access_token' => 'test-token'])),
            new Response(201, [], json_encode([
                'reference' => 'order-123',
                'redirectUrl' => 'https://landing.vipps.no?token=abc123',
            ])),
        ]);

        $epayment = new EPaymentApi($client);
        $result = $epayment->createSimplePayment(
            reference: 'order-123',
            amount: 10000,
            currency: 'NOK',
            userFlow: 'WEB_REDIRECT',
            options: [
                'returnUrl' => 'https://example.com/return',
                'paymentDescription' => 'Simple payment',
            ]
        );

        $this->assertArrayHasKey('reference', $result);
        $this->assertEquals('order-123', $result['reference']);
    }

    public function testCaptureAmount(): void
    {
        $client = $this->createMockClient([
            new Response(200, [], json_encode(['access_token' => 'test-token'])),
            new Response(200, [], json_encode([
                'reference' => 'order-123',
                'aggregate' => [
                    'capturedAmount' => ['value' => 10000, 'currency' => 'NOK'],
                ],
            ])),
        ]);

        $epayment = new EPaymentApi($client);
        $result = $epayment->captureAmount('order-123', 10000);

        $this->assertArrayHasKey('aggregate', $result);
        $this->assertEquals(10000, $result['aggregate']['capturedAmount']['value']);
    }

    public function testRefundAmount(): void
    {
        $client = $this->createMockClient([
            new Response(200, [], json_encode(['access_token' => 'test-token'])),
            new Response(200, [], json_encode([
                'reference' => 'order-123',
                'aggregate' => [
                    'refundedAmount' => ['value' => 5000, 'currency' => 'NOK'],
                ],
            ])),
        ]);

        $epayment = new EPaymentApi($client);
        $result = $epayment->refundAmount('order-123', 5000);

        $this->assertArrayHasKey('aggregate', $result);
        $this->assertEquals(5000, $result['aggregate']['refundedAmount']['value']);
    }
}
