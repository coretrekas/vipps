# Vipps MobilePay PHP SDK

[![Latest Version](https://img.shields.io/packagist/v/coretrek/vipps.svg)](https://packagist.org/packages/coretrek/vipps)
[![PHP Version](https://img.shields.io/packagist/php-v/coretrek/vipps.svg)](https://packagist.org/packages/coretrek/vipps)
[![License](https://img.shields.io/packagist/l/coretrek/vipps.svg)](https://packagist.org/packages/coretrek/vipps)

A comprehensive, production-ready PHP SDK for Vipps MobilePay APIs, providing easy integration with:
- **Checkout API v3** - Complete checkout sessions for payments and subscriptions
- **Recurring Payments API v3** - Recurring payment agreements and charges
- **Login API v1** - OAuth 2.0 / OpenID Connect authentication

## Features

- ✅ **Full API Coverage** - Complete support for Checkout API v3, Recurring Payments API v3, and Login API v1
- ✅ **Automatic Token Management** - Access tokens cached and refreshed automatically
- ✅ **Fluent Builders** - Easy-to-use builder interfaces for sessions, agreements, and authorization URLs
- ✅ **Type-Safe** - PHP 8.1+ with strict types and full type hints
- ✅ **Error Handling** - Comprehensive exception handling with detailed error information
- ✅ **PSR Compliant** - PSR-3 (Logger), PSR-4 (Autoloading), PSR-12 (Code Style), PSR-18 (HTTP Client)
- ✅ **Well Tested** - 89 unit tests with 187 assertions, 100% pass rate
- ✅ **Code Quality** - PHPStan level 5, Laravel Pint for code style
- ✅ **Environment Support** - Separate test and production configurations
- ✅ **Production Ready** - Comprehensive documentation and examples

## Requirements

- PHP 8.1 or higher
- ext-json

## Installation

Install via Composer:

```bash
composer require coretrekas/vipps
```

> **Note:** This package requires PHP 8.1 or higher.

## Package Structure

The SDK is organized under the `Coretrek\Vipps` namespace:

```
Coretrek\Vipps\
├── VippsClient                  # Main SDK client
├── Checkout\
│   ├── CheckoutApi              # Checkout API methods
│   └── SessionBuilder           # Fluent builder for sessions
├── Recurring\
│   ├── RecurringApi             # Recurring API methods
│   └── AgreementBuilder         # Fluent builder for agreements
├── Login\
│   ├── LoginApi                 # Login API methods
│   └── AuthorizationUrlBuilder  # Fluent builder for OAuth URLs
└── Exceptions\
    └── VippsException           # Exception handling
```

All classes use the `Coretrek\Vipps` namespace prefix.

## Quick Start

### Initialize the Client

```php
use Coretrek\Vipps\VippsClient;

$client = new VippsClient(
    clientId: 'your-client-id',
    clientSecret: 'your-client-secret',
    subscriptionKey: 'your-subscription-key',
    merchantSerialNumber: 'your-msn',
    testMode: true // Set to false for production
);
```

### Create a Payment Session (Checkout API)

```php
// Simple payment session
$session = $client->checkout()->createPaymentSession(
    reference: 'order-12345',
    amount: 10000, // Amount in minor units (100.00 NOK)
    currency: 'NOK',
    options: [
        'paymentDescription' => 'Order #12345',
        'merchantInfo' => [
            'callbackUrl' => 'https://example.com/vipps/callback',
            'returnUrl' => 'https://example.com/order/12345/complete',
            'termsAndConditionsUrl' => 'https://example.com/terms',
        ],
    ]
);

// Redirect user to checkout
header('Location: ' . $session['checkoutFrontendUrl']);
```

### Create a Payment Session with Builder (Recommended)

```php
$session = $client->checkout()
    ->buildPaymentSession()
    ->reference('order-12345')
    ->transaction(10000, 'NOK', 'order-12345', 'Order #12345')
    ->merchantInfo(
        callbackUrl: 'https://example.com/vipps/callback',
        returnUrl: 'https://example.com/order/12345/complete',
        termsAndConditionsUrl: 'https://example.com/terms',
        callbackAuthorizationToken: 'your-secret-token'
    )
    ->prefillCustomer([
        'firstName' => 'John',
        'lastName' => 'Doe',
        'email' => 'john@example.com',
        'phoneNumber' => '+4712345678',
    ])
    ->customerInteraction('CUSTOMER_NOT_PRESENT')
    ->elements('Full')
    ->countries(['NO', 'SE', 'DK'])
    ->idempotencyKey('unique-key-' . time())
    ->systemInfo('my-ecommerce', '1.0.0', 'vipps-plugin', '2.0.0')
    ->create();

echo "Checkout URL: " . $session['checkoutFrontendUrl'];
```

### Get Session Information

```php
$sessionInfo = $client->checkout()->getSession('order-12345');

echo "Session State: " . $sessionInfo['sessionState'];
echo "Payment Method: " . $sessionInfo['paymentMethod'];
```

### Create a Recurring Agreement

```php
$agreement = $client->recurring()
    ->buildAgreement()
    ->legacyPricing(2500, 'NOK') // 25.00 NOK per interval
    ->interval('MONTH', 1)
    ->product('Premium Subscription', 'Access to premium features')
    ->merchantUrls(
        redirectUrl: 'https://example.com/subscription/complete',
        agreementUrl: 'https://example.com/my-subscriptions'
    )
    ->phoneNumber('4712345678')
    ->initialCharge(100, 'NOK', 'Activation fee', 'DIRECT_CAPTURE')
    ->idempotencyKey('agreement-' . time())
    ->create();

// Redirect user to accept agreement
header('Location: ' . $agreement['vippsConfirmationUrl']);
```

### List Agreements

```php
$agreements = $client->recurring()->listAgreements([
    'status' => 'ACTIVE',
    'pageNumber' => 1,
    'pageSize' => 50,
]);

foreach ($agreements as $agreement) {
    echo "Agreement ID: " . $agreement['id'] . "\n";
    echo "Product: " . $agreement['productName'] . "\n";
    echo "Status: " . $agreement['status'] . "\n";
}
```

### Create a Charge

```php
$charge = $client->recurring()->createCharge(
    agreementId: 'agr_5kSeqz',
    chargeData: [
        'amount' => 2500,
        'transactionType' => 'DIRECT_CAPTURE',
        'description' => 'Monthly subscription - January 2024',
        'due' => '2024-01-01',
        'retryDays' => 5,
        'type' => 'RECURRING',
    ],
    headers: ['Idempotency-Key' => 'charge-jan-2024']
);

echo "Charge ID: " . $charge['chargeId'];
```

### Capture a Reserved Charge

```php
$client->recurring()->captureCharge(
    agreementId: 'agr_5kSeqz',
    chargeId: 'chr_123',
    captureData: [
        'amount' => 2500,
        'description' => 'Capture for January',
    ]
);
```

### Refund a Charge

```php
$client->recurring()->refundCharge(
    agreementId: 'agr_5kSeqz',
    chargeId: 'chr_123',
    refundData: [
        'amount' => 2500,
        'description' => 'Customer requested refund',
    ]
);
```

## Advanced Usage

### Custom HTTP Client

You can provide your own PSR-18 compatible HTTP client:

```php
use GuzzleHttp\Client;

$httpClient = new Client([
    'timeout' => 60,
    'verify' => true,
]);

$client = new VippsClient(
    clientId: 'your-client-id',
    clientSecret: 'your-client-secret',
    subscriptionKey: 'your-subscription-key',
    merchantSerialNumber: 'your-msn',
    testMode: true,
    options: ['http_client' => $httpClient]
);
```

### Custom Logger

Integrate with your PSR-3 compatible logger:

```php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$logger = new Logger('vipps');
$logger->pushHandler(new StreamHandler('path/to/vipps.log', Logger::DEBUG));

$client = new VippsClient(
    clientId: 'your-client-id',
    clientSecret: 'your-client-secret',
    subscriptionKey: 'your-subscription-key',
    merchantSerialNumber: 'your-msn',
    testMode: true,
    options: ['logger' => $logger]
);
```

### Error Handling

```php
use Coretrek\Vipps\Exceptions\VippsException;

try {
    $session = $client->checkout()->createPaymentSession(
        reference: 'order-12345',
        amount: 10000,
        currency: 'NOK'
    );
} catch (VippsException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Status Code: " . $e->getCode() . "\n";
    
    // Check error type
    if ($e->isValidationError()) {
        echo "Validation error occurred\n";
        print_r($e->getErrorDetails());
    }
    
    if ($e->isAuthenticationError()) {
        echo "Authentication failed - check your credentials\n";
    }
    
    if ($e->isNotFoundError()) {
        echo "Resource not found\n";
    }
}
```

## Checkout API Examples

### Payment with Logistics Options

```php
$session = $client->checkout()
    ->buildPaymentSession()
    ->reference('order-12345')
    ->transaction(10000, 'NOK', 'order-12345', 'Order with shipping')
    ->merchantInfo(
        'https://example.com/callback',
        'https://example.com/return',
        'https://example.com/terms'
    )
    ->logistics([
        'fixedOptions' => [
            [
                'brand' => 'POSTEN',
                'amount' => ['value' => 300, 'currency' => 'NOK'],
                'id' => 'posten-home',
                'priority' => 1,
                'isDefault' => true,
                'description' => 'Home delivery',
            ],
            [
                'brand' => 'POSTEN',
                'amount' => ['value' => 200, 'currency' => 'NOK'],
                'type' => 'PICKUP_POINT',
                'id' => 'posten-pickup',
                'priority' => 2,
                'isDefault' => false,
                'description' => 'Pickup point',
            ],
        ],
    ])
    ->create();
```

### Subscription Session

```php
$session = $client->checkout()
    ->buildSubscriptionSession()
    ->reference('sub-12345')
    ->transaction(100, 'NOK', 'sub-12345', 'Initial charge')
    ->subscription([
        'productName' => 'Premium Membership',
        'amount' => ['value' => 2500, 'currency' => 'NOK'],
        'interval' => ['unit' => 'MONTH', 'count' => 1],
        'merchantAgreementUrl' => 'https://example.com/my-subscriptions',
        'productDescription' => 'Monthly premium membership',
    ])
    ->merchantInfo(
        'https://example.com/callback',
        'https://example.com/return',
        'https://example.com/terms'
    )
    ->create();
```

## Recurring API Examples

### Agreement with Campaign

```php
// Price campaign - reduced price until a date
$agreement = $client->recurring()
    ->buildAgreement()
    ->legacyPricing(3900, 'NOK')
    ->interval('MONTH', 1)
    ->product('News Subscription')
    ->merchantUrls('https://example.com/redirect', 'https://example.com/manage')
    ->phoneNumber('4712345678')
    ->priceCampaign(100, '2024-12-31T23:59:59Z') // 1 NOK until end of year
    ->create();

// Period campaign - fixed price for a period
$agreement = $client->recurring()
    ->buildAgreement()
    ->legacyPricing(3900, 'NOK')
    ->interval('MONTH', 1)
    ->product('News Subscription')
    ->merchantUrls('https://example.com/redirect', 'https://example.com/manage')
    ->phoneNumber('4712345678')
    ->periodCampaign(100, 'WEEK', 4) // 1 NOK for 4 weeks
    ->initialCharge(100, 'NOK', 'Campaign activation', 'DIRECT_CAPTURE')
    ->create();
```

### Variable Amount Agreement

```php
$agreement = $client->recurring()
    ->buildAgreement()
    ->variablePricing(5000, 'NOK') // User can be charged up to 50 NOK
    ->interval('MONTH', 1)
    ->product('Usage-based Service')
    ->merchantUrls('https://example.com/redirect', 'https://example.com/manage')
    ->phoneNumber('4712345678')
    ->create();
```

### Create Multiple Charges Asynchronously

```php
$charges = [
    [
        'agreementId' => 'agr_123',
        'amount' => 2500,
        'transactionType' => 'DIRECT_CAPTURE',
        'description' => 'January charge',
        'due' => '2024-01-01',
        'retryDays' => 5,
        'type' => 'RECURRING',
    ],
    [
        'agreementId' => 'agr_456',
        'amount' => 2500,
        'transactionType' => 'DIRECT_CAPTURE',
        'description' => 'January charge',
        'due' => '2024-01-01',
        'retryDays' => 5,
        'type' => 'RECURRING',
    ],
];

$result = $client->recurring()->createChargesAsync($charges);
```

## Login API Examples

### OAuth 2.0 / OpenID Connect Flow

```php
use Coretrek\Vipps\Login\AuthorizationUrlBuilder;

// Generate secure random values
$state = AuthorizationUrlBuilder::generateState();
$nonce = AuthorizationUrlBuilder::generateNonce();
$codeVerifier = AuthorizationUrlBuilder::generateCodeVerifier();

// Build authorization URL
$authUrl = $client->login()
    ->buildAuthorizationUrl()
    ->clientId('your-client-id')
    ->redirectUri('https://example.com/vipps/callback')
    ->scope(['openid', 'name', 'email', 'phoneNumber', 'address'])
    ->state($state)
    ->nonce($nonce)
    ->pkce($codeVerifier, 'S256')
    ->build();

// Store state, nonce, and code_verifier in session
$_SESSION['oauth_state'] = $state;
$_SESSION['oauth_nonce'] = $nonce;
$_SESSION['oauth_code_verifier'] = $codeVerifier;

// Redirect user to Vipps login
header('Location: ' . $authUrl);
```

### Handle OAuth Callback

```php
// In your callback handler
$code = $_GET['code'];
$returnedState = $_GET['state'];

// Verify state to prevent CSRF
if ($returnedState !== $_SESSION['oauth_state']) {
    throw new Exception('Invalid state');
}

// Exchange code for tokens
$tokens = $client->login()->exchangeCodeForTokens(
    code: $code,
    redirectUri: 'https://example.com/vipps/callback',
    options: [
        'code_verifier' => $_SESSION['oauth_code_verifier'],
    ]
);

// Get user information
$userInfo = $client->login()->getUserInfo($tokens['access_token']);

echo "Welcome, " . $userInfo['name'];
echo "Email: " . $userInfo['email'];
echo "Phone: " . $userInfo['phone_number'];
```

### CIBA Flow (Merchant-Initiated Login)

```php
// Check if user exists
$userExists = $client->login()->checkUserExists('4712345678');

if ($userExists['exists']) {
    // Initiate authentication
    $auth = $client->login()->initiateCibaAuth(
        loginHint: '4712345678',
        options: [
            'scope' => 'openid name email',
            'bindingMessage' => 'Login to Example App',
            'requested_expiry' => 300,
        ]
    );

    // Poll for token (with proper interval)
    $interval = $auth['interval'];
    $authReqId = $auth['auth_req_id'];

    while (true) {
        sleep($interval);

        try {
            $tokens = $client->login()->pollCibaToken($authReqId);
            // User has authenticated
            break;
        } catch (VippsException $e) {
            // Still waiting for user to approve
            continue;
        }
    }

    $userInfo = $client->login()->getUserInfo($tokens['access_token']);
}
```

### Get OpenID Configuration

```php
// Get OpenID Connect discovery document
$config = $client->login()->getOpenIdConfiguration();

echo "Issuer: " . $config['issuer'];
echo "Authorization Endpoint: " . $config['authorization_endpoint'];
echo "Supported Scopes: " . implode(', ', $config['scopes_supported']);

// Get JWKS for token verification
$jwks = $client->login()->getJwks();
```

## Testing

### Running Tests

```bash
# Run all tests
composer test

# Run only unit tests
composer test:unit

# Run only integration tests
composer test:integration

# Run with coverage report
composer test:coverage
```

### Code Quality

```bash
# Run static analysis with PHPStan (level 5)
composer phpstan

# Check code style with Laravel Pint
composer pint:test

# Fix code style issues automatically
composer pint
```

### Integration Tests

Integration tests require valid Vipps test environment credentials. Set these environment variables:

```bash
export VIPPS_CLIENT_ID="your-test-client-id"
export VIPPS_CLIENT_SECRET="your-test-client-secret"
export VIPPS_SUBSCRIPTION_KEY="your-test-subscription-key"
export VIPPS_MERCHANT_SERIAL_NUMBER="your-test-msn"

# Run integration tests
composer test:integration
```

> **Note:** Integration tests are skipped by default if environment variables are not set.

## API Documentation

For detailed API documentation, visit:
- [Checkout API Guide](https://developer.vippsmobilepay.com/docs/APIs/checkout-api/)
- [Recurring API Guide](https://developer.vippsmobilepay.com/docs/APIs/recurring-api/)
- [Login API Guide](https://developer.vippsmobilepay.com/docs/APIs/login-api/)

## Support

- **Issues**: [GitHub Issues](https://github.com/coretrek/vipps/issues)

## Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

### Development Setup

```bash
# Clone the repository
git clone https://github.com/coretrekas/vipps.git
cd vipps

# Install dependencies
composer install

# Run tests
composer test

# Check code quality
composer phpstan
composer pint:test
```

## License

This SDK is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history.

