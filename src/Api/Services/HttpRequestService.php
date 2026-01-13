<?php

declare(strict_types=1);

namespace Langfuse\Api\Services;

use Illuminate\Http\Client\Factory as HttpClientFactory;
use Illuminate\Http\Client\Response;
use Langfuse\Api\Exceptions\ApiException;
use Langfuse\Client\Configuration;

/**
 * Service for executing HTTP requests
 */
class HttpRequestService
{
    public function __construct(
        private readonly Configuration $config,
        private readonly HttpClientFactory $httpClient,
        private readonly DataSanitizationService $sanitizationService
    ) {
    }

    /**
     * Execute an HTTP request
     *
     * @param string $method HTTP method
     * @param string $url Request URL
     * @param array $data Request data
     * @param array $query Query parameters
     * @return Response HTTP response
     * @throws ApiException
     */
    public function execute(string $method, string $url, array $data = [], array $query = []): Response
    {
        if ($this->config->isDebugEnabled()) {
            \Illuminate\Support\Facades\Log::debug('Langfuse API Request', [
                'method' => $method,
                'url' => $url,
                'data' => $this->sanitizationService->sanitize($data),
                'query' => $query,
            ]);
        }

        $client = $this->httpClient
            ->withHeaders($this->config->getAllHeaders())
            ->timeout($this->config->timeout);

        if (!empty($query)) {
            $client = $client->withQueryParameters($query);
        }

        return match (strtoupper($method)) {
            'GET' => $client->get($url),
            'POST' => $client->post($url, $data),
            'PUT' => $client->put($url, $data),
            'PATCH' => $client->patch($url, $data),
            'DELETE' => $client->delete($url, $data),
            default => throw new ApiException("Unsupported HTTP method: {$method}"),
        };
    }
}
