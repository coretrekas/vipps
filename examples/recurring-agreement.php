<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Coretrek\Vipps\Exceptions\VippsException;
use Coretrek\Vipps\VippsClient;

/**
 * Example: Create a recurring agreement with Recurring API
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
    // Create a recurring agreement with a campaign
    $agreement = $client->recurring()
        ->buildAgreement()
        ->legacyPricing(2500, 'NOK') // 25.00 NOK per month
        ->interval('MONTH', 1)
        ->product(
            name: 'Premium Subscription',
            description: 'Access to all premium features'
        )
        ->merchantUrls(
            redirectUrl: 'https://example.com/subscription/complete',
            agreementUrl: 'https://example.com/my-subscriptions'
        )
        ->phoneNumber('4712345678')
        ->initialCharge(
            amount: 100, // 1.00 NOK activation fee
            currency: 'NOK',
            description: 'Activation fee',
            transactionType: 'DIRECT_CAPTURE'
        )
        ->periodCampaign(
            price: 100, // 1.00 NOK for the campaign period
            unit: 'WEEK',
            count: 4 // First 4 weeks
        )
        ->scope('address name email phoneNumber')
        ->idempotencyKey('agreement-' . time())
        ->systemInfo('example-shop', '1.0.0')
        ->create();

    echo "✅ Agreement created successfully!\n\n";
    echo 'Agreement ID: ' . $agreement['agreementId'] . "\n";
    echo 'Confirmation URL: ' . $agreement['vippsConfirmationUrl'] . "\n";

    if (isset($agreement['chargeId'])) {
        echo 'Initial Charge ID: ' . $agreement['chargeId'] . "\n";
    }

    echo "\n👉 Redirect the customer to: " . $agreement['vippsConfirmationUrl'] . "\n";

    // Later, you can check the agreement status
    echo "\n--- Checking agreement status ---\n";

    $agreementInfo = $client->recurring()->getAgreement($agreement['agreementId']);

    echo 'Status: ' . $agreementInfo['status'] . "\n";
    echo 'Product: ' . $agreementInfo['productName'] . "\n";

    // If the agreement is active, you can create charges
    if ($agreementInfo['status'] === 'ACTIVE') {
        echo "\n--- Creating a charge ---\n";

        $charge = $client->recurring()->createCharge(
            agreementId: $agreement['agreementId'],
            chargeData: [
                'amount' => 2500,
                'transactionType' => 'DIRECT_CAPTURE',
                'description' => 'Monthly subscription - ' . date('F Y'),
                'due' => date('Y-m-d', strtotime('+1 month')),
                'retryDays' => 5,
                'type' => 'RECURRING',
            ],
            headers: ['Idempotency-Key' => 'charge-' . time()]
        );

        echo 'Charge created: ' . $charge['chargeId'] . "\n";
    }

} catch (VippsException $e) {
    echo '❌ Error: ' . $e->getMessage() . "\n";
    echo 'Status Code: ' . $e->getCode() . "\n";

    if ($e->getErrorDetails()) {
        echo "Error Details:\n";
        print_r($e->getErrorDetails());
    }
}
