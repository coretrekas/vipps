<?php

declare(strict_types=1);

namespace Coretrek\Vipps;

use Coretrek\Vipps\Checkout\CheckoutApi;
use Coretrek\Vipps\Exceptions\VippsException;
use Coretrek\Vipps\Login\LoginApi;
use Coretrek\Vipps\Recurring\RecurringApi;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Main Vipps MobilePay SDK Client
 *
 * This is the primary entry point for interacting with Vipps MobilePay APIs.
 * It handles authentication, configuration, and provides access to API endpoints.
 *
 * @package Vipps
 */
class VippsClient
{
    private const PRODUCTION_URL = 'https://api.vipps.no';
    private const TEST_URL = 'https://apitest.vipps.no';

    private HttpClient $httpClient;
    private LoggerInterface $logger;
    private ?string $accessToken = null;
    private ?int $tokenExpiresAt = null;

    /**
     * @param string $clientId Client ID from Vipps portal
     * @param string $clientSecret Client Secret from Vipps portal
     * @param string $subscriptionKey Ocp-Apim-Subscription-Key from Vipps portal
     * @param string $merchantSerialNumber Merchant Serial Number (MSN)
     * @param bool $testMode Whether to use test environment (default: true)
     * @param array<string, mixed> $options Additional options
     */
    public function __construct(
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $subscriptionKey,
        private readonly string $merchantSerialNumber,
        private readonly bool $testMode = true,
        array $options = []
    ) {
        $this->httpClient = $options['http_client'] ?? new HttpClient([
            'base_uri' => $this->getBaseUrl(),
            'timeout' => $options['timeout'] ?? 30,
            'connect_timeout' => $options['connect_timeout'] ?? 10,
        ]);

        $this->logger = $options['logger'] ?? new NullLogger();
    }

    /**
     * Get the Checkout API instance
     */
    public function checkout(): CheckoutApi
    {
        return new CheckoutApi($this);
    }

    /**
     * Get the Recurring API instance
     */
    public function recurring(): RecurringApi
    {
        return new RecurringApi($this);
    }

    /**
     * Get the base URL for API requests
     */
    public function getBaseUrl(): string
    {
        return $this->testMode ? self::TEST_URL : self::PRODUCTION_URL;
    }

    /**
     * Get the merchant serial number
     */
    public function getMerchantSerialNumber(): string
    {
        return $this->merchantSerialNumber;
    }

    /**
     * Get the subscription key
     */
    public function getSubscriptionKey(): string
    {
        return $this->subscriptionKey;
    }

    /**
     * Get an access token, fetching a new one if needed
     *
     * @throws VippsException
     */
    public function getAccessToken(): string
    {
        if ($this->accessToken && $this->tokenExpiresAt && time() < $this->tokenExpiresAt) {
            return $this->accessToken;
        }

        $this->fetchAccessToken();

        if (!$this->accessToken) {
            throw new VippsException('Failed to obtain access token');
        }

        return $this->accessToken;
    }

    /**
     * Fetch a new access token from the API
     *
     * @throws VippsException
     */
    private function fetchAccessToken(): void
    {
        try {
            $response = $this->httpClient->request('POST', '/accesstoken/get', [
                'headers' => [
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'Ocp-Apim-Subscription-Key' => $this->subscriptionKey,
                    'Merchant-Serial-Number' => $this->merchantSerialNumber,
                ],
            ]);

            $data = json_decode((string) $response->getBody(), true);

            if (!isset($data['access_token'])) {
                throw new VippsException('Invalid access token response');
            }

            $this->accessToken = $data['access_token'];

            // Token expires in 1 hour for test, 24 hours for production
            // We'll refresh 5 minutes before expiry
            $expiresIn = $this->testMode ? 3600 : 86400;
            $this->tokenExpiresAt = time() + $expiresIn - 300;

            $this->logger->info('Access token fetched successfully');
        } catch (GuzzleException $e) {
            $this->logger->error('Failed to fetch access token', ['exception' => $e]);
            throw new VippsException('Failed to fetch access token: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Make an authenticated API request
     *
     * @param string $method HTTP method
     * @param string $uri Request URI
     * @param array<string, mixed> $options Request options
     * @return array<string, mixed> Response data
     * @throws VippsException
     */
    public function request(string $method, string $uri, array $options = []): array
    {
        try {
            $token = $this->getAccessToken();

            $headers = array_merge([
                'Authorization' => 'Bearer ' . $token,
                'Ocp-Apim-Subscription-Key' => $this->subscriptionKey,
                'Merchant-Serial-Number' => $this->merchantSerialNumber,
                'Content-Type' => 'application/json',
            ], $options['headers'] ?? []);

            $options['headers'] = $headers;

            $this->logger->debug('Making API request', [
                'method' => $method,
                'uri' => $uri,
            ]);

            $response = $this->httpClient->request($method, $uri, $options);
            $body = (string) $response->getBody();

            if (empty($body)) {
                return [];
            }

            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new VippsException('Invalid JSON response: ' . json_last_error_msg());
            }

            return $data;
        } catch (GuzzleException $e) {
            $this->logger->error('API request failed', [
                'method' => $method,
                'uri' => $uri,
                'exception' => $e,
            ]);

            throw VippsException::fromGuzzleException($e);
        }
    }

    /**
     * Get the HTTP client instance
     */
    public function getHttpClient(): HttpClient
    {
        return $this->httpClient;
    }

    /**
     * Get the logger instance
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Check if running in test mode
     */
    public function isTestMode(): bool
    {
        return $this->testMode;
    }

    /**
     * Get Login API instance
     */
    public function login(): LoginApi
    {
        return new LoginApi($this);
    }
}
