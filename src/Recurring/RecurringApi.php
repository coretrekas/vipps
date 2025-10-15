<?php

declare(strict_types=1);

namespace Coretrek\Vipps\Recurring;

use Coretrek\Vipps\Exceptions\VippsException;
use Coretrek\Vipps\VippsClient;

/**
 * Vipps Recurring Payments API
 *
 * Handles recurring payment agreements and charges
 *
 * @package Vipps\Recurring
 */
class RecurringApi
{
    private const API_VERSION = 'v3';

    public function __construct(
        private readonly VippsClient $client
    ) {
    }

    /**
     * List all agreements
     *
     * @param array<string, mixed> $params Query parameters (status, createdAfter, pageNumber, pageSize)
     * @param array<string, string> $headers Additional headers
     * @return array<string, mixed> List of agreements
     * @throws VippsException
     */
    public function listAgreements(array $params = [], array $headers = []): array
    {
        $uri = sprintf('/recurring/%s/agreements', self::API_VERSION);

        if (!empty($params)) {
            $uri .= '?' . http_build_query($params);
        }

        return $this->client->request('GET', $uri, ['headers' => $headers]);
    }

    /**
     * Create a new agreement
     *
     * @param array<string, mixed> $agreementData Agreement configuration
     * @param array<string, string> $headers Additional headers (e.g., Idempotency-Key)
     * @return array<string, mixed> Agreement response with agreementId and vippsConfirmationUrl
     * @throws VippsException
     */
    public function createAgreement(array $agreementData, array $headers = []): array
    {
        $uri = sprintf('/recurring/%s/agreements', self::API_VERSION);

        $options = [
            'json' => $agreementData,
            'headers' => $headers,
        ];

        return $this->client->request('POST', $uri, $options);
    }

    /**
     * Get agreement details
     *
     * @param string $agreementId Agreement ID
     * @param array<string, string> $headers Additional headers
     * @return array<string, mixed> Agreement details
     * @throws VippsException
     */
    public function getAgreement(string $agreementId, array $headers = []): array
    {
        $uri = sprintf('/recurring/%s/agreements/%s', self::API_VERSION, urlencode($agreementId));

        return $this->client->request('GET', $uri, ['headers' => $headers]);
    }

    /**
     * Update an agreement
     *
     * @param string $agreementId Agreement ID
     * @param array<string, mixed> $updateData Update data
     * @param array<string, string> $headers Additional headers
     * @return array<string, mixed> Response
     * @throws VippsException
     */
    public function updateAgreement(string $agreementId, array $updateData, array $headers = []): array
    {
        $uri = sprintf('/recurring/%s/agreements/%s', self::API_VERSION, urlencode($agreementId));

        $options = [
            'json' => $updateData,
            'headers' => $headers,
        ];

        return $this->client->request('PATCH', $uri, $options);
    }

    /**
     * Force accept an agreement (test environment only)
     *
     * @param string $agreementId Agreement ID
     * @param array<string, mixed> $data Phone number data
     * @param array<string, string> $headers Additional headers
     * @return array<string, mixed> Response
     * @throws VippsException
     */
    public function forceAcceptAgreement(string $agreementId, array $data, array $headers = []): array
    {
        $uri = sprintf('/recurring/%s/agreements/%s/accept', self::API_VERSION, urlencode($agreementId));

        $options = [
            'json' => $data,
            'headers' => $headers,
        ];

        return $this->client->request('PATCH', $uri, $options);
    }

    /**
     * List charges for an agreement
     *
     * @param string $agreementId Agreement ID
     * @param array<string, mixed> $params Query parameters (status)
     * @param array<string, string> $headers Additional headers
     * @return array<string, mixed> List of charges
     * @throws VippsException
     */
    public function listCharges(string $agreementId, array $params = [], array $headers = []): array
    {
        $uri = sprintf('/recurring/%s/agreements/%s/charges', self::API_VERSION, urlencode($agreementId));

        if (!empty($params)) {
            $uri .= '?' . http_build_query($params);
        }

        return $this->client->request('GET', $uri, ['headers' => $headers]);
    }

