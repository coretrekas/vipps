# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.2.1] - 2025-11-17

### Added
- `getClientId()` method to VippsClient for accessing client ID
- `getClientSecret()` method to VippsClient for accessing client secret

### Fixed
- **Critical**: Fixed OAuth token exchange authentication in Login API
    - `exchangeCodeForTokens()` now uses HTTP Basic Authentication instead of Bearer token
    - `pollCibaToken()` now uses HTTP Basic Authentication instead of Bearer token
    - OAuth 2.0 token endpoints require `Authorization: Basic <base64(client_id:client_secret)>` header
    - Previously, these methods incorrectly used `Authorization: Bearer <vipps-api-token>` header
    - This caused Vipps to reject requests with `invalid_request` error
    - Both methods now bypass the SDK's `request()` method and use Guzzle directly with proper Basic Auth

## [1.2.0] - 2025-11-04

### Added
- Full support for ePayment API v1
    - Create payments with multiple user flows (WEB_REDIRECT, PUSH_MESSAGE, QR, NATIVE_REDIRECT)
    - Get payment details and status
    - Get payment event log
    - Capture payments (full or partial)
    - Refund payments (full or partial)
    - Cancel payments
    - Force approve payments (test environment only)
    - Fluent PaymentBuilder for easy payment creation
    - Support for payment receipts with order lines
    - Support for shipping options (fixed and dynamic)
    - Support for QR code payments with customizable formats
    - Support for customer identification (phone number, personal QR, token)
    - Support for user profile data collection
    - Support for industry-specific data (airline data)
    - Support for metadata
- 22 new unit tests for ePayment API (100% pass rate)
- Complete ePayment API documentation in README
- ePayment payment example with 11 different scenarios
- Helper methods: `createSimplePayment()`, `captureAmount()`, `refundAmount()`

### Changed
- Updated README with comprehensive ePayment API examples
- Updated package structure documentation to include ePayment API
- Updated feature list to include ePayment API v1

## [1.1.2] - 2025-10-28
- Added client id and client secret to request headers.
- Added system info headers (Vipps-System-Name, Vipps-System-Version, Vipps-System-Plugin-Name, Vipps-System-Plugin-Version) to all API requests
- System info can now be set via constructor options or setSystemInfo() method

## [1.0.0] - 2025-10-15

### Added
- Initial release of Vipps MobilePay PHP SDK
- Full support for Checkout API v3
    - Create payment sessions
    - Create subscription sessions
    - Get session information
    - Update sessions
    - Expire sessions
    - Fluent SessionBuilder for easy session creation
- Full support for Recurring API v3
    - Create, list, get, and update agreements
    - Create, list, get, cancel, capture, and refund charges
    - Support for multiple charge creation (async)
    - Fluent AgreementBuilder for easy agreement creation
    - Support for all pricing types (LEGACY, VARIABLE, FLEXIBLE)
    - Support for all campaign types (PRICE, PERIOD, EVENT)
- Automatic access token management with caching
- Comprehensive error handling with VippsException
- PSR-3 logger support
- PSR-18 HTTP client compatibility
- Full test coverage with PHPUnit
- Detailed documentation and examples
- Type-safe implementation with PHP 8.1+ strict types

### Features
- Production and test environment support
- Idempotency key support for safe retries
- System information headers for tracking
- Custom HTTP client support
- Custom logger support
- Detailed error messages with status codes
- Helper methods for common operations
- Builder pattern for complex requests

## [1.1.0] - 2025-10-15

### Added
- Full support for Login API v1 (OAuth 2.0 / OpenID Connect)
    - Authorization URL builder with fluent interface
    - Token exchange (authorization code flow)
    - User information retrieval
    - CIBA (Client Initiated Backchannel Authentication) support
    - User existence check
    - OpenID Connect discovery
    - JWKS (JSON Web Key Set) retrieval
    - PKCE (Proof Key for Code Exchange) support
    - State and nonce generation helpers
    - Code verifier generation
- 31 new unit tests for Login API (100% pass rate)
- Complete Login API documentation in README
- Login flow example with OAuth and CIBA flows
- Replaced PHP_CodeSniffer with Laravel Pint for code quality
- Updated namespace from `Coretrekas\Vipps` to `Coretrek\Vipps`

### Changed
- Updated test count: 89 tests with 187 assertions
- Improved code style with Laravel Pint (PSR-12 compliant)
- Enhanced README with Login API examples and documentation
- Updated package badges and links

### Fixed
- Code style issues across all files
- Import ordering (alphabetically sorted)

