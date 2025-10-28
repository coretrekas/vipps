# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.1] - 2025-10-28
- Added client id and client secret to request headers.

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

## [Unreleased]

### Planned
- Webhook signature verification helpers
- More comprehensive examples
- Performance optimizations

