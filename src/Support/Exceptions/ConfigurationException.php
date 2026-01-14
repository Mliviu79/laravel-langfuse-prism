<?php

declare(strict_types=1);

namespace Langfuse\Support\Exceptions;

/**
 * Exception thrown when configuration is invalid
 */
class ConfigurationException extends LangfuseException
{
    public static function missingRequiredValue(string $key): self
    {
        return new self("Required configuration value '{$key}' is missing or empty.");
    }

    public static function invalidValue(string $key, string $reason): self
    {
        return new self("Invalid configuration value for '{$key}': {$reason}");
    }

    public static function invalidUrl(string $url): self
    {
        return new self("Invalid URL: {$url}");
    }

    public static function invalidSampleRate(float $rate): self
    {
        return new self("Sample rate must be between 0.0 and 1.0, got: {$rate}");
    }

    public static function invalidEnvironmentName(string $environment): self
    {
        return new self("Invalid environment name: {$environment}. Must be lowercase alphanumeric with hyphens/underscores and not start with 'langfuse'.");
    }
}
