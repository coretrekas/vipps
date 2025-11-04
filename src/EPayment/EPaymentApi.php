<?php

declare(strict_types=1);

namespace Coretrek\Vipps\EPayment;

use Coretrek\Vipps\Exceptions\VippsException;
use Coretrek\Vipps\VippsClient;

/**
 * Vipps ePayment API
 *
 * The ePayment API enables you to create Vipps MobilePay payments for online
 * and in-person payments.
 *
 * @see https://developer.vippsmobilepay.com/docs/APIs/epayment-api
 * @package Vipps\EPayment
 */
class EPaymentApi
{
    private const API_VERSION = 'v1';

    public function __construct(
        private readonly VippsClient $client
    ) {
    }

    /**
     * Create a new payment
     *
     * @param array<string, mixed> $paymentData Payment configuration
     * @param array<string, string> $headers Additional headers (e.g., Idempotency-Key)
     * @return array<string, mixed> Payment response with reference and redirectUrl
     * @throws VippsException
     */
    public function createPayment(array $paymentData, array $headers = []): array
    {
        $uri = sprintf('/epayment/%s/payments', self::API_VERSION);

        $options = [
            'json' => $paymentData,
            'headers' => $headers,
        ];

        return $this->client->request('POST', $uri, $options);
    }

    /**
     * Get a payment by reference
     *
     * @param string $reference Payment reference
     * @param array<string, string> $headers Additional headers
     * @return array<string, mixed> Payment details
     * @throws VippsException
     */
    public function getPayment(string $reference, array $headers = []): array
    {
        $uri = sprintf('/epayment/%s/payments/%s', self::API_VERSION, urlencode($reference));

        return $this->client->request('GET', $uri, ['headers' => $headers]);
    }

    /**
     * Get a payment's event log
     *
     * @param string $reference Payment reference
     * @param array<string, string> $headers Additional headers
     * @return array<string, mixed> Array of payment events
     * @throws VippsException
     */
    public function getPaymentEventLog(string $reference, array $headers = []): array
    {
        $uri = sprintf('/epayment/%s/payments/%s/events', self::API_VERSION, urlencode($reference));

        return $this->client->request('GET', $uri, ['headers' => $headers]);
    }

    /**
     * Cancel a payment
     *
     * @param string $reference Payment reference
     * @param array<string, mixed>|null $cancelData Optional cancel request data
     * @param array<string, string> $headers Additional headers
     * @return array<string, mixed> Cancellation response
     * @throws VippsException
     */
    public function cancelPayment(string $reference, ?array $cancelData = null, array $headers = []): array
    {
        $uri = sprintf('/epayment/%s/payments/%s/cancel', self::API_VERSION, urlencode($reference));

        $options = ['headers' => $headers];
        if ($cancelData !== null) {
            $options['json'] = $cancelData;
        }

        return $this->client->request('POST', $uri, $options);
    }

    /**
     * Capture a payment
     *
     * @param string $reference Payment reference
     * @param array<string, mixed> $captureData Capture request data with modificationAmount
     * @param array<string, string> $headers Additional headers (e.g., Idempotency-Key)
     * @return array<string, mixed> Capture response
     * @throws VippsException
     */
    public function capturePayment(string $reference, array $captureData, array $headers = []): array
    {
        $uri = sprintf('/epayment/%s/payments/%s/capture', self::API_VERSION, urlencode($reference));

        $options = [
            'json' => $captureData,
            'headers' => $headers,
        ];

        return $this->client->request('POST', $uri, $options);
    }

    /**
     * Refund a payment
     *
     * @param string $reference Payment reference
     * @param array<string, mixed> $refundData Refund request data with modificationAmount
     * @param array<string, string> $headers Additional headers (e.g., Idempotency-Key)
     * @return array<string, mixed> Refund response
     * @throws VippsException
     */
    public function refundPayment(string $reference, array $refundData, array $headers = []): array
    {
        $uri = sprintf('/epayment/%s/payments/%s/refund', self::API_VERSION, urlencode($reference));

        $options = [
            'json' => $refundData,
            'headers' => $headers,
        ];

        return $this->client->request('POST', $uri, $options);
    }

    /**
     * Force approve a payment (test environment only)
     *
     * This endpoint is only available in the test environment.
     * It allows developers to approve a payment through the ePayment API
     * without the use of the Vipps or MobilePay app.
     *
     * @param string $reference Payment reference
     * @param array<string, mixed>|null $approveData Optional approve request data
     * @param array<string, string> $headers Additional headers
     * @return array<string, mixed> Approval response
     * @throws VippsException
     */
    public function forceApprove(string $reference, ?array $approveData = null, array $headers = []): array
    {
        $uri = sprintf('/epayment/%s/test/payments/%s/approve', self::API_VERSION, urlencode($reference));

        $options = ['headers' => $headers];
        if ($approveData !== null) {
            $options['json'] = $approveData;
        }

        return $this->client->request('POST', $uri, $options);
    }

    /**
     * Build a payment with fluent interface
     *
     * @param array<string, mixed> $config Initial payment configuration
     * @return PaymentBuilder
     */
    public function buildPayment(array $config = []): PaymentBuilder
    {
        return new PaymentBuilder($this, $config);
    }

    /**
     * Helper method to create a simple payment
     *
     * @param string $reference Unique payment reference
     * @param int $amount Amount in minor units (øre/cents)
     * @param string $currency Currency code (NOK, DKK, EUR)
     * @param string $userFlow User flow type (WEB_REDIRECT, PUSH_MESSAGE, QR, NATIVE_REDIRECT)
     * @param array<string, mixed> $options Additional payment options
     * @param array<string, string> $headers Additional headers
     * @return array<string, mixed> Payment response
     * @throws VippsException
     */
    public function createSimplePayment(
        string $reference,
        int $amount,
        string $currency,
        string $userFlow,
        array $options = [],
        array $headers = []
    ): array {
        $paymentData = array_merge([
            'amount' => [
                'value' => $amount,
                'currency' => $currency,
            ],
            'paymentMethod' => [
                'type' => 'WALLET',
            ],
            'reference' => $reference,
            'userFlow' => $userFlow,
            'paymentDescription' => $options['paymentDescription'] ?? 'Payment',
        ], $options);

        return $this->createPayment($paymentData, $headers);
    }

    /**
     * Helper method to capture a payment with amount
     *
     * @param string $reference Payment reference
     * @param int $amount Amount to capture in minor units
     * @param string $currency Currency code
     * @param array<string, string> $headers Additional headers (e.g., Idempotency-Key)
     * @return array<string, mixed> Capture response
     * @throws VippsException
     */
    public function captureAmount(
        string $reference,
        int $amount,
        string $currency = 'NOK',
        array $headers = []
    ): array {
        return $this->capturePayment($reference, [
            'modificationAmount' => [
                'value' => $amount,
                'currency' => $currency,
            ],
        ], $headers);
    }

    /**
     * Helper method to refund a payment with amount
     *
     * @param string $reference Payment reference
     * @param int $amount Amount to refund in minor units
     * @param string $currency Currency code
     * @param array<string, string> $headers Additional headers (e.g., Idempotency-Key)
     * @return array<string, mixed> Refund response
     * @throws VippsException
     */
    public function refundAmount(
        string $reference,
        int $amount,
        string $currency = 'NOK',
        array $headers = []
    ): array {
        return $this->refundPayment($reference, [
            'modificationAmount' => [
                'value' => $amount,
                'currency' => $currency,
            ],
        ], $headers);
    }
}
