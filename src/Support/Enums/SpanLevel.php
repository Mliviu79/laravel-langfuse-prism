<?php

declare(strict_types=1);

namespace Langfuse\Support\Enums;

enum SpanLevel: string
{
    case DEBUG = 'DEBUG';
    case DEFAULT = 'DEFAULT';
    case INFO = 'INFO';
    case WARNING = 'WARNING';
    case ERROR = 'ERROR';

    /**
     * Get the numeric priority of this level (higher = more severe)
     */
    public function getPriority(): int
    {
        return match ($this) {
            self::DEBUG => 0,
            self::DEFAULT => 1,
            self::INFO => 2,
            self::WARNING => 3,
            self::ERROR => 4,
        };
    }

    /**
     * Check if this level indicates an error condition
     */
    public function isError(): bool
    {
        return $this === self::ERROR;
    }

    /**
     * Check if this level indicates a warning or error condition
     */
    public function isWarningOrError(): bool
    {
        return in_array($this, [self::WARNING, self::ERROR], true);
    }

    /**
     * Get the PSR-3 log level equivalent
     */
    public function toPsrLogLevel(): string
    {
        return match ($this) {
            self::DEBUG => 'debug',
            self::DEFAULT => 'info',
            self::INFO => 'info',
            self::WARNING => 'warning',
            self::ERROR => 'error',
        };
    }

    /**
     * Create from PSR-3 log level
     */
    public static function fromPsrLogLevel(string $level): self
    {
        return match (strtolower($level)) {
            'debug' => self::DEBUG,
            'info' => self::INFO,
            'warning', 'warn' => self::WARNING,
            'error' => self::ERROR,
            default => self::DEFAULT,
        };
    }

    /**
     * Get a human-readable description of the level
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::DEBUG => 'Debug information for troubleshooting',
            self::DEFAULT => 'Default level for normal operations',
            self::INFO => 'Informational messages about normal operations',
            self::WARNING => 'Warning conditions that should be noted',
            self::ERROR => 'Error conditions that need attention',
        };
    }
}