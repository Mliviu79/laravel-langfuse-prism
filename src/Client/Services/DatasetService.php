<?php

declare(strict_types=1);

namespace Langfuse\Client\Services;

use Langfuse\Api\Contracts\ApiClientInterface;
use Langfuse\Datasets\Dataset;
use Langfuse\Datasets\DatasetItem;
use Langfuse\Datasets\DatasetRun;
use Langfuse\Support\Contracts\IdGeneratorInterface;

/**
 * Service for dataset operations
 */
class DatasetService
{
    public function __construct(
        private readonly ApiClientInterface $apiClient,
        private readonly IdGeneratorInterface $idGenerator
    ) {
    }

    /**
     * Create a dataset
     */
    public function createDataset(
        string $name,
        ?string $description = null,
        array $metadata = [],
    ): Dataset {
        $datasetData = [
            'name' => $name,
            'description' => $description,
            'metadata' => $metadata,
        ];

        $response = $this->apiClient->createDataset($datasetData);

        return new Dataset(
            id: $response['id'] ?? $this->idGenerator->generateId(),
            name: $name,
            description: $description,
            metadata: $metadata,
        );
    }

    /**
     * Get a dataset by name
     */
    public function getDataset(string $name): ?Dataset
    {
        try {
            $response = $this->apiClient->getDataset($name);

            return new Dataset(
                id: $response['id'],
                name: $response['name'],
                description: $response['description'] ?? null,
                metadata: $response['metadata'] ?? [],
            );
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Create a dataset item
     */
    public function createDatasetItem(
        string $datasetName,
        mixed $input,
        mixed $expectedOutput = null,
        array $metadata = [],
        ?string $sourceTraceId = null,
        ?string $sourceObservationId = null,
    ): DatasetItem {
        $itemData = [
            'datasetName' => $datasetName,
            'input' => $input,
            'expectedOutput' => $expectedOutput,
            'metadata' => $metadata,
            'sourceTraceId' => $sourceTraceId,
            'sourceObservationId' => $sourceObservationId,
        ];

        // Remove null values
        $itemData = array_filter($itemData, fn ($val) => $val !== null);

        $response = $this->apiClient->createDatasetItem($itemData);

        return new DatasetItem(
            id: $response['id'] ?? $this->idGenerator->generateId(),
            datasetId: $response['datasetId'] ?? 'unknown',
            datasetName: $datasetName,
            input: $input,
            expectedOutput: $expectedOutput,
            metadata: $metadata,
            sourceTraceId: $sourceTraceId,
            sourceObservationId: $sourceObservationId,
        );
    }

    /**
     * Create a dataset run
     */
    public function createDatasetRun(
        string $datasetName,
        ?string $name = null,
        ?string $description = null,
        array $metadata = [],
    ): DatasetRun {
        $runData = [
            'datasetName' => $datasetName,
            'name' => $name,
            'description' => $description,
            'metadata' => $metadata,
        ];

        // Remove null values
        $runData = array_filter($runData, fn ($val) => $val !== null);

        $response = $this->apiClient->createDatasetRun($runData);

        return new DatasetRun(
            id: $response['id'] ?? $this->idGenerator->generateId(),
            name: $name ?? "run-{$this->idGenerator->generateTimestamp()}",
            description: $description,
            datasetId: $response['datasetId'] ?? 'unknown',
            datasetName: $datasetName,
            metadata: $metadata,
        );
    }
}
