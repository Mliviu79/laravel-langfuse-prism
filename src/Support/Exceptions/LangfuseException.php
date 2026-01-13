<?php

declare(strict_types=1);

namespace Langfuse\Support\Exceptions;

use Exception;
use Throwable;

/**
 * Base exception class for all Langfuse-related exceptions
 */
class LangfuseException extends Exception
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        protected array $context = []
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get additional context information about the exception
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Add context information to the exception
     */
    public function withContext(array $context): self
    {
        $this->context = array_merge($this->context, $context);

        return $this;
    }
}