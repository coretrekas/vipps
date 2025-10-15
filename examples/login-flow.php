<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Coretrek\Vipps\Exceptions\VippsException;
use Coretrek\Vipps\Login\AuthorizationUrlBuilder;
use Coretrek\Vipps\VippsClient;

/**
 * Example: Vipps Login Flow
 *
 * This example demonstrates the complete OAuth 2.0 / OpenID Connect login flow
 * with Vipps MobilePay.
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
    // ============================================================================
    // STEP 1: Generate Authorization URL
    // ============================================================================

    // Generate secure random values for state and nonce
    $state = AuthorizationUrlBuilder::generateState();
    $nonce = AuthorizationUrlBuilder::generateNonce();

    // Optional: Use PKCE for enhanced security
    $codeVerifier = AuthorizationUrlBuilder::generateCodeVerifier();

    // Build the authorization URL
    $authUrl = $client->login()
        ->buildAuthorizationUrl()
        ->clientId('your-client-id')
        ->redirectUri('https://example.com/vipps/callback')
        ->scope(['openid', 'name', 'email', 'phoneNumber', 'address'])
        ->state($state)
        ->nonce($nonce)
        ->pkce($codeVerifier, 'S256')
        ->uiLocales(['nb', 'en'])
        ->build();

    echo "Authorization URL:\n";
    echo $authUrl . "\n\n";

    // Store state, nonce, and code_verifier in session for verification
    // $_SESSION['oauth_state'] = $state;
    // $_SESSION['oauth_nonce'] = $nonce;
    // $_SESSION['oauth_code_verifier'] = $codeVerifier;

    // Redirect user to authorization URL
    // header('Location: ' . $authUrl);
    // exit;

    // ============================================================================
    // STEP 2: Handle Callback (after user authorizes)
    // ============================================================================

    // In your callback handler (e.g., /vipps/callback):
    // $code = $_GET['code'] ?? null;
    // $returnedState = $_GET['state'] ?? null;

    // Verify state to prevent CSRF attacks
    // if ($returnedState !== $_SESSION['oauth_state']) {
    //     throw new Exception('Invalid state parameter');
    // }

    // Example code for demonstration
    $code = 'example-authorization-code';

    // Exchange authorization code for tokens
    $tokens = $client->login()->exchangeCodeForTokens(
        code: $code,
        redirectUri: 'https://example.com/vipps/callback',
        options: [
            'code_verifier' => $codeVerifier, // Required if PKCE was used
        ]
    );

    echo "Tokens received:\n";
    echo 'Access Token: ' . substr($tokens['access_token'], 0, 20) . "...\n";
    echo 'ID Token: ' . substr($tokens['id_token'], 0, 20) . "...\n";
    echo 'Token Type: ' . $tokens['token_type'] . "\n";
    echo 'Expires In: ' . $tokens['expires_in'] . " seconds\n";
    echo 'Scope: ' . $tokens['scope'] . "\n\n";

    // ============================================================================
    // STEP 3: Get User Information
    // ============================================================================

    $userInfo = $client->login()->getUserInfo($tokens['access_token']);

    echo "User Information:\n";
    echo 'User ID (sub): ' . $userInfo['sub'] . "\n";
    echo 'Name: ' . ($userInfo['name'] ?? 'N/A') . "\n";
    echo 'Email: ' . ($userInfo['email'] ?? 'N/A') . "\n";
    echo 'Phone: ' . ($userInfo['phone_number'] ?? 'N/A') . "\n";

    if (isset($userInfo['address'])) {
        echo 'Address: ' . $userInfo['address']['formatted'] . "\n";
    }

    if (isset($userInfo['birthdate'])) {
        echo 'Birth Date: ' . $userInfo['birthdate'] . "\n";
    }

    echo "\n";

    // ============================================================================
    // ALTERNATIVE: CIBA Flow (Merchant-Initiated Login)
    // ============================================================================

    echo "=== CIBA Flow Example ===\n\n";

    // Check if user exists
    $userExists = $client->login()->checkUserExists('4712345678');
    echo 'User exists: ' . ($userExists['exists'] ? 'Yes' : 'No') . "\n\n";

    if ($userExists['exists']) {
        // Initiate CIBA authentication
        $cibaAuth = $client->login()->initiateCibaAuth(
            loginHint: '4712345678',
            options: [
                'scope' => 'openid name email',
                'bindingMessage' => 'Login to Example App',
                'requested_expiry' => 300,
            ]
        );

        echo "CIBA Authentication initiated:\n";
        echo 'Auth Request ID: ' . $cibaAuth['auth_req_id'] . "\n";
        echo 'Expires in: ' . $cibaAuth['expires_in'] . " seconds\n";
        echo 'Poll interval: ' . $cibaAuth['interval'] . " seconds\n\n";

        // Poll for token (in a real app, do this in a loop with proper interval)
        echo "Polling for token...\n";
        // sleep($cibaAuth['interval']);

        try {
            $cibaTokens = $client->login()->pollCibaToken($cibaAuth['auth_req_id']);

            echo "CIBA tokens received:\n";
            echo 'Access Token: ' . substr($cibaTokens['access_token'], 0, 20) . "...\n";
            echo 'ID Token: ' . substr($cibaTokens['id_token'], 0, 20) . "...\n";
        } catch (VippsException $e) {
            echo "Token not ready yet (user hasn't approved): " . $e->getMessage() . "\n";
        }
    }

    // ============================================================================
    // OpenID Configuration
    // ============================================================================

    echo "\n=== OpenID Configuration ===\n\n";

    $config = $client->login()->getOpenIdConfiguration();
    echo 'Issuer: ' . $config['issuer'] . "\n";
    echo 'Authorization Endpoint: ' . $config['authorization_endpoint'] . "\n";
    echo 'Token Endpoint: ' . $config['token_endpoint'] . "\n";
    echo 'Supported Scopes: ' . implode(', ', $config['scopes_supported']) . "\n\n";

    // Get JWKS for token verification
    $jwks = $client->login()->getJwks();
    echo 'Number of keys in JWKS: ' . count($jwks['keys']) . "\n";
} catch (VippsException $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
    echo 'Status Code: ' . $e->getStatusCode() . "\n";

    if ($e->getResponseBody()) {
        echo 'Response: ' . json_encode($e->getResponseBody(), JSON_PRETTY_PRINT) . "\n";
    }
}
