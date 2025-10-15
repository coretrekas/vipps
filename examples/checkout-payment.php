<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Coretrek\Vipps\Exceptions\VippsException;
use Coretrek\Vipps\VippsClient;

/**
 * Example: Create a payment session with Checkout API
 */

// Initialize the client
$client = new VippsClient(
    clientId: getenv('VIPPS_CLIENT_ID') ?: 'your-client-id',
    clientSecret: getenv('VIPPS_CLIENT_SECRET') ?: 'your-client-secret',
    subscriptionKey: getenv('VIPPS_SUBSCRIPTION_KEY') ?: 'your-subscription-key',
    merchantSerialNumber: getenv('VIPPS_MERCHANT_SERIAL_NUMBER') ?: 'your-msn',
    testMode: true
);

try {
    // Create a payment session using the builder
    $reference = 'order-' . time();

    $session = $client->checkout()
        ->buildPaymentSession()
        ->reference($reference)
        ->transaction(
            amount: 10000, // 100.00 NOK in minor units
            currency: 'NOK',
            reference: $reference,
            description: 'Example payment for order #' . $reference
        )
        ->merchantInfo(
            callbackUrl: 'https://example.com/vipps/callback',
            returnUrl: 'https://example.com/order/' . $reference . '/complete',
            termsAndConditionsUrl: 'https://example.com/terms',
            callbackAuthorizationToken: 'secret-token-' . bin2hex(random_bytes(16))
        )
        ->prefillCustomer([
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john.doe@example.com',
            'phoneNumber' => '+4712345678',
            'streetAddress' => 'Example Street 1',
            'city' => 'Oslo',
            'postalCode' => '0150',
            'country' => 'NO',
        ])
        ->customerInteraction('CUSTOMER_NOT_PRESENT')
        ->elements('Full')
        ->countries(['NO', 'SE', 'DK'])
        ->idempotencyKey('payment-' . $reference)
        ->systemInfo('example-shop', '1.0.0', 'vipps-plugin', '1.0.0')
        ->create();

    echo "✅ Payment session created successfully!\n\n";
    echo 'Session Token: ' . $session['token'] . "\n";
    echo 'Checkout URL: ' . $session['checkoutFrontendUrl'] . "\n";
    echo 'Polling URL: ' . $session['pollingUrl'] . "\n\n";
    echo '👉 Redirect the customer to: ' . $session['checkoutFrontendUrl'] . "\n";

    // Later, you can retrieve the session information
    echo "\n--- Retrieving session information ---\n";

    $sessionInfo = $client->checkout()->getSession($reference);

    echo 'Session State: ' . $sessionInfo['sessionState'] . "\n";

} catch (VippsException $e) {
    echo '❌ Error: ' . $e->getMessage() . "\n";
    echo 'Status Code: ' . $e->getCode() . "\n";

    if ($e->getErrorDetails()) {
        echo "Error Details:\n";
        print_r($e->getErrorDetails());
    }
}
