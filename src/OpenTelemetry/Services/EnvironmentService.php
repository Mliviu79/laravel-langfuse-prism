<?php

declare(strict_types=1);

namespace Langfuse\OpenTelemetry\Services;

/**
 * Service for accessing environment variables
 */
class EnvironmentService
{
    /**
     * Get environment variable value with default
     */
    public function getEnvValue(string $key, ?string $default = null): ?string
    {
        $value = $_ENV[$key] ?? getenv($key) ?: $default;

        return $value === '' ? null : $value;
    }

    /**
     * Get boolean environment variable value
     */
    public function getBoolEnvValue(string $key, bool $default): bool
    {
        $value = $this->getEnvValue($key);

        if ($value === null) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Check if running in console (CLI/queue worker)
     */
    public function isRunningInConsole(): bool
    {
        // Check if running in CLI mode
        if (php_sapi_name() === 'cli') {
            return true;
        }

        // Laravel-specific check
        if (function_exists('app') && app()->bound('Illuminate\Contracts\Console\Kernel')) {
            try {
                return app()->runningInConsole();
            } catch (\Throwable) {
                // Ignore if Laravel not available
            }
        }

        return false;
    }
}
