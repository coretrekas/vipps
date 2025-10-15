<?php

declare(strict_types=1);

namespace Coretrek\Vipps\Checkout;

use Coretrek\Vipps\Exceptions\VippsException;
use Coretrek\Vipps\VippsClient;

/**
 * Vipps Checkout API
 *
 * Handles checkout sessions for payments and subscriptions
 *
 * @package Vipps\Checkout
 */
class CheckoutApi
{
    private const API_VERSION = 'v3';

    public function __construct(
        private readonly VippsClient $client
    ) {
    }

    /**
     * Create a new checkout session
     *
     * @param array<string, mixed> $sessionData Session configuration
     * @param array<string, string> $headers Additional headers (e.g., Idempotency-Key)
     * @return array<string, mixed> Session response with token and checkout URL
     * @throws VippsException
     */
    public function createSession(array $sessionData, array $headers = []): array
    {
        $uri = sprintf('/checkout/%s/session', self::API_VERSION);

        $options = [
            'json' => $sessionData,
            'headers' => $headers,
        ];

        return $this->client->request('POST', $uri, $options);
    }

    /**
     * Create a payment session
     *
     * @param string $reference Unique reference for the order
     * @param int $amount Amount in minor units (e.g., 1000 = 10.00 NOK)
     * @param string $currency Currency code (NOK, EUR, DKK)
     * @param array<string, mixed> $options Additional session options
     * @param array<string, string> $headers Additional headers
     * @return array<string, mixed> Session response
     * @throws VippsException
     */
    public function createPaymentSession(
        string $reference,
        int $amount,
        string $currency,
        array $options = [],
        array $headers = []
    ): array {
        $sessionData = array_merge([
            'type' => 'PAYMENT',
            'reference' => $reference,
            'transaction' => [
                'amount' => [
                    'value' => $amount,
                    'currency' => $currency,
                ],
                'reference' => $reference,
                'paymentDescription' => $options['paymentDescription'] ?? 'Payment',
            ],
        ], $options);

        return $this->createSession($sessionData, $headers);
    }

    /**
     * Create a subscription session
     *
     * @param string $reference Unique reference for the subscription
     * @param int $amount Initial charge amount in minor units
     * @param string $currency Currency code
     * @param array<string, mixed> $subscription Subscription details
     * @param array<string, mixed> $options Additional session options
     * @param array<string, string> $headers Additional headers
     * @return array<string, mixed> Session response
     * @throws VippsException
     */
    public function createSubscriptionSession(
        string $reference,
        int $amount,
        string $currency,
        array $subscription,
        array $options = [],
        array $headers = []
    ): array {
        $sessionData = array_merge([
            'type' => 'SUBSCRIPTION',
            'reference' => $reference,
            'transaction' => [
                'amount' => [
                    'value' => $amount,
                    'currency' => $currency,
                ],
                'reference' => $reference,
                'paymentDescription' => $options['paymentDescription'] ?? 'Subscription payment',
            ],
            'subscription' => $subscription,
        ], $options);

        return $this->createSession($sessionData, $headers);
    }

    /**
     * Get session information
     *
     * @param string $reference Session reference
     * @param array<string, string> $headers Additional headers
     * @return array<string, mixed> Session details
     * @throws VippsException
     */
    public function getSession(string $reference, array $headers = []): array
    {
        $uri = sprintf('/checkout/%s/session/%s', self::API_VERSION, urlencode($reference));

        return $this->client->request('GET', $uri, ['headers' => $headers]);
    }

    /**
     * Update a session
     *
     * @param string $reference Session reference
     * @param array<string, mixed> $updateData Update data
     * @param array<string, string> $headers Additional headers
     * @return array<string, mixed> Response
     * @throws VippsException
     */
    public function updateSession(string $reference, array $updateData, array $headers = []): array
    {
        $uri = sprintf('/checkout/%s/session/%s', self::API_VERSION, urlencode($reference));

        $options = [
            'json' => $updateData,
            'headers' => $headers,
        ];

        return $this->client->request('PATCH', $uri, $options);
    }

    /**
     * Expire a session
     *
     * @param string $reference Session reference
     * @param array<string, string> $headers Additional headers
     * @return array<string, mixed> Response
     * @throws VippsException
     */
    public function expireSession(string $reference, array $headers = []): array
    {
        $uri = sprintf('/checkout/%s/session/%s/expire', self::API_VERSION, urlencode($reference));

        return $this->client->request('POST', $uri, ['headers' => $headers]);
    }

    /**
     * Build a complete payment session with all common options
     *
     * @param array<string, mixed> $config Session configuration
     * @return SessionBuilder
     */
    public function buildPaymentSession(array $config = []): SessionBuilder
    {
        return new SessionBuilder($this, 'PAYMENT', $config);
    }

    /**
     * Build a complete subscription session with all common options
     *
     * @param array<string, mixed> $config Session configuration
     * @return SessionBuilder
     */
    public function buildSubscriptionSession(array $config = []): SessionBuilder
    {
        return new SessionBuilder($this, 'SUBSCRIPTION', $config);
    }
}
