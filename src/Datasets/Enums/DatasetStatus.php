<?php

declare(strict_types=1);

namespace Langfuse\Datasets\Enums;

enum DatasetStatus: string
{
    case ACTIVE = 'ACTIVE';
    case ARCHIVED = 'ARCHIVED';

    /**
     * Check if the dataset is active and can be used
     */
    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Check if the dataset is archived
     */
    public function isArchived(): bool
    {
        return $this === self::ARCHIVED;
    }

    /**
     * Get a human-readable description of the status
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::ACTIVE => 'Dataset is active and available for use',
            self::ARCHIVED => 'Dataset is archived and read-only',
        };
    }
}