<?php

declare(strict_types=1);

namespace Coretrek\Vipps\Tests\Unit\Exceptions;

use Coretrek\Vipps\Exceptions\VippsException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class VippsExceptionTest extends TestCase
{
    public function testFromGuzzleException(): void
    {
        $request = new Request('GET', '/test');
        $response = new Response(404, [], json_encode([
            'title' => 'Not Found',
            'detail' => 'Resource not found',
        ]));

        $guzzleException = new RequestException(
            'Error',
            $request,
            $response
        );

        $exception = VippsException::fromGuzzleException($guzzleException);

        $this->assertInstanceOf(VippsException::class, $exception);
        $this->assertEquals('Not Found', $exception->getMessage());
        $this->assertEquals(404, $exception->getCode());
    }

    public function testFromResponse(): void
    {
        $response = new Response(400, [], json_encode([
            'title' => 'Bad Request',
            'detail' => 'Validation failed',
            'errorCode' => 'VALIDATION_ERROR',
        ]));

        $exception = VippsException::fromResponse($response);

        $this->assertEquals('Bad Request', $exception->getMessage());
        $this->assertEquals(400, $exception->getCode());
        $this->assertNotNull($exception->getResponse());
    }

    public function testGetErrorDetails(): void
    {
        $errorData = [
            'title' => 'Bad Request',
            'errorCode' => 'VALIDATION_ERROR',
            'errors' => [
                'amount' => ['Amount must be positive'],
            ],
        ];

        $response = new Response(400, [], json_encode($errorData));
        $exception = VippsException::fromResponse($response);

        $this->assertEquals($errorData, $exception->getErrorDetails());
    }

    public function testGetErrorField(): void
    {
        $response = new Response(400, [], json_encode([
            'title' => 'Bad Request',
            'errorCode' => 'VALIDATION_ERROR',
        ]));

        $exception = VippsException::fromResponse($response);

        $this->assertEquals('VALIDATION_ERROR', $exception->getErrorField('errorCode'));
        $this->assertNull($exception->getErrorField('nonexistent'));
    }

    public function testIsValidationError(): void
    {
        $response = new Response(400, [], json_encode(['title' => 'Bad Request']));
        $exception = VippsException::fromResponse($response);

        $this->assertTrue($exception->isValidationError());
        $this->assertFalse($exception->isAuthenticationError());
        $this->assertFalse($exception->isNotFoundError());
    }

    public function testIsAuthenticationError(): void
    {
        $response = new Response(401, [], json_encode(['title' => 'Unauthorized']));
        $exception = VippsException::fromResponse($response);

        $this->assertTrue($exception->isAuthenticationError());
        $this->assertFalse($exception->isValidationError());
        $this->assertFalse($exception->isNotFoundError());
    }

    public function testIsNotFoundError(): void
    {
        $response = new Response(404, [], json_encode(['title' => 'Not Found']));
        $exception = VippsException::fromResponse($response);

        $this->assertTrue($exception->isNotFoundError());
        $this->assertFalse($exception->isValidationError());
        $this->assertFalse($exception->isAuthenticationError());
    }

    public function testDefaultErrorMessages(): void
    {
        $testCases = [
            400 => 'Bad Request: Validation errors',
            401 => 'Unauthorized: Invalid credentials',
            403 => 'Forbidden: Invalid subscription or configuration',
            404 => 'Not Found: Resource not found',
            409 => 'Conflict: Duplicate reference or resource conflict',
            500 => 'Internal Server Error: Unexpected errors',
            502 => 'Bad Gateway: Unexpected errors in integrations',
            503 => 'HTTP Error 503',
        ];

        foreach ($testCases as $statusCode => $expectedMessage) {
            $response = new Response($statusCode, [], '{}');
            $exception = VippsException::fromResponse($response);

            $this->assertEquals($expectedMessage, $exception->getMessage());
        }
    }

    public function testErrorMessagePriority(): void
    {
        // Title takes priority
        $response = new Response(400, [], json_encode([
            'title' => 'Custom Title',
            'detail' => 'Custom Detail',
            'errorCode' => 'CUSTOM_ERROR',
        ]));

        $exception = VippsException::fromResponse($response);
        $this->assertEquals('Custom Title', $exception->getMessage());

        // Detail is used if no title
        $response = new Response(400, [], json_encode([
            'detail' => 'Custom Detail',
            'errorCode' => 'CUSTOM_ERROR',
        ]));

        $exception = VippsException::fromResponse($response);
        $this->assertEquals('Custom Detail', $exception->getMessage());

        // ErrorCode is used if no title or detail
        $response = new Response(400, [], json_encode([
            'errorCode' => 'CUSTOM_ERROR',
        ]));

        $exception = VippsException::fromResponse($response);
        $this->assertEquals('Error: CUSTOM_ERROR', $exception->getMessage());
    }
}