    /**
     * Create a new charge
     *
     * @param string $agreementId Agreement ID
     * @param array<string, mixed> $chargeData Charge configuration
     * @param array<string, string> $headers Additional headers (e.g., Idempotency-Key)
     * @return array<string, mixed> Charge reference
     * @throws VippsException
     */
    public function createCharge(string $agreementId, array $chargeData, array $headers = []): array
    {
        $uri = sprintf('/recurring/%s/agreements/%s/charges', self::API_VERSION, urlencode($agreementId));

        $options = [
            'json' => $chargeData,
            'headers' => $headers,
        ];

        return $this->client->request('POST', $uri, $options);
    }

    /**
     * Create multiple charges asynchronously
     *
     * @param array<int, array<string, mixed>> $charges Array of charge configurations
     * @param array<string, string> $headers Additional headers (e.g., Idempotency-Key)
     * @return array<string, mixed> Async charge response
     * @throws VippsException
     */
    public function createChargesAsync(array $charges, array $headers = []): array
    {
        $uri = sprintf('/recurring/%s/agreements/charges', self::API_VERSION);

        $options = [
            'json' => $charges,
            'headers' => $headers,
        ];

        return $this->client->request('POST', $uri, $options);
    }

    /**
     * Get charge details
     *
     * @param string $agreementId Agreement ID
     * @param string $chargeId Charge ID
     * @param array<string, string> $headers Additional headers
     * @return array<string, mixed> Charge details
     * @throws VippsException
     */
    public function getCharge(string $agreementId, string $chargeId, array $headers = []): array
    {
        $uri = sprintf(
            '/recurring/%s/agreements/%s/charges/%s',
            self::API_VERSION,
            urlencode($agreementId),
            urlencode($chargeId)
        );

        return $this->client->request('GET', $uri, ['headers' => $headers]);
    }

    /**
     * Get charge by ID only (without agreement ID)
     *
     * @param string $chargeId Charge ID
     * @param array<string, string> $headers Additional headers
     * @return array<string, mixed> Charge details
     * @throws VippsException
     */
    public function getChargeById(string $chargeId, array $headers = []): array
    {
        $uri = sprintf('/recurring/%s/charges/%s', self::API_VERSION, urlencode($chargeId));

        return $this->client->request('GET', $uri, ['headers' => $headers]);
    }

    /**
     * Cancel a charge
     *
     * @param string $agreementId Agreement ID
     * @param string $chargeId Charge ID
     * @param array<string, string> $headers Additional headers
     * @return array<string, mixed> Response
     * @throws VippsException
     */
    public function cancelCharge(string $agreementId, string $chargeId, array $headers = []): array
    {
        $uri = sprintf(
            '/recurring/%s/agreements/%s/charges/%s',
            self::API_VERSION,
            urlencode($agreementId),
            urlencode($chargeId)
        );

        return $this->client->request('DELETE', $uri, ['headers' => $headers]);
    }

    /**
     * Capture a reserved charge
     *
     * @param string $agreementId Agreement ID
     * @param string $chargeId Charge ID
     * @param array<string, mixed> $captureData Capture data (amount, description)
     * @param array<string, string> $headers Additional headers
     * @return array<string, mixed> Response
     * @throws VippsException
     */
    public function captureCharge(
        string $agreementId,
        string $chargeId,
        array $captureData,
        array $headers = []
    ): array {
        $uri = sprintf(
            '/recurring/%s/agreements/%s/charges/%s/capture',
            self::API_VERSION,
            urlencode($agreementId),
            urlencode($chargeId)
        );

        $options = [
            'json' => $captureData,
            'headers' => $headers,
        ];

        return $this->client->request('POST', $uri, $options);
    }

    /**
     * Refund a charge
     *
     * @param string $agreementId Agreement ID
     * @param string $chargeId Charge ID
     * @param array<string, mixed> $refundData Refund data (amount, description)
     * @param array<string, string> $headers Additional headers
     * @return array<string, mixed> Response
     * @throws VippsException
     */
    public function refundCharge(
        string $agreementId,
        string $chargeId,
        array $refundData,
        array $headers = []
    ): array {
        $uri = sprintf(
            '/recurring/%s/agreements/%s/charges/%s/refund',
            self::API_VERSION,
            urlencode($agreementId),
            urlencode($chargeId)
        );

        $options = [
            'json' => $refundData,
            'headers' => $headers,
        ];

        return $this->client->request('POST', $uri, $options);
    }

    /**
     * Build an agreement with fluent interface
     *
     * @param array<string, mixed> $config Initial configuration
     * @return AgreementBuilder
     */
    public function buildAgreement(array $config = []): AgreementBuilder
    {
        return new AgreementBuilder($this, $config);
    }
}
