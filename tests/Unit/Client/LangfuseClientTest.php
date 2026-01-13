<?php

declare(strict_types=1);

namespace Langfuse\Tests\Unit\Client;

use Langfuse\Api\Contracts\ApiClientInterface;
use Langfuse\Client\Configuration;
use Langfuse\Client\LangfuseClient;
use Langfuse\Client\Services\DatasetService;
use Langfuse\Client\Services\ScoreService;
use Langfuse\Client\Services\TracingService;
use Langfuse\Datasets\Dataset;
use Langfuse\Datasets\DatasetItem;
use Langfuse\Datasets\DatasetRun;
use Langfuse\Observability\Spans\NullSpan;
use Langfuse\Scoring\Score;
use Langfuse\Support\Enums\ObservationType;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class LangfuseClientTest extends TestCase
{
    private Configuration $config;
    private MockInterface $apiClient;
    private MockInterface $tracingService;
    private MockInterface $datasetService;
    private MockInterface $scoreService;
    private LangfuseClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = new Configuration(
            publicKey: 'test-key',
            secretKey: 'test-secret',
            tracingEnabled: true,
            sampleRate: 1.0
        );

        $this->apiClient = Mockery::mock(ApiClientInterface::class);
        $this->tracingService = Mockery::mock(TracingService::class);
        $this->datasetService = Mockery::mock(DatasetService::class);
        $this->scoreService = Mockery::mock(ScoreService::class);

        $this->client = new LangfuseClient(
            config: $this->config,
            apiClient: $this->apiClient,
            tracingService: $this->tracingService,
            datasetService: $this->datasetService,
            scoreService: $this->scoreService,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_start_span_delegates_to_tracing_service(): void
    {
        $expectedSpan = new NullSpan();
        
        $this->tracingService->shouldReceive('startSpan')
            ->once()
            ->with(
                'test-span',
                ObservationType::SPAN,
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                null
            )
            ->andReturn($expectedSpan);

        $result = $this->client->startSpan('test-span');

        $this->assertSame($expectedSpan, $result);
    }

    public function test_start_span_passes_all_parameters(): void
    {
        $expectedSpan = new NullSpan();
        $metadata = ['key' => 'value'];
        
        $this->tracingService->shouldReceive('startSpan')
            ->once()
            ->withArgs(function ($name, $type, $input, $output, $meta) use ($metadata) {
                return $name === 'test-span'
                    && $type === ObservationType::GENERATION
                    && $input === ['prompt' => 'test']
                    && $output === ['response' => 'test']
                    && $meta === $metadata;
            })
            ->andReturn($expectedSpan);

        $result = $this->client->startSpan(
            name: 'test-span',
            type: ObservationType::GENERATION,
            input: ['prompt' => 'test'],
            output: ['response' => 'test'],
            metadata: $metadata
        );

        $this->assertSame($expectedSpan, $result);
    }

    public function test_create_score_delegates_to_score_service(): void
    {
        $expectedScore = new Score(
            id: 'score-id',
            name: 'accuracy',
            value: 0.95,
            traceId: 'trace-id'
        );

        $this->scoreService->shouldReceive('createScore')
            ->once()
            ->with('accuracy', 0.95, 'trace-id', null, null, null, null)
            ->andReturn($expectedScore);

        $result = $this->client->createScore('accuracy', 0.95, 'trace-id');

        $this->assertSame($expectedScore, $result);
    }

    public function test_create_dataset_delegates_to_dataset_service(): void
    {
        $expectedDataset = new Dataset(
            id: 'dataset-id',
            name: 'test-dataset',
            description: 'Test description',
            metadata: []
        );

        $this->datasetService->shouldReceive('createDataset')
            ->once()
            ->with('test-dataset', 'Test description', [])
            ->andReturn($expectedDataset);

        $result = $this->client->createDataset('test-dataset', 'Test description');

        $this->assertSame($expectedDataset, $result);
    }

    public function test_get_dataset_delegates_to_dataset_service(): void
    {
        $expectedDataset = new Dataset(
            id: 'dataset-id',
            name: 'test-dataset'
        );

        $this->datasetService->shouldReceive('getDataset')
            ->once()
            ->with('test-dataset')
            ->andReturn($expectedDataset);

        $result = $this->client->getDataset('test-dataset');

        $this->assertSame($expectedDataset, $result);
    }

    public function test_create_dataset_item_delegates_to_dataset_service(): void
    {
        $expectedItem = new DatasetItem(
            id: 'item-id',
            datasetId: 'dataset-id',
            datasetName: 'test-dataset',
            input: ['prompt' => 'test']
        );

        $this->datasetService->shouldReceive('createDatasetItem')
            ->once()
            ->with('test-dataset', ['prompt' => 'test'], null, [], null, null)
            ->andReturn($expectedItem);

        $result = $this->client->createDatasetItem('test-dataset', ['prompt' => 'test']);

        $this->assertSame($expectedItem, $result);
    }

    public function test_create_dataset_run_delegates_to_dataset_service(): void
    {
        $expectedRun = new DatasetRun(
            id: 'run-id',
            name: 'test-run',
            datasetId: 'dataset-id',
            datasetName: 'test-dataset'
        );

        $this->datasetService->shouldReceive('createDatasetRun')
            ->once()
            ->with('test-dataset', 'test-run', null, [])
            ->andReturn($expectedRun);

        $result = $this->client->createDatasetRun('test-dataset', 'test-run');

        $this->assertSame($expectedRun, $result);
    }

    public function test_flush_delegates_to_tracing_service(): void
    {
        $this->tracingService->shouldReceive('flush')
            ->once()
            ->andReturnNull();

        $this->client->flush();

        // Verify was called (no exception = success)
        $this->assertTrue(true);
    }

    public function test_shutdown_delegates_to_tracing_service(): void
    {
        $this->tracingService->shouldReceive('shutdown')
            ->once()
            ->andReturnNull();

        $this->client->shutdown();

        // Verify was called (no exception = success)
        $this->assertTrue(true);
    }
}
