<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Coretrek\Vipps\Exceptions\VippsException;
use Coretrek\Vipps\VippsClient;

/**
 * Example: Create a payment with ePayment API
 *
 * This example demonstrates how to create payments using the ePayment API,
 * which is designed for online and in-person payments.
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
    // Example 1: Create a simple payment using the builder
    echo "=== Example 1: Simple Web Redirect Payment ===\n\n";

    $reference = 'order-' . time();

    $payment = $client->epayment()
        ->buildPayment()
        ->amount(10000, 'NOK') // 100.00 NOK in minor units
        ->reference($reference)
        ->userFlow('WEB_REDIRECT')
        ->returnUrl('https://example.com/order/' . $reference . '/complete')
        ->paymentDescription('Order #' . $reference)
        ->paymentMethod('WALLET')
        ->customerInteraction('CUSTOMER_NOT_PRESENT')
        ->idempotencyKey('payment-' . $reference)
        ->systemInfo('example-shop', '1.0.0', 'vipps-plugin', '1.0.0')
        ->create();

    echo "✅ Payment created successfully!\n";
    echo 'Reference: ' . $payment['reference'] . "\n";
    echo 'Redirect URL: ' . $payment['redirectUrl'] . "\n\n";
    echo '👉 Redirect the customer to: ' . $payment['redirectUrl'] . "\n\n";

    // Example 2: Create a payment with receipt (order lines)
    echo "=== Example 2: Payment with Receipt ===\n\n";

    $reference2 = 'order-' . (time() + 1);

    $orderLines = [
        [
            'name' => 'Premium Socks',
            'id' => 'SOCK-001',
            'totalAmount' => 5000,
            'totalAmountExcludingTax' => 4000,
            'totalTaxAmount' => 1000,
            'unitInfo' => [
                'unitPrice' => 2500,
                'quantity' => '2',
                'quantityUnit' => 'PCS',
            ],
        ],
        [
            'name' => 'Shipping',
            'id' => 'SHIP-001',
            'totalAmount' => 5000,
            'totalAmountExcludingTax' => 4000,
            'totalTaxAmount' => 1000,
            'isShipping' => true,
        ],
    ];

    $bottomLine = [
        'currency' => 'NOK',
        'receiptNumber' => $reference2,
    ];

    $payment2 = $client->epayment()
        ->buildPayment()
        ->amount(10000, 'NOK')
        ->reference($reference2)
        ->userFlow('WEB_REDIRECT')
        ->returnUrl('https://example.com/order/' . $reference2 . '/complete')
        ->paymentDescription('Order with receipt')
        ->paymentMethod('WALLET')
        ->receipt($orderLines, $bottomLine)
        ->metadata([
            'orderId' => $reference2,
            'customerId' => 'CUST-12345',
        ])
        ->idempotencyKey('payment-' . $reference2)
        ->create();

    echo "✅ Payment with receipt created!\n";
    echo 'Reference: ' . $payment2['reference'] . "\n\n";

    // Example 3: Create a QR code payment
    echo "=== Example 3: QR Code Payment ===\n\n";

    $reference3 = 'order-' . (time() + 2);

    $payment3 = $client->epayment()
        ->buildPayment()
        ->amount(5000, 'NOK')
        ->reference($reference3)
        ->userFlow('QR')
        ->qrFormat('IMAGE/SVG+XML')
        ->paymentDescription('QR payment')
        ->paymentMethod('WALLET')
        ->idempotencyKey('payment-' . $reference3)
        ->create();

    echo "✅ QR payment created!\n";
    echo 'Reference: ' . $payment3['reference'] . "\n";
    echo 'QR Code URL: ' . $payment3['redirectUrl'] . "\n\n";

    // Example 4: Create a push message payment (requires phone number)
    echo "=== Example 4: Push Message Payment ===\n\n";

    $reference4 = 'order-' . (time() + 3);

    $payment4 = $client->epayment()
        ->buildPayment()
        ->amount(7500, 'NOK')
        ->reference($reference4)
        ->userFlow('PUSH_MESSAGE')
        ->customerPhoneNumber('4712345678')
        ->paymentDescription('Push message payment')
        ->paymentMethod('WALLET')
        ->idempotencyKey('payment-' . $reference4)
        ->create();

    echo "✅ Push message payment created!\n";
    echo 'Reference: ' . $payment4['reference'] . "\n\n";

    // Example 5: Get payment details
    echo "=== Example 5: Get Payment Details ===\n\n";

    $paymentDetails = $client->epayment()->getPayment($reference);

    echo 'Payment State: ' . $paymentDetails['state'] . "\n";
    echo 'Amount: ' . $paymentDetails['amount']['value'] . ' ' . $paymentDetails['amount']['currency'] . "\n";
    echo 'Authorized: ' . $paymentDetails['aggregate']['authorizedAmount']['value'] . "\n";
    echo 'Captured: ' . $paymentDetails['aggregate']['capturedAmount']['value'] . "\n\n";

    // Example 6: Get payment event log
    echo "=== Example 6: Get Payment Event Log ===\n\n";

    $events = $client->epayment()->getPaymentEventLog($reference);

    echo "Payment Events:\n";
    foreach ($events as $event) {
        echo '- ' . $event['name'] . ' at ' . $event['timestamp'] . "\n";
    }
    echo "\n";

    // Example 7: Capture a payment (after authorization)
    // Note: This would typically be done after the payment is authorized
    echo "=== Example 7: Capture Payment ===\n\n";

    // Uncomment to test capture:
    // $captureResult = $client->epayment()->captureAmount(
    //     reference: $reference,
    //     amount: 10000,
    //     currency: 'NOK',
    //     headers: ['Idempotency-Key' => 'capture-' . $reference]
    // );
    // echo "✅ Payment captured!\n";
    // echo 'Captured Amount: ' . $captureResult['aggregate']['capturedAmount']['value'] . "\n\n";

    // Example 8: Refund a payment (after capture)
    echo "=== Example 8: Refund Payment ===\n\n";

    // Uncomment to test refund:
    // $refundResult = $client->epayment()->refundAmount(
    //     reference: $reference,
    //     amount: 5000,
    //     currency: 'NOK',
    //     headers: ['Idempotency-Key' => 'refund-' . $reference]
    // );
    // echo "✅ Payment refunded!\n";
    // echo 'Refunded Amount: ' . $refundResult['aggregate']['refundedAmount']['value'] . "\n\n";

    // Example 9: Cancel a payment
    echo "=== Example 9: Cancel Payment ===\n\n";

    // Uncomment to test cancel:
    // $cancelResult = $client->epayment()->cancelPayment($reference);
    // echo "✅ Payment cancelled!\n";
    // echo 'Payment State: ' . $cancelResult['state'] . "\n\n";

    // Example 10: Force approve (test environment only)
    echo "=== Example 10: Force Approve (Test Only) ===\n\n";

    // Uncomment to test force approve:
    // $approveResult = $client->epayment()->forceApprove($reference, [
    //     'customer' => [
    //         'phoneNumber' => '4712345678',
    //     ],
    // ]);
    // echo "✅ Payment force approved!\n\n";

    // Example 11: Payment with Express shipping
    echo "=== Example 11: Payment with Express Shipping ===\n\n";

    $reference11 = 'order-' . (time() + 4);

    $payment11 = $client->epayment()
        ->buildPayment()
        ->amount(15000, 'NOK')
        ->reference($reference11)
        ->userFlow('WEB_REDIRECT')
        ->returnUrl('https://example.com/order/' . $reference11 . '/complete')
        ->paymentDescription('Order with shipping')
        ->paymentMethod('WALLET')
        ->fixedShipping([
            [
                'type' => 'HOME_DELIVERY',
                'brand' => 'POSTEN',
                'options' => [
                    [
                        'id' => 'posten-standard',
                        'name' => 'Standard Delivery',
                        'amount' => ['value' => 9900, 'currency' => 'NOK'],
                        'estimatedDelivery' => '2-3 days',
                    ],
                    [
                        'id' => 'posten-express',
                        'name' => 'Express Delivery',
                        'amount' => ['value' => 19900, 'currency' => 'NOK'],
                        'estimatedDelivery' => 'Next day',
                    ],
                ],
            ],
        ])
        ->profileScope('name email phoneNumber address')
        ->idempotencyKey('payment-' . $reference11)
        ->create();

    echo "✅ Payment with shipping created!\n";
    echo 'Reference: ' . $payment11['reference'] . "\n\n";

} catch (VippsException $e) {
    echo '❌ Error: ' . $e->getMessage() . "\n";
    echo 'Status Code: ' . $e->getCode() . "\n";

    if ($e->getErrorDetails()) {
        echo "Error Details:\n";
        print_r($e->getErrorDetails());
    }
}
