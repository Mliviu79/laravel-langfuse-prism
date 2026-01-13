<?php

declare(strict_types=1);

namespace Langfuse\Api\Contracts;

use Langfuse\Api\Exceptions\ApiException;

interface ApiClientInterface
{
    /**
     * Send a batch of events to the ingestion endpoint
     *
     * @throws ApiException
     */
    public function batch(array $events): array;

    /**
     * Create a trace
     *
     * @throws ApiException
     */
    public function createTrace(array $data): array;

    /**
     * Update a trace
     *
     * @throws ApiException
     */
    public function updateTrace(string $traceId, array $data): array;

    /**
     * Get a trace
     *
     * @throws ApiException
     */
    public function getTrace(string $traceId): array;

    /**
     * Create a score
     *
     * @throws ApiException
     */
    public function createScore(array $data): array;

    /**
     * Create a dataset
     *
     * @throws ApiException
     */
    public function createDataset(array $data): array;

    /**
     * Get datasets
     *
     * @throws ApiException
     */
    public function getDatasets(?int $page = null, ?int $limit = null): array;

    /**
     * Get a dataset by name
     *
     * @throws ApiException
     */
    public function getDataset(string $name): array;

    /**
     * Create a dataset item
     *
     * @throws ApiException
     */
    public function createDatasetItem(array $data): array;

    /**
     * Get dataset items
     *
     * @throws ApiException
     */
    public function getDatasetItems(string $datasetName, ?int $page = null, ?int $limit = null): array;

    /**
     * Create a dataset run
     *
     * @throws ApiException
     */
    public function createDatasetRun(array $data): array;

    /**
     * Get dataset runs
     *
     * @throws ApiException
     */
    public function getDatasetRuns(string $datasetName, ?int $page = null, ?int $limit = null): array;

    /**
     * Upload media content
     *
     * @throws ApiException
     */
    public function uploadMedia(string $contentType, string $content): array;

    /**
     * Get media upload URL
     *
     * @throws ApiException
     */
    public function getMediaUploadUrl(string $contentType, ?string $contentLength = null): array;

    /**
     * Get prompts
     *
     * @throws ApiException
     */
    public function getPrompts(?string $name = null, ?string $label = null, ?string $tag = null, ?int $page = null, ?int $limit = null): array;

    /**
     * Get a prompt by name
     *
     * @throws ApiException
     */
    public function getPrompt(string $name, ?int $version = null, ?string $label = null): array;

    /**
     * Create a prompt
     *
     * @throws ApiException
     */
    public function createPrompt(array $data): array;

    /**
     * Update prompt labels
     *
     * @throws ApiException
     */
    public function updatePromptLabels(string $name, int $version, array $labels): array;

    /**
     * Get prompt filter options
     *
     * @throws ApiException
     */
    public function getPromptFilterOptions(): array;
}
