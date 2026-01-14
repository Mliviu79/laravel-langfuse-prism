<?php

declare(strict_types=1);

namespace Langfuse\Integration\Laravel\DTOs;

use Langfuse\OpenTelemetry\DTOs\OpenTelemetryConfigDto;

/**
 * Data Transfer Object for Langfuse status report.
 */
readonly class StatusReportDto
{
    /**
     * @param  string[]  $warnings
     */
    public function __construct(
        public bool $tracingEnabled,
        public string $environment,
        public float $sampleRate,
        public bool $hasPublicKey,
        public bool $hasSecretKey,
        public OpenTelemetryConfigDto $otelConfig,
        public string $phpSapi,
        public bool $runningInConsole,
        public array $warnings = [],
    ) {}

    /**
     * Check if credentials are fully configured.
     */
    public function hasFullCredentials(): bool
    {
        return $this->hasPublicKey && $this->hasSecretKey;
    }

    /**
     * Get the sample rate as a percentage.
     */
    public function getSampleRatePercentage(): float
    {
        return $this->sampleRate * 100;
    }

    /**
     * Get the processor type name.
     */
    public function getProcessorType(): string
    {
        return $this->otelConfig->useSimpleProcessor ? 'SimpleProcessor' : 'BatchProcessor';
    }

    /**
     * Get the processor type reason.
     */
    public function getProcessorReason(): string
    {
        return $this->otelConfig->useSimpleProcessor
            ? '(CLI/Queue mode - immediate export)'
            : '(HTTP mode - batched export)';
    }

    /**
     * Check if there are any warnings.
     */
    public function hasWarnings(): bool
    {
        return count($this->warnings) > 0;
    }
}
