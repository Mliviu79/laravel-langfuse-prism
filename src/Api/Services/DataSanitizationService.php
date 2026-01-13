<?php

declare(strict_types=1);

namespace Langfuse\Api\Services;

/**
 * Service for sanitizing data for logging
 */
class DataSanitizationService
{
    /**
     * Sanitize data for logging (remove sensitive information)
     */
    public function sanitize(mixed $data): mixed
    {
        if (!is_array($data) && !is_string($data)) {
            return $data;
        }

        if (is_string($data)) {
            // Truncate very long strings
            return strlen($data) > 1000 ? substr($data, 0, 1000).'...[truncated]' : $data;
        }

        $sensitiveKeys = ['password', 'secret', 'token', 'key', 'authorization'];

        $sanitized = [];
        foreach ($data as $key => $value) {
            if (is_string($key)) {
                $lowerKey = strtolower($key);
                $shouldRedact = false;
                foreach ($sensitiveKeys as $sensitive) {
                    if (str_contains($lowerKey, $sensitive)) {
                        $shouldRedact = true;
                        break;
                    }
                }

                if ($shouldRedact) {
                    $sanitized[$key] = '[REDACTED]';
                    continue;
                }
            }

            if (is_array($value)) {
                $sanitized[$key] = $this->sanitize($value);
                continue;
            }

            $sanitized[$key] = $value;
        }

        return $sanitized;
    }
}
