<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Coretrek\Vipps\Exceptions\VippsException;
use Coretrek\Vipps\VippsClient;

/**
 * Example: Handle Vipps callbacks/webhooks
 *
 * This example shows how to handle callbacks from Vipps when a payment
 * or subscription is completed.
 */

// Initialize the client
$client = new VippsClient(
    clientId: getenv('VIPPS_CLIENT_ID') ?: 'your-client-id',
    clientSecret: getenv('VIPPS_CLIENT_SECRET') ?: 'your-client-secret',
    subscriptionKey: getenv('VIPPS_SUBSCRIPTION_KEY') ?: 'your-subscription-key',
    merchantSerialNumber: getenv('VIPPS_MERCHANT_SERIAL_NUMBER') ?: 'your-msn',
    testMode: true
);

// Get the callback data
$rawPayload = file_get_contents('php://input');
$payload = json_decode($rawPayload, true);

// Verify the authorization token (if you set one)
$expectedToken = 'your-callback-authorization-token';
$receivedToken = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

if ($receivedToken !== 'Bearer ' . $expectedToken) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Log the callback
error_log('Vipps callback received: ' . $rawPayload);

try {
    // Handle Checkout API callback
    if (isset($payload['sessionId'])) {
        handleCheckoutCallback($client, $payload);
    }
    // Handle Recurring API callback (if applicable)
    elseif (isset($payload['agreementId'])) {
        handleRecurringCallback($client, $payload);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid payload']);
        exit;
    }

    // Return success
    http_response_code(200);
    echo json_encode(['status' => 'ok']);

} catch (Exception $e) {
    error_log('Error processing callback: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}

/**
 * Handle Checkout API callback
 */
function handleCheckoutCallback(VippsClient $client, array $payload): void
{
    $sessionId = $payload['sessionId'];
    $sessionState = $payload['sessionState'];
    $reference = $payload['reference'] ?? null;

    error_log("Checkout callback - Session: $sessionId, State: $sessionState");

    // Fetch the full session details
    if ($reference) {
        try {
            $session = $client->checkout()->getSession($reference);

            // Process based on session state
            switch ($session['sessionState']) {
                case 'PaymentInitiated':
                    // Payment was successful
                    processSuccessfulPayment($session);
                    break;

                case 'PaymentTerminated':
                    // Payment was cancelled or failed
                    processFailedPayment($session);
                    break;

                case 'SessionExpired':
                    // Session expired
                    processExpiredSession($session);
                    break;
            }
        } catch (VippsException $e) {
            error_log('Error fetching session: ' . $e->getMessage());
            throw $e;
        }
    }
}

/**
 * Handle Recurring API callback
 */
function handleRecurringCallback(VippsClient $client, array $payload): void
{
    $agreementId = $payload['agreementId'];
    $status = $payload['status'] ?? null;

    error_log("Recurring callback - Agreement: $agreementId, Status: $status");

    // Fetch the full agreement details
    try {
        $agreement = $client->recurring()->getAgreement($agreementId);

        // Process based on agreement status
        switch ($agreement['status']) {
            case 'ACTIVE':
                // Agreement is now active
                processActiveAgreement($agreement);
                break;

            case 'STOPPED':
                // Agreement was stopped
                processStoppedAgreement($agreement);
                break;

            case 'EXPIRED':
                // Agreement expired
                processExpiredAgreement($agreement);
                break;
        }
    } catch (VippsException $e) {
        error_log('Error fetching agreement: ' . $e->getMessage());
        throw $e;
    }
}

/**
 * Process successful payment
 */
function processSuccessfulPayment(array $session): void
{
    $reference = $session['reference'];

    // Update your database
    // Mark order as paid
    // Send confirmation email
    // Fulfill the order

    error_log("Processing successful payment for reference: $reference");

    // Example: Update order in database
    // $db->query("UPDATE orders SET status = 'paid', vipps_session_id = ? WHERE reference = ?",
    //            [$session['sessionId'], $reference]);
}

/**
 * Process failed payment
 */
function processFailedPayment(array $session): void
{
    $reference = $session['reference'];

    error_log("Processing failed payment for reference: $reference");

    // Update your database
    // Mark order as failed
    // Send notification
}

/**
 * Process expired session
 */
function processExpiredSession(array $session): void
{
    $reference = $session['reference'];

    error_log("Processing expired session for reference: $reference");

    // Update your database
    // Mark order as expired
}

/**
 * Process active agreement
 */
function processActiveAgreement(array $agreement): void
{
    $agreementId = $agreement['id'];

    error_log("Processing active agreement: $agreementId");

    // Update your database
    // Activate subscription
    // Send welcome email
    // Grant access to service
}

/**
 * Process stopped agreement
 */
function processStoppedAgreement(array $agreement): void
{
    $agreementId = $agreement['id'];

    error_log("Processing stopped agreement: $agreementId");

    // Update your database
    // Deactivate subscription
    // Revoke access
}

/**
 * Process expired agreement
 */
function processExpiredAgreement(array $agreement): void
{
    $agreementId = $agreement['id'];

    error_log("Processing expired agreement: $agreementId");

    // Update your database
    // Handle expiration
}
