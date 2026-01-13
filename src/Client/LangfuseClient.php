<?php

declare(strict_types=1);

namespace Langfuse\Client;

use Langfuse\Api\Contracts\ApiClientInterface;
use Langfuse\Client\Contracts\LangfuseClientInterface;
use Langfuse\Client\Services\DatasetService;
use Langfuse\Client\Services\ScoreService;
use Langfuse\Client\Services\TracingService;
use Langfuse\Datasets\Dataset;
use Langfuse\Datasets\DatasetItem;
use Langfuse\Datasets\DatasetRun;
use Langfuse\Observability\Contracts\SpanInterface;
use Langfuse\Scoring\Score;
use Langfuse\Support\Enums\ObservationType;
use Langfuse\Support\Enums\SpanLevel;

/**
 * Main Langfuse client - thin facade that delegates to services
 */
class LangfuseClient implements LangfuseClientInterface
{
    public function __construct(
        private readonly Configuration $config,
        private readonly ApiClientInterface $apiClient,
        private readonly TracingService $tracingService,
        private readonly DatasetService $datasetService,
        private readonly ScoreService $scoreService,
    ) {
    }

    public function startSpan(
        string $name,
        ObservationType $type = ObservationType::SPAN,
        mixed $input = null,
        mixed $output = null,
        ?array $metadata = null,
        ?string $version = null,
        ?SpanLevel $level = null,
        ?string $statusMessage = null,
        ?string $parentId = null,
        ?string $model = null,
    ): SpanInterface {
        return $this->tracingService->startSpan(
            name: $name,
            type: $type,
            input: $input,
            output: $output,
            metadata: $metadata,
            version: $version,
            level: $level,
            statusMessage: $statusMessage,
            parentId: $parentId,
            model: $model,
        );
    }

    public function createScore(
        string $name,
        float|int|bool|string $value,
        ?string $traceId = null,
        ?string $observationId = null,
        ?string $scoreId = null,
        ?string $comment = null,
        ?string $configId = null,
    ): Score {
        return $this->scoreService->createScore(
            name: $name,
            value: $value,
            traceId: $traceId,
            observationId: $observationId,
            scoreId: $scoreId,
            comment: $comment,
            configId: $configId,
        );
    }

    public function createDataset(
        string $name,
        ?string $description = null,
        array $metadata = [],
    ): Dataset {
        return $this->datasetService->createDataset(
            name: $name,
            description: $description,
            metadata: $metadata,
        );
    }

    public function getDataset(string $name): ?Dataset
    {
        return $this->datasetService->getDataset($name);
    }

    public function createDatasetItem(
        string $datasetName,
        mixed $input,
        mixed $expectedOutput = null,
        array $metadata = [],
        ?string $sourceTraceId = null,
        ?string $sourceObservationId = null,
    ): DatasetItem {
        return $this->datasetService->createDatasetItem(
            datasetName: $datasetName,
            input: $input,
            expectedOutput: $expectedOutput,
            metadata: $metadata,
            sourceTraceId: $sourceTraceId,
            sourceObservationId: $sourceObservationId,
        );
    }

    public function createDatasetRun(
        string $datasetName,
        ?string $name = null,
        ?string $description = null,
        array $metadata = [],
    ): DatasetRun {
        return $this->datasetService->createDatasetRun(
            datasetName: $datasetName,
            name: $name,
            description: $description,
            metadata: $metadata,
        );
    }

    public function flush(): void
    {
        $this->tracingService->flush();
    }

    public function shutdown(): void
    {
        $this->tracingService->shutdown();
    }
}
