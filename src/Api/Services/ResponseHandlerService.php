<?php

declare(strict_types=1);

namespace Langfuse\Api\Services;

use Illuminate\Http\Client\Response;
use Langfuse\Api\Exceptions\ApiException;
use Langfuse\Client\Configuration;
use Throwable;

/**
 * Service for handling HTTP responses
 */
class ResponseHandlerService
{
    public function __construct(
        private readonly Configuration $config,
        private readonly DataSanitizationService $sanitizationService
    ) {
    }

    /**
     * Handle HTTP response and convert to array or throw exception
     */
    public function handleResponse(Response $response): array
    {
        $statusCode = $response->status();
        $body = $response->body();
        $headers = $response->headers();

        if ($this->config->isDebugEnabled()) {
            \Illuminate\Support\Facades\Log::debug('Langfuse API Response', [
                'status' => $statusCode,
                'headers' => $headers,
                'body' => $this->sanitizationService->sanitize($body),
            ]);
        }

        if (!$response->successful()) {
            $message = $this->extractErrorMessage($response);

            throw ApiException::fromHttpResponse(
                statusCode: $statusCode,
                responseBody: $body,
                headers: $headers,
                message: $message
            );
        }

        try {
            return $response->json() ?? [];
        } catch (Throwable $e) {
            throw new ApiException(
                message: "Failed to decode JSON response: {$e->getMessage()}",
                httpStatusCode: $statusCode,
                httpResponseBody: $body,
                httpHeaders: $headers
            );
        }
    }

    /**
     * Extract error message from response
     */
    private function extractErrorMessage(Response $response): string
    {
        try {
            $data = $response->json();

            // Try common error message fields
            if (isset($data['message'])) {
                return $data['message'];
            }

            if (isset($data['error'])) {
                return is_string($data['error']) ? $data['error'] : ($data['error']['message'] ?? 'Unknown error');
            }

            if (isset($data['detail'])) {
                return $data['detail'];
            }

            return "HTTP {$response->status()}";
        } catch (Throwable) {
            return "HTTP {$response->status()}";
        }
    }
}
