<?php

declare(strict_types=1);

namespace Coretrek\Vipps\Tests\Integration;

use Coretrek\Vipps\VippsClient;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for Checkout API
 *
 * These tests require valid API credentials and will make real API calls.
 * Set the following environment variables to run these tests:
 * - VIPPS_CLIENT_ID
 * - VIPPS_CLIENT_SECRET
 * - VIPPS_SUBSCRIPTION_KEY
 * - VIPPS_MERCHANT_SERIAL_NUMBER
 */
class CheckoutIntegrationTest extends TestCase
{
    private ?VippsClient $client = null;

    protected function setUp(): void
    {
        $clientId = getenv('VIPPS_CLIENT_ID');
        $clientSecret = getenv('VIPPS_CLIENT_SECRET');
        $subscriptionKey = getenv('VIPPS_SUBSCRIPTION_KEY');
        $merchantSerialNumber = getenv('VIPPS_MERCHANT_SERIAL_NUMBER');

        if (!$clientId || !$clientSecret || !$subscriptionKey || !$merchantSerialNumber) {
            $this->markTestSkipped('Integration tests require environment variables to be set');
        }

        $this->client = new VippsClient(
            clientId: $clientId,
            clientSecret: $clientSecret,
            subscriptionKey: $subscriptionKey,
            merchantSerialNumber: $merchantSerialNumber,
            testMode: true
        );
    }

    public function testCreatePaymentSession(): void
    {
        if (!$this->client) {
            $this->markTestSkipped('Client not initialized');
        }

        $reference = 'test-order-' . time();

        $result = $this->client->checkout()
            ->buildPaymentSession()
            ->reference($reference)
            ->transaction(1000, 'NOK', $reference, 'Integration test payment')
            ->merchantInfo(
                'https://example.com/callback',
                'https://example.com/return',
                'https://example.com/terms'
            )
            ->customerInteraction('CUSTOMER_NOT_PRESENT')
            ->elements('Full')
            ->countries(['NO'])
            ->idempotencyKey('test-' . $reference)
            ->create();

        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('checkoutFrontendUrl', $result);
    }

    public function testGetSession(): void
    {
        if (!$this->client) {
            $this->markTestSkipped('Client not initialized');
        }

        // First create a session
        $reference = 'test-order-' . time();

        $this->client->checkout()
            ->buildPaymentSession()
            ->reference($reference)
            ->transaction(1000, 'NOK', $reference, 'Integration test payment')
            ->merchantInfo(
                'https://example.com/callback',
                'https://example.com/return',
                'https://example.com/terms'
            )
            ->idempotencyKey('test-' . $reference)
            ->create();

        // Then retrieve it
        $session = $this->client->checkout()->getSession($reference);

        $this->assertIsArray($session);
    }
}
