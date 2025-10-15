<?php

declare(strict_types=1);

namespace Coretrek\Vipps\Exceptions;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;

/**
 * Base exception for all Vipps SDK errors
 */
class VippsException extends Exception
{
    private ?ResponseInterface $response = null;
    private ?array $errorDetails = null;

    /**
     * Create exception from Guzzle exception
     */
    public static function fromGuzzleException(GuzzleException $e): self
    {
        if ($e instanceof RequestException && $e->hasResponse()) {
            $response = $e->getResponse();
            if ($response !== null) {
                return self::fromResponse($response, $e);
            }
        }

        return new self($e->getMessage(), $e->getCode(), $e);
    }

    /**
     * Create exception from HTTP response
     */
    public static function fromResponse(ResponseInterface $response, ?Exception $previous = null): self
    {
        $statusCode = $response->getStatusCode();
        $body = (string) $response->getBody();
        $data = json_decode($body, true);

        $message = self::getErrorMessage($statusCode, $data);

        $exception = new self($message, $statusCode, $previous);
        $exception->response = $response;
        $exception->errorDetails = $data;

        return $exception;
    }

    /**
     * Get error message from response data
     */
    private static function getErrorMessage(int $statusCode, ?array $data): string
    {
        if ($data) {
            if (isset($data['title'])) {
                return $data['title'];
            }
            if (isset($data['detail'])) {
                return $data['detail'];
            }
            if (isset($data['errorCode'])) {
                return 'Error: ' . $data['errorCode'];
            }
        }

        return match ($statusCode) {
            400 => 'Bad Request: Validation errors',
            401 => 'Unauthorized: Invalid credentials',
            403 => 'Forbidden: Invalid subscription or configuration',
            404 => 'Not Found: Resource not found',
            409 => 'Conflict: Duplicate reference or resource conflict',
            500 => 'Internal Server Error: Unexpected errors',
            502 => 'Bad Gateway: Unexpected errors in integrations',
            default => 'HTTP Error ' . $statusCode,
        };
    }

    /**
     * Get the HTTP response if available
     */
    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }

    /**
     * Get error details from the response
     */
    public function getErrorDetails(): ?array
    {
        return $this->errorDetails;
    }

    /**
     * Get specific error field
     */
    public function getErrorField(string $field): mixed
    {
        return $this->errorDetails[$field] ?? null;
    }

    /**
     * Check if this is a validation error
     */
    public function isValidationError(): bool
    {
        return $this->getCode() === 400;
    }

    /**
     * Check if this is an authentication error
     */
    public function isAuthenticationError(): bool
    {
        return $this->getCode() === 401;
    }

    /**
     * Check if this is a not found error
     */
    public function isNotFoundError(): bool
    {
        return $this->getCode() === 404;
    }
}
