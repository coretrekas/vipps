<?php

declare(strict_types=1);

namespace Coretrek\Vipps\EPayment;

use Coretrek\Vipps\Exceptions\VippsException;

/**
 * Fluent builder for creating ePayment payments
 *
 * @package Vipps\EPayment
 */
class PaymentBuilder
{
    /** @var array<string, mixed> */
    private array $data = [];

    /** @var array<string, string> */
    private array $headers = [];

    public function __construct(
        private readonly EPaymentApi $api,
        array $config = []
    ) {
        $this->data = $config;
    }

    /**
     * Set the payment amount
     */
    public function amount(int $value, string $currency = 'NOK'): self
    {
        $this->data['amount'] = [
            'value' => $value,
            'currency' => $currency,
        ];
        return $this;
    }

    /**
     * Set the payment reference
     */
    public function reference(string $reference): self
    {
        $this->data['reference'] = $reference;
        return $this;
    }

    /**
     * Set the user flow
     */
    public function userFlow(string $userFlow): self
    {
        $this->data['userFlow'] = $userFlow;
        return $this;
    }

    /**
     * Set the return URL
     */
    public function returnUrl(string $returnUrl): self
    {
        $this->data['returnUrl'] = $returnUrl;
        return $this;
    }

    /**
     * Set the payment description
     */
    public function paymentDescription(string $description): self
    {
        $this->data['paymentDescription'] = $description;
        return $this;
    }

    /**
     * Set the payment method
     */
    public function paymentMethod(string $type, ?array $blockedSources = null): self
    {
        $this->data['paymentMethod'] = ['type' => $type];
        if ($blockedSources !== null) {
            $this->data['paymentMethod']['blockedSources'] = $blockedSources;
        }
        return $this;
    }

    /**
     * Set customer by phone number
     */
    public function customerPhoneNumber(string $phoneNumber): self
    {
        $this->data['customer'] = ['phoneNumber' => $phoneNumber];
        return $this;
    }

    /**
     * Set customer by personal QR code
     */
    public function customerPersonalQr(string $personalQr): self
    {
        $this->data['customer'] = ['personalQr' => $personalQr];
        return $this;
    }

    /**
     * Set customer by token
     */
    public function customerToken(string $customerToken): self
    {
        $this->data['customer'] = ['customerToken' => $customerToken];
        return $this;
    }

    /**
     * Set customer interaction type
     */
    public function customerInteraction(string $interaction): self
    {
        $this->data['customerInteraction'] = $interaction;
        return $this;
    }

    /**
     * Set minimum user age
     */
    public function minimumUserAge(int $age): self
    {
        $this->data['minimumUserAge'] = $age;
        return $this;
    }

    /**
     * Set expiration time
     */
    public function expiresAt(string $expiresAt): self
    {
        $this->data['expiresAt'] = $expiresAt;
        return $this;
    }

    /**
     * Set QR format
     */
    public function qrFormat(string $format, ?int $size = null): self
    {
        $this->data['qrFormat'] = ['format' => $format];
        if ($size !== null) {
            $this->data['qrFormat']['size'] = $size;
        }
        return $this;
    }

    /**
     * Set receipt
     */
    public function receipt(array $orderLines, array $bottomLine): self
    {
        $this->data['receipt'] = [
            'orderLines' => $orderLines,
            'bottomLine' => $bottomLine,
        ];
        return $this;
    }

    /**
     * Set receipt URL
     */
    public function receiptUrl(string $receiptUrl): self
    {
        $this->data['receiptUrl'] = $receiptUrl;
        return $this;
    }

    /**
     * Set metadata
     */
    public function metadata(array $metadata): self
    {
        $this->data['metadata'] = $metadata;
        return $this;
    }

    /**
     * Set profile scope for user info
     */
    public function profileScope(string $scope): self
    {
        $this->data['profile'] = ['scope' => $scope];
        return $this;
    }

    /**
     * Set industry data (e.g., airline data)
     */
    public function industryData(array $industryData): self
    {
        $this->data['industryData'] = $industryData;
        return $this;
    }

    /**
     * Set airline data
     */
    public function airlineData(
        string $agencyInvoiceNumber,
        string $airlineCode,
        string $airlineDesignatorCode,
        string $passengerName,
        ?string $ticketNumber = null
    ): self {
        $airlineData = [
            'agencyInvoiceNumber' => $agencyInvoiceNumber,
            'airlineCode' => $airlineCode,
            'airlineDesignatorCode' => $airlineDesignatorCode,
            'passengerName' => $passengerName,
        ];

        if ($ticketNumber !== null) {
            $airlineData['ticketNumber'] = $ticketNumber;
        }

        $this->data['industryData'] = ['airlineData' => $airlineData];
        return $this;
    }

    /**
     * Set shipping options (Express)
     */
    public function shipping(array $shipping): self
    {
        $this->data['shipping'] = $shipping;
        return $this;
    }

    /**
     * Set dynamic shipping options
     */
    public function dynamicShipping(string $callbackUrl, ?string $callbackAuthorizationToken = null): self
    {
        $dynamicOptions = ['callbackUrl' => $callbackUrl];
        if ($callbackAuthorizationToken !== null) {
            $dynamicOptions['callbackAuthorizationToken'] = $callbackAuthorizationToken;
        }

        $this->data['shipping'] = ['dynamicOptions' => $dynamicOptions];
        return $this;
    }

    /**
     * Set fixed shipping options
     */
    public function fixedShipping(array $shippingGroups): self
    {
        $this->data['shipping'] = ['fixedOptions' => $shippingGroups];
        return $this;
    }

    /**
     * Set idempotency key header
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
     * Get the built payment data
     *
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Get the headers
     *
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Create the payment
     *
     * @return array<string, mixed>
     * @throws VippsException
     */
    public function create(): array
    {
        return $this->api->createPayment($this->data, $this->headers);
    }
}
