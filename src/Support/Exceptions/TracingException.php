<?php

declare(strict_types=1);

namespace Langfuse\Support\Exceptions;

use Throwable;

/**
 * Exception thrown when tracing operations fail
 */
class TracingException extends LangfuseException
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $context);
    }
}
