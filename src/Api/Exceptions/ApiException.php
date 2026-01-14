<?php

declare(strict_types=1);

namespace Langfuse\Api\Exceptions;

use Langfuse\Support\Exceptions\LangfuseException;
use Throwable;

/**
 * Exception thrown when API requests fail
 */
class ApiException extends LangfuseException
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        array $context = [],
        protected ?int $httpStatusCode = null,
        protected ?string $httpResponseBody = null,
        protected array $httpHeaders = []
    ) {
        parent::__construct($message, $code, $previous, $context);
    }

    /**
     * Get the HTTP status code if available
     */
    public function getHttpStatusCode(): ?int
    {
        return $this->httpStatusCode;
    }

    /**
     * Get the HTTP response body if available
     */
    public function getHttpResponseBody(): ?string
    {
        return $this->httpResponseBody;
    }

    /**
     * Get the HTTP headers if available
     */
    public function getHttpHeaders(): array
    {
        return $this->httpHeaders;
    }

    /**
     * Create from HTTP response data
     */
    public static function fromHttpResponse(
        int $statusCode,
        string $responseBody,
        array $headers = [],
        ?string $message = null
    ): self {
        $message = $message ?? "API request failed with status {$statusCode}";

        return new self(
            message: $message,
            httpStatusCode: $statusCode,
            httpResponseBody: $responseBody,
            httpHeaders: $headers
        );
    }

    /**
     * Check if this is a client error (4xx)
     */
    public function isClientError(): bool
    {
        return $this->httpStatusCode >= 400 && $this->httpStatusCode < 500;
    }

    /**
     * Check if this is a server error (5xx)
     */
    public function isServerError(): bool
    {
        return $this->httpStatusCode >= 500 && $this->httpStatusCode < 600;
    }

    /**
     * Check if this is a rate limit error (429)
     */
    public function isRateLimitError(): bool
    {
        return $this->httpStatusCode === 429;
    }

    /**
     * Check if this is an authentication error (401)
     */
    public function isAuthenticationError(): bool
    {
        return $this->httpStatusCode === 401;
    }
}
