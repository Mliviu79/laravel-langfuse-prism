<?php

declare(strict_types=1);

namespace Langfuse\Scoring\Enums;

enum ScoreDataType: string
{
    case NUMERIC = 'NUMERIC';
    case BOOLEAN = 'BOOLEAN';
    case CATEGORICAL = 'CATEGORICAL';

    /**
     * Get the expected PHP type for this score data type
     */
    public function getExpectedType(): string
    {
        return match ($this) {
            self::NUMERIC => 'float|int',
            self::BOOLEAN => 'bool',
            self::CATEGORICAL => 'string',
        };
    }

    /**
     * Validate that a value is compatible with this score data type
     */
    public function validateValue(mixed $value): bool
    {
        return match ($this) {
            self::NUMERIC => is_numeric($value),
            self::BOOLEAN => is_bool($value),
            self::CATEGORICAL => is_string($value),
        };
    }

    /**
     * Convert a value to the appropriate type for this score data type
     */
    public function castValue(mixed $value): float|bool|string
    {
        return match ($this) {
            self::NUMERIC => is_float($value) ? $value : (float) $value,
            self::BOOLEAN => (bool) $value,
            self::CATEGORICAL => (string) $value,
        };
    }

    /**
     * Get a human-readable description of the score data type
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::NUMERIC => 'Numeric score (integer or float)',
            self::BOOLEAN => 'Boolean score (true/false)',
            self::CATEGORICAL => 'Categorical score (string value)',
        };
    }
}