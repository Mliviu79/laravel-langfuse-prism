<?php

declare(strict_types=1);

namespace Langfuse\Tests\Unit\Client\Services;

use Langfuse\Api\Contracts\ApiClientInterface;
use Langfuse\Client\Services\DatasetService;
use Langfuse\Datasets\Dataset;
use Langfuse\Datasets\DatasetItem;
use Langfuse\Datasets\DatasetRun;
use Langfuse\Support\Contracts\IdGeneratorInterface;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class DatasetServiceTest extends TestCase
{
    private MockInterface $apiClient;
    private MockInterface $idGenerator;
    private DatasetService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->apiClient = Mockery::mock(ApiClientInterface::class);
        $this->idGenerator = Mockery::mock(IdGeneratorInterface::class);
        
        $this->service = new DatasetService($this->apiClient, $this->idGenerator);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_create_dataset_calls_api_and_returns_dataset(): void
    {
        $this->apiClient->shouldReceive('createDataset')
            ->once()
            ->with([
                'name' => 'test-dataset',
                'description' => 'Test description',
                'metadata' => ['key' => 'value'],
            ])
            ->andReturn(['id' => 'dataset-123']);

        $dataset = $this->service->createDataset(
            'test-dataset',
            'Test description',
            ['key' => 'value']
        );

        $this->assertInstanceOf(Dataset::class, $dataset);
        $this->assertEquals('dataset-123', $dataset->id);
        $this->assertEquals('test-dataset', $dataset->name);
        $this->assertEquals('Test description', $dataset->description);
        $this->assertEquals(['key' => 'value'], $dataset->metadata);
    }

    public function test_create_dataset_generates_id_when_not_returned(): void
    {
        $this->apiClient->shouldReceive('createDataset')
            ->once()
            ->andReturn([]); // No ID returned
        
        $this->idGenerator->shouldReceive('generateId')
            ->once()
            ->andReturn('generated-id');

        $dataset = $this->service->createDataset('test-dataset');

        $this->assertEquals('generated-id', $dataset->id);
    }

    public function test_get_dataset_returns_dataset_on_success(): void
    {
        $this->apiClient->shouldReceive('getDataset')
            ->once()
            ->with('test-dataset')
            ->andReturn([
                'id' => 'dataset-123',
                'name' => 'test-dataset',
                'description' => 'Description',
                'metadata' => ['key' => 'value'],
            ]);

        $dataset = $this->service->getDataset('test-dataset');

        $this->assertInstanceOf(Dataset::class, $dataset);
        $this->assertEquals('dataset-123', $dataset->id);
        $this->assertEquals('test-dataset', $dataset->name);
    }

    public function test_get_dataset_returns_null_on_exception(): void
    {
        $this->apiClient->shouldReceive('getDataset')
            ->once()
            ->andThrow(new \Exception('Not found'));

        $dataset = $this->service->getDataset('nonexistent');

        $this->assertNull($dataset);
    }

    public function test_create_dataset_item_calls_api_and_returns_item(): void
    {
        $this->apiClient->shouldReceive('createDatasetItem')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['datasetName'] === 'test-dataset'
                    && $data['input'] === ['prompt' => 'test']
                    && $data['expectedOutput'] === ['response' => 'test'];
            }))
            ->andReturn([
                'id' => 'item-123',
                'datasetId' => 'dataset-456',
            ]);

        $item = $this->service->createDatasetItem(
            'test-dataset',
            ['prompt' => 'test'],
            ['response' => 'test'],
            ['meta' => 'data'],
            'trace-123',
            'observation-456'
        );

        $this->assertInstanceOf(DatasetItem::class, $item);
        $this->assertEquals('item-123', $item->id);
        $this->assertEquals('dataset-456', $item->datasetId);
        $this->assertEquals('test-dataset', $item->datasetName);
    }

    public function test_create_dataset_item_removes_null_values(): void
    {
        $this->apiClient->shouldReceive('createDatasetItem')
            ->once()
            ->with(Mockery::on(function ($data) {
                return !array_key_exists('expectedOutput', $data)
                    && !array_key_exists('sourceTraceId', $data)
                    && !array_key_exists('sourceObservationId', $data);
            }))
            ->andReturn(['id' => 'item-123', 'datasetId' => 'ds-1']);

        $item = $this->service->createDatasetItem('test-dataset', ['input' => 'data']);

        $this->assertEquals('item-123', $item->id);
    }

    public function test_create_dataset_run_calls_api_and_returns_run(): void
    {
        $this->apiClient->shouldReceive('createDatasetRun')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['datasetName'] === 'test-dataset'
                    && $data['name'] === 'run-1'
                    && $data['description'] === 'Run description';
            }))
            ->andReturn([
                'id' => 'run-123',
                'datasetId' => 'dataset-456',
            ]);

        $run = $this->service->createDatasetRun(
            'test-dataset',
            'run-1',
            'Run description',
            ['key' => 'value']
        );

        $this->assertInstanceOf(DatasetRun::class, $run);
        $this->assertEquals('run-123', $run->id);
        $this->assertEquals('run-1', $run->name);
        $this->assertEquals('dataset-456', $run->datasetId);
        $this->assertEquals('test-dataset', $run->datasetName);
    }

    public function test_create_dataset_run_generates_name_when_not_provided(): void
    {
        $this->apiClient->shouldReceive('createDatasetRun')
            ->once()
            ->andReturn(['id' => 'run-123', 'datasetId' => 'ds-1']);
        
        $this->idGenerator->shouldReceive('generateTimestamp')
            ->once()
            ->andReturn('1705056000');

        $run = $this->service->createDatasetRun('test-dataset');

        $this->assertStringContainsString('run-1705056000', $run->name);
    }
}
