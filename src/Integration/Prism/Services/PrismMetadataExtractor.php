<?php

declare(strict_types=1);

namespace Langfuse\Integration\Prism\Services;

/**
 * Service for extracting metadata from Prism requests/responses
 */
class PrismMetadataExtractor
{
    /**
     * Extract metadata from Prism request
     */
    public function extractFromRequest(mixed $request, ?string $provider = null): array
    {
        $metadata = [
            'provider' => $provider ?? $this->extractProvider($request),
            'langfuse_sdk' => 'langfuse-php',
            'prism_version' => $this->getPrismVersion(),
        ];

        $requestId = $this->extractRequestId($request);
        if ($requestId !== null) {
            $metadata['prism_request_id'] = $requestId;
        }

        $userData = $this->extractUserData($request);
        if ($userData !== null) {
            $metadata['user_data'] = $userData;
        }

        return $metadata;
    }

    /**
     * Extract metadata from Prism response
     */
    public function extractFromResponse(mixed $response): array
    {
        $metadata = [];

        $responseMetadata = match (true) {
            method_exists($response, 'metadata') => $response->metadata() ?? null,
            method_exists($response, 'getMetadata') => $response->getMetadata() ?? null,
            property_exists($response, 'metadata') => $response->metadata ?? null,
            default => null,
        };

        if ($responseMetadata !== null) {
            $metadata['response_metadata'] = is_array($responseMetadata) ? $responseMetadata : (array) $responseMetadata;
        }

        return $metadata;
    }

    private function extractProvider(mixed $request): ?string
    {
        return match (true) {
            method_exists($request, 'getProvider') => $request->getProvider() ?? null,
            method_exists($request, 'provider') => $request->provider() ?? null,
            property_exists($request, 'provider') => $request->provider ?? null,
            default => null,
        };
    }

    private function extractRequestId(mixed $request): ?string
    {
        return match (true) {
            method_exists($request, 'getRequestId') => $request->getRequestId() ?? null,
            method_exists($request, 'requestId') => $request->requestId() ?? null,
            property_exists($request, 'requestId') => $request->requestId ?? null,
            default => null,
        };
    }

    private function extractUserData(mixed $request): ?array
    {
        return match (true) {
            method_exists($request, 'getUserData') => $request->getUserData() ?? null,
            method_exists($request, 'userData') => $request->userData() ?? null,
            property_exists($request, 'userData') => $request->userData ?? null,
            default => null,
        };
    }

    private function getPrismVersion(): string
    {
        try {
            if (class_exists(\Composer\InstalledVersions::class)) {
                try {
                    return \Composer\InstalledVersions::getVersion('prism-php/prism') ?? 'unknown';
                } catch (\Throwable) {
                    // Fallback if package name differs or not found
                }
            }

            $composerPath = base_path('vendor/prism-php/prism/composer.json');
            if (file_exists($composerPath)) {
                $composer = json_decode(file_get_contents($composerPath), true);

                return $composer['version'] ?? 'unknown';
            }

            return 'unknown';
        } catch (\Throwable) {
            return 'unknown';
        }
    }
}
