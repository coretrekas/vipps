# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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

## [Unreleased]

### Planned
- Support for additional Vipps MobilePay APIs
- Webhook signature verification helpers
- More comprehensive examples
- Performance optimizations

