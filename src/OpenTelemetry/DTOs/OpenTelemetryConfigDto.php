<?php

declare(strict_types=1);

namespace Langfuse\OpenTelemetry\DTOs;

/**
 * Data Transfer Object for OpenTelemetry configuration
 */
readonly class OpenTelemetryConfigDto
{
    public function __construct(
        public string $endpoint,
        public string $protocol,
        public array $headers,
        public int $timeout,
        public int $maxQueueSize,
        public int $scheduledDelayMillis,
        public int $exportTimeoutMillis,
        public int $maxExportBatchSize,
        public bool $compression,
        public string $serviceName,
        public string $serviceVersion,
        public array $resourceAttributes,
        public bool $useSimpleProcessor,
    ) {}

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            endpoint: $data['endpoint'],
            protocol: $data['protocol'],
            headers: $data['headers'] ?? [],
            timeout: $data['timeout'],
            maxQueueSize: $data['maxQueueSize'],
            scheduledDelayMillis: $data['scheduledDelayMillis'],
            exportTimeoutMillis: $data['exportTimeoutMillis'],
            maxExportBatchSize: $data['maxExportBatchSize'],
            compression: $data['compression'] ?? false,
            serviceName: $data['serviceName'],
            serviceVersion: $data['serviceVersion'],
            resourceAttributes: $data['resourceAttributes'] ?? [],
            useSimpleProcessor: $data['useSimpleProcessor'] ?? false,
        );
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'endpoint' => $this->endpoint,
            'protocol' => $this->protocol,
            'headers' => $this->headers,
            'timeout' => $this->timeout,
            'maxQueueSize' => $this->maxQueueSize,
            'scheduledDelayMillis' => $this->scheduledDelayMillis,
            'exportTimeoutMillis' => $this->exportTimeoutMillis,
            'maxExportBatchSize' => $this->maxExportBatchSize,
            'compression' => $this->compression,
            'serviceName' => $this->serviceName,
            'serviceVersion' => $this->serviceVersion,
            'resourceAttributes' => $this->resourceAttributes,
            'useSimpleProcessor' => $this->useSimpleProcessor,
        ];
    }
}
