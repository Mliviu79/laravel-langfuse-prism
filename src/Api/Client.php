<?php

declare(strict_types=1);

namespace Langfuse\Api;

use Illuminate\Support\Str;
use Langfuse\Api\Contracts\ApiClientInterface;
use Langfuse\Api\Exceptions\ApiException;
use Langfuse\Api\Services\DataSanitizationService;
use Langfuse\Api\Services\HttpRequestService;
use Langfuse\Api\Services\ResponseHandlerService;
use Langfuse\Client\Configuration;

class Client implements ApiClientInterface
{
    public function __construct(
        private readonly Configuration $config,
        private readonly HttpRequestService $httpRequestService,
        private readonly ResponseHandlerService $responseHandlerService,
        private readonly DataSanitizationService $sanitizationService
    ) {}

    public function batch(array $events): array
    {
        // Normalize events to match batch ingestion schema: require envelope id, timestamp,
        // and ensure body.type matches API expectations (e.g., GENERATION|SPAN|EVENT)
        $normalized = array_map(function (array $event) {
            $body = $event['body'] ?? [];

            // Ensure body.type is uppercase if present
            if (isset($body['type']) && is_string($body['type'])) {
                $body['type'] = strtoupper($body['type']);
            }

            // Derive envelope id and timestamp
            $envelopeId = $event['id'] ?? ($body['id'] ?? (string) Str::uuid());
            $timestamp = $event['timestamp']
                ?? ($body['endTime'] ?? ($body['startTime'] ?? (new \DateTime)->format('c')));

            return [
                'id' => $envelopeId,
                'timestamp' => $timestamp,
                'type' => $event['type'],
                'body' => $body,
            ];
        }, $events);

        return $this->makeRequest('POST', '/ingestion', [
            'batch' => $normalized,
        ]);
    }

    public function createTrace(array $data): array
    {
        return $this->makeRequest('POST', '/traces', $data);
    }

    public function updateTrace(string $traceId, array $data): array
    {
        return $this->makeRequest('PATCH', "/traces/{$traceId}", $data);
    }

    public function getTrace(string $traceId): array
    {
        return $this->makeRequest('GET', "/traces/{$traceId}");
    }

    public function createScore(array $data): array
    {
        return $this->makeRequest('POST', '/scores', $data);
    }

    public function createDataset(array $data): array
    {
        return $this->makeRequest('POST', '/datasets', $data);
    }

    public function getDatasets(?int $page = null, ?int $limit = null): array
    {
        $query = [];
        if ($page !== null) {
            $query['page'] = $page;
        }
        if ($limit !== null) {
            $query['limit'] = $limit;
        }

        return $this->makeRequest('GET', '/datasets', query: $query);
    }

    public function getDataset(string $name): array
    {
        // URL-encode the dataset name to handle names with special characters
        $encodedName = rawurlencode($name);

        return $this->makeRequest('GET', "/datasets/{$encodedName}");
    }

    public function createDatasetItem(array $data): array
    {
        return $this->makeRequest('POST', '/dataset-items', $data);
    }

    public function getDatasetItems(string $datasetName, ?int $page = null, ?int $limit = null): array
    {
        $query = ['datasetName' => $datasetName];
        if ($page !== null) {
            $query['page'] = $page;
        }
        if ($limit !== null) {
            $query['limit'] = $limit;
        }

        return $this->makeRequest('GET', '/dataset-items', query: $query);
    }

    public function createDatasetRun(array $data): array
    {
        return $this->makeRequest('POST', '/dataset-runs', $data);
    }

    public function getDatasetRuns(string $datasetName, ?int $page = null, ?int $limit = null): array
    {
        $query = ['datasetName' => $datasetName];
        if ($page !== null) {
            $query['page'] = $page;
        }
        if ($limit !== null) {
            $query['limit'] = $limit;
        }

        return $this->makeRequest('GET', '/dataset-runs', query: $query);
    }

    public function uploadMedia(string $contentType, string $content): array
    {
        return $this->makeRequest('POST', '/media', [
            'contentType' => $contentType,
            'content' => base64_encode($content),
        ]);
    }

    public function getMediaUploadUrl(string $contentType, ?string $contentLength = null): array
    {
        $data = ['contentType' => $contentType];
        if ($contentLength !== null) {
            $data['contentLength'] = $contentLength;
        }

        return $this->makeRequest('POST', '/media/upload-url', $data);
    }

    public function getPrompts(?string $name = null, ?string $label = null, ?string $tag = null, ?int $page = null, ?int $limit = null): array
    {
        $query = [];
        if ($name !== null) {
            $query['name'] = $name;
        }
        if ($label !== null) {
            $query['label'] = $label;
        }
        if ($tag !== null) {
            $query['tag'] = $tag;
        }
        if ($page !== null) {
            $query['page'] = $page;
        }
        if ($limit !== null) {
            $query['limit'] = $limit;
        }

        return $this->makeRequest('GET', '/v2/prompts', query: $query);
    }

    public function getPrompt(string $name, ?int $version = null, ?string $label = null): array
    {
        $query = [];
        if ($version !== null) {
            $query['version'] = $version;
        }
        if ($label !== null) {
            $query['label'] = $label;
        }

        // URL-encode the prompt name to handle names with special characters like slashes
        $encodedName = rawurlencode($name);

        return $this->makeRequest('GET', "/v2/prompts/{$encodedName}", query: $query);
    }

    public function createPrompt(array $data): array
    {
        return $this->makeRequest('POST', '/v2/prompts', $data);
    }

    public function updatePromptLabels(string $name, int $version, array $labels): array
    {
        // URL-encode the prompt name to handle names with special characters like slashes
        $encodedName = rawurlencode($name);

        return $this->makeRequest('PATCH', "/v2/prompts/{$encodedName}/versions/{$version}", [
            'labels' => $labels,
        ]);
    }

    public function getPromptFilterOptions(): array
    {
        return $this->makeRequest('GET', '/v2/prompts/filterOptions');
    }

    /**
     * Make an HTTP request to the Langfuse API
     */
    protected function makeRequest(
        string $method,
        string $endpoint,
        array $data = [],
        array $query = []
    ): array {
        try {
            $url = $this->config->getApiUrl().$endpoint;

            $response = $this->httpRequestService->execute($method, $url, $data, $query);

            return $this->responseHandlerService->handleResponse($response);
        } catch (\Throwable $e) {
            if ($e instanceof ApiException) {
                throw $e;
            }

            throw new ApiException(
                message: "API request failed: {$e->getMessage()}",
                previous: $e,
                context: [
                    'method' => $method,
                    'endpoint' => $endpoint,
                    'data' => $this->sanitizationService->sanitize($data),
                ]
            );
        }
    }
}
