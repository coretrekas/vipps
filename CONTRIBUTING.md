# Contributing to Vipps MobilePay PHP SDK

Thank you for your interest in contributing to the Vipps MobilePay PHP SDK! We welcome contributions from the community.

## Getting Started

1. Fork the repository
2. Clone your fork: `git clone https://github.com/coretrekas/vipps.git`
3. Install dependencies: `composer install`
4. Create a new branch: `git checkout -b feature/your-feature-name`

## Development Guidelines

### Code Style

This project follows PSR-12 coding standards using Laravel Pint. Before submitting a pull request:

```bash
# Check code style
composer pint:test

# Fix code style issues
composer pint
```

### Static Analysis

We use PHPStan for static analysis at level 5:

```bash
composer phpstan
```

### Testing

All new features and bug fixes must include tests:

```bash
# Run all tests
composer test

# Run tests with coverage
composer test:coverage
```

#### Writing Tests

- Place unit tests in `tests/Unit/`
- Place integration tests in `tests/Integration/`
- Aim for high test coverage
- Use descriptive test method names
- Follow the Arrange-Act-Assert pattern

Example:

```php
public function testCreatePaymentSession(): void
{
    // Arrange
    $client = $this->createMockClient([...]);
    
    // Act
    $result = $client->checkout()->createPaymentSession(...);
    
    // Assert
    $this->assertArrayHasKey('token', $result);
}
```

### Documentation

- Update README.md if you add new features
- Add PHPDoc comments to all public methods
- Include code examples for new features
- Update CHANGELOG.md following [Keep a Changelog](https://keepachangelog.com/) format

### Commit Messages

Write clear, descriptive commit messages:

```
Add support for dynamic logistics options

- Implement logistics builder methods
- Add tests for logistics configuration
- Update documentation with examples
```

## Pull Request Process

1. Ensure all tests pass
2. Update documentation as needed
3. Add an entry to CHANGELOG.md under "Unreleased"
4. Submit your pull request with a clear description of the changes
5. Wait for review and address any feedback

## Code Review

All submissions require review. We use GitHub pull requests for this purpose.

## Questions?

If you have questions, please:
- Check existing issues and pull requests
- Open a new issue for discussion

## License

By contributing, you agree that your contributions will be licensed under the MIT License.

