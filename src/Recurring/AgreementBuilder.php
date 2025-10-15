<?php

declare(strict_types=1);

namespace Coretrek\Vipps\Recurring;

use Coretrek\Vipps\Exceptions\VippsException;

/**
 * Fluent builder for creating recurring agreements
 *
 * @package Vipps\Recurring
 */
class AgreementBuilder
{
    private array $data = [];
    private array $headers = [];

    public function __construct(
        private readonly RecurringApi $api,
        array $config = []
    ) {
        $this->data = $config;
    }

    /**
     * Set pricing with legacy type (fixed amount)
     */
    public function legacyPricing(int $amount, string $currency = 'NOK'): self
    {
        $this->data['pricing'] = [
            'type' => 'LEGACY',
            'amount' => $amount,
            'currency' => $currency,
        ];
        return $this;
    }

    /**
     * Set pricing with variable type
     */
    public function variablePricing(int $suggestedMaxAmount, string $currency = 'NOK'): self
    {
        $this->data['pricing'] = [
            'type' => 'VARIABLE',
            'suggestedMaxAmount' => $suggestedMaxAmount,
            'currency' => $currency,
        ];
        return $this;
    }

    /**
     * Set pricing with flexible type
     */
    public function flexiblePricing(string $currency = 'NOK'): self
    {
        $this->data['pricing'] = [
            'type' => 'FLEXIBLE',
            'currency' => $currency,
        ];
        return $this;
    }

    /**
     * Set interval
     */
    public function interval(string $unit, int $count = 1): self
    {
        $this->data['interval'] = [
            'unit' => $unit,
            'count' => $count,
        ];
        return $this;
    }

    /**
     * Set product information
     */
    public function product(string $name, ?string $description = null): self
    {
        $this->data['productName'] = $name;
        if ($description) {
            $this->data['productDescription'] = $description;
        }
        return $this;
    }

    /**
     * Set merchant URLs
     */
    public function merchantUrls(string $redirectUrl, ?string $agreementUrl = null): self
    {
        $this->data['merchantRedirectUrl'] = $redirectUrl;
        if ($agreementUrl) {
            $this->data['merchantAgreementUrl'] = $agreementUrl;
        }
        return $this;
    }

    /**
     * Set phone number
     */
    public function phoneNumber(string $phoneNumber): self
    {
        $this->data['phoneNumber'] = $phoneNumber;
        return $this;
    }

    /**
     * Set initial charge
     */
    public function initialCharge(
        int $amount,
        string $currency,
        string $description,
        string $transactionType = 'DIRECT_CAPTURE'
    ): self {
        $this->data['initialCharge'] = [
            'amount' => $amount,
            'currency' => $currency,
            'description' => $description,
            'transactionType' => $transactionType,
        ];
        return $this;
    }

    /**
     * Set price campaign
     */
    public function priceCampaign(int $price, string $end): self
    {
        $this->data['campaign'] = [
            'type' => 'PRICE_CAMPAIGN',
            'price' => $price,
            'end' => $end,
        ];
        return $this;
    }

    /**
     * Set period campaign
     */
    public function periodCampaign(int $price, string $unit, int $count): self
    {
        $this->data['campaign'] = [
            'type' => 'PERIOD_CAMPAIGN',
            'price' => $price,
            'period' => [
                'unit' => $unit,
                'count' => $count,
            ],
        ];
        return $this;
    }

    /**
     * Set event campaign
     */
    public function eventCampaign(int $price, string $eventDate, string $eventText): self
    {
        $this->data['campaign'] = [
            'type' => 'EVENT_CAMPAIGN',
            'price' => $price,
            'eventDate' => $eventDate,
            'eventText' => $eventText,
        ];
        return $this;
    }

    /**
     * Set scope for user data
     */
    public function scope(string $scope): self
    {
        $this->data['scope'] = $scope;
        return $this;
    }

    /**
     * Enable app flow
     */
    public function isApp(bool $isApp = true): self
    {
        $this->data['isApp'] = $isApp;
        return $this;
    }

    /**
     * Skip landing page
     */
    public function skipLandingPage(bool $skip = true): self
    {
        $this->data['skipLandingPage'] = $skip;
        return $this;
    }

    /**
     * Set external ID
     */
    public function externalId(string $externalId): self
    {
        $this->data['externalId'] = $externalId;
        return $this;
    }

    /**
     * Set idempotency key
     */
    public function idempotencyKey(string $key): self
    {
        $this->headers['Idempotency-Key'] = $key;
        return $this;
    }

    /**
     * Set system information headers
     */
    public function systemInfo(
        string $systemName,
        string $systemVersion,
        ?string $pluginName = null,
        ?string $pluginVersion = null
    ): self {
        $this->headers['Vipps-System-Name'] = $systemName;
        $this->headers['Vipps-System-Version'] = $systemVersion;

        if ($pluginName) {
            $this->headers['Vipps-System-Plugin-Name'] = $pluginName;
        }

        if ($pluginVersion) {
            $this->headers['Vipps-System-Plugin-Version'] = $pluginVersion;
        }

        return $this;
    }

    /**
     * Add a custom header
     */
    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Get the built agreement data
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Get the headers
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Create the agreement
     *
     * @throws VippsException
     */
    public function create(): array
    {
        return $this->api->createAgreement($this->data, $this->headers);
    }
}
