<?php

declare(strict_types=1);

namespace Langfuse\Api\Exceptions;

use Throwable;

/**
 * Exception thrown when API request operations fail
 */
class ApiRequestException extends ApiException
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        array $context = [],
        ?int $httpStatusCode = null,
        ?string $httpResponseBody = null,
        array $httpHeaders = []
    ) {
        parent::__construct(
            $message,
            $code,
            $previous,
            $context,
            $httpStatusCode,
            $httpResponseBody,
            $httpHeaders
        );
    }
}
