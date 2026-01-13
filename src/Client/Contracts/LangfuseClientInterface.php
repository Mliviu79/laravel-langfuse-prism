<?php

declare(strict_types=1);

namespace Langfuse\Client\Contracts;

use Langfuse\Datasets\Dataset;
use Langfuse\Datasets\DatasetItem;
use Langfuse\Datasets\DatasetRun;
use Langfuse\Observability\Contracts\SpanInterface;
use Langfuse\Scoring\Score;
use Langfuse\Support\Enums\ObservationType;
use Langfuse\Support\Enums\SpanLevel;

interface LangfuseClientInterface
{
    /**
     * Start a new span
     */
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
    ): SpanInterface;

    /**
     * Create a score for a trace or observation
     */
    public function createScore(
        string $name,
        float|int|bool|string $value,
        ?string $traceId = null,
        ?string $observationId = null,
        ?string $scoreId = null,
        ?string $comment = null,
        ?string $configId = null,
    ): Score;

    /**
     * Create a new dataset
     */
    public function createDataset(
        string $name,
        ?string $description = null,
        array $metadata = [],
    ): Dataset;

    /**
     * Get a dataset by name
     */
    public function getDataset(string $name): ?Dataset;

    /**
     * Add an item to a dataset
     */
    public function createDatasetItem(
        string $datasetName,
        mixed $input,
        mixed $expectedOutput = null,
        array $metadata = [],
        ?string $sourceTraceId = null,
        ?string $sourceObservationId = null,
    ): DatasetItem;

    /**
     * Create a dataset run
     */
    public function createDatasetRun(
        string $datasetName,
        ?string $name = null,
        ?string $description = null,
        array $metadata = [],
    ): DatasetRun;

    /**
     * Flush any pending operations
     */
    public function flush(): void;

    /**
     * Shutdown the client gracefully
     */
    public function shutdown(): void;
}
