<?php

declare(strict_types=1);

namespace Coretrek\Vipps\Checkout;

use Coretrek\Vipps\Exceptions\VippsException;

/**
 * Fluent builder for creating checkout sessions
 *
 * @package Vipps\Checkout
 */
class SessionBuilder
{
    private array $data = [];
    private array $headers = [];

    public function __construct(
        private readonly CheckoutApi $api,
        string $type,
        array $config = []
    ) {
        $this->data = array_merge(['type' => $type], $config);
    }

    /**
     * Set the order reference
     */
    public function reference(string $reference): self
    {
        $this->data['reference'] = $reference;
        return $this;
    }

    /**
     * Set transaction details
     */
    public function transaction(int $amount, string $currency, string $reference, string $description): self
    {
        $this->data['transaction'] = [
            'amount' => [
                'value' => $amount,
                'currency' => $currency,
            ],
            'reference' => $reference,
            'paymentDescription' => $description,
        ];
        return $this;
    }

    /**
     * Set subscription details (for subscription sessions)
     */
    public function subscription(array $subscription): self
    {
        $this->data['subscription'] = $subscription;
        return $this;
    }

    /**
     * Set merchant info
     */
    public function merchantInfo(
        string $callbackUrl,
        string $returnUrl,
        string $termsAndConditionsUrl,
        ?string $callbackAuthorizationToken = null
    ): self {
        $this->data['merchantInfo'] = [
            'callbackUrl' => $callbackUrl,
            'returnUrl' => $returnUrl,
            'termsAndConditionsUrl' => $termsAndConditionsUrl,
        ];

        if ($callbackAuthorizationToken) {
            $this->data['merchantInfo']['callbackAuthorizationToken'] = $callbackAuthorizationToken;
        }

        return $this;
    }

    /**
     * Prefill customer information
     */
    public function prefillCustomer(array $customer): self
    {
        $this->data['prefillCustomer'] = $customer;
        return $this;
    }

    /**
     * Set logistics options
     */
    public function logistics(array $logistics): self
    {
        $this->data['logistics'] = $logistics;
        return $this;
    }

    /**
     * Set configuration
     */
    public function configuration(array $config): self
    {
        $this->data['configuration'] = $config;
        return $this;
    }

    /**
     * Set customer interaction type
     */
    public function customerInteraction(string $interaction): self
    {
        if (!isset($this->data['configuration'])) {
            $this->data['configuration'] = [];
        }
        $this->data['configuration']['customerInteraction'] = $interaction;
        return $this;
    }

    /**
     * Set elements to display
     */
    public function elements(string $elements): self
    {
        if (!isset($this->data['configuration'])) {
            $this->data['configuration'] = [];
        }
        $this->data['configuration']['elements'] = $elements;
        return $this;
    }

    /**
     * Set supported countries
     */
    public function countries(array $countries): self
    {
        if (!isset($this->data['configuration'])) {
            $this->data['configuration'] = [];
        }
        $this->data['configuration']['countries'] = [
            'supported' => $countries,
        ];
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
     * Get the built session data
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
     * Create the session
     *
     * @throws VippsException
     */
    public function create(): array
    {
        return $this->api->createSession($this->data, $this->headers);
    }
}
