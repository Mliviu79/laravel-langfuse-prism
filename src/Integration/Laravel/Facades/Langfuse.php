<?php

declare(strict_types=1);

namespace Langfuse\Integration\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use Langfuse\Client\Contracts\LangfuseClientInterface;
use Langfuse\Datasets\Dataset;
use Langfuse\Datasets\DatasetItem;
use Langfuse\Datasets\DatasetRun;
use Langfuse\Observability\Contracts\SpanInterface;
use Langfuse\Scoring\Score;
use Langfuse\Support\Enums\ObservationType;
use Langfuse\Support\Enums\SpanLevel;

/**
 * @method static SpanInterface startSpan(string $name, ObservationType $type = ObservationType::SPAN, mixed $input = null, mixed $output = null, array $metadata = null, ?string $version = null, ?SpanLevel $level = null, ?string $statusMessage = null, ?string $parentId = null)
 * @method static Score createScore(string $name, float|int|bool|string $value, ?string $traceId = null, ?string $observationId = null, ?string $scoreId = null, ?string $comment = null, ?string $configId = null)
 * @method static Dataset createDataset(string $name, ?string $description = null, array $metadata = [])
 * @method static Dataset|null getDataset(string $name)
 * @method static DatasetItem createDatasetItem(string $datasetName, mixed $input, mixed $expectedOutput = null, array $metadata = [], ?string $sourceTraceId = null, ?string $sourceObservationId = null)
 * @method static DatasetRun createDatasetRun(string $datasetName, ?string $name = null, ?string $description = null, array $metadata = [])
 * @method static void flush()
 * @method static void shutdown()
 *
 * @see LangfuseClientInterface
 */
class Langfuse extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'langfuse';
    }
}
