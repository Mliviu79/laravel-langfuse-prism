<?php

declare(strict_types=1);

namespace Langfuse\Integration\Prism\Services;

use DateTime;
use Langfuse\Client\Contracts\LangfuseClientInterface;
use Langfuse\Integration\Prism\Contracts\PrismRequestExtractorInterface;
use Langfuse\Integration\Prism\Contracts\PrismResponseExtractorInterface;
use Langfuse\Integration\Prism\DTOs\PrismRequestDto;
use Langfuse\Integration\Prism\DTOs\PrismResponseDto;
use Langfuse\Observability\Contracts\SpanInterface;
use Langfuse\Support\Enums\ObservationType;
use Langfuse\Support\Enums\SpanLevel;
use Prism\Prism\Audio\AudioResponse as TextToSpeechResponse;
use Prism\Prism\Audio\SpeechToTextRequest;
use Prism\Prism\Audio\TextResponse as SpeechToTextResponse;
use Prism\Prism\Audio\TextToSpeechRequest;
use Prism\Prism\Contracts\PrismRequest;
use Prism\Prism\Embeddings\Request as EmbeddingsRequest;
use Prism\Prism\Embeddings\Response as EmbeddingsResponse;
use Prism\Prism\Images\Request as ImagesRequest;
use Prism\Prism\Images\Response as ImagesResponse;
use Prism\Prism\Moderation\Request as ModerationRequest;
use Prism\Prism\Moderation\Response as ModerationResponse;
use Prism\Prism\Structured\Request as StructuredRequest;
use Prism\Prism\Structured\Response as StructuredResponse;
use Prism\Prism\Text\Request as TextRequest;
use Prism\Prism\Text\Response as TextResponse;
use Throwable;

/**
 * Service for tracing Prism operations.
 *
 * Provides methods to start, update, and end traces for Prism AI operations.
 */
class PrismTracingService
{
    public function __construct(
        private readonly LangfuseClientInterface $langfuse,
        private readonly PrismRequestExtractorInterface $requestExtractor,
        private readonly PrismResponseExtractorInterface $responseExtractor,
        private readonly PrismMetadataExtractor $metadataExtractor,
        private readonly bool $traceModelParams = true,
        private readonly bool $traceUsage = true,
        private readonly bool $traceCost = true,
    ) {}

    /**
     * Start tracing a Prism operation.
     */
    public function startTrace(
        PrismRequest|TextRequest|StructuredRequest|EmbeddingsRequest|ImagesRequest|ModerationRequest|TextToSpeechRequest|SpeechToTextRequest $request,
        string $operationType
    ): SpanInterface {
        $requestDto = $this->requestExtractor->extract($request);
        $provider = $requestDto->provider ?? 'unknown';
        $model = $requestDto->model ?? 'unknown';

        $input = $this->buildInput($requestDto);
        $metadata = $this->metadataExtractor->extractFromRequest($request, $provider);

        if ($this->traceModelParams && $requestDto->parameters !== null) {
            $metadata['model_params'] = $requestDto->parameters;
        }

        $observationType = $this->determineObservationType($operationType);

        $span = $this->langfuse->startSpan(
            name: "prism-{$provider}-{$model}",
            type: $observationType,
            input: $input,
            metadata: $metadata,
        );

        // Update span with model, model parameters, and prompt details
        $span->update(
            model: $model !== 'unknown' ? $model : null,
            modelParameters: $this->traceModelParams ? $requestDto->parameters : null,
            promptName: $requestDto->promptName,
            promptVersion: $requestDto->promptVersion,
        );

        return $span;
    }

    /**
     * Update span with successful response.
     */
    public function updateWithSuccess(
        SpanInterface $span,
        TextResponse|StructuredResponse|EmbeddingsResponse|ImagesResponse|ModerationResponse|TextToSpeechResponse|SpeechToTextResponse $response,
        DateTime $startTime
    ): void {
        $responseDto = $this->responseExtractor->extract($response);

        $updateData = [
            'output' => $this->buildOutput($responseDto),
            'completionStartTime' => $responseDto->completionStartTime ?? $startTime,
        ];

        // Add usage details if available and enabled
        if ($this->traceUsage && $responseDto->usage !== null) {
            $updateData['usageDetails'] = [
                'input' => $responseDto->usage->promptTokens,
                'output' => $responseDto->usage->completionTokens,
                'total' => $responseDto->usage->totalTokens,
                'unit' => 'TOKENS',
            ];

            if ($responseDto->usage->thoughtTokens !== null) {
                $updateData['usageDetails']['reasoning'] = $responseDto->usage->thoughtTokens;
            }
        }

        // Add cost details if available and enabled
        if ($this->traceCost && $responseDto->cost !== null) {
            $updateData['costDetails'] = [
                'input' => $responseDto->cost->inputCost,
                'output' => $responseDto->cost->outputCost,
                'total' => $responseDto->cost->totalCost,
            ];
        }

        // Add additional response metadata
        $responseMetadata = $this->metadataExtractor->extractFromResponse($response);
        if (! empty($responseMetadata)) {
            $updateData['metadata'] = $responseMetadata;
        }

        $span->update(...$updateData);
    }

    /**
     * Update span with error.
     */
    public function updateWithError(SpanInterface $span, Throwable $exception): void
    {
        $span->update(
            level: SpanLevel::ERROR,
            statusMessage: $exception->getMessage(),
            metadata: [
                'error' => [
                    'type' => get_class($exception),
                    'message' => $exception->getMessage(),
                    'code' => $exception->getCode(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                ],
            ]
        );
    }

    /**
     * Determine the observation type based on operation type.
     */
    private function determineObservationType(string $operationType): ObservationType
    {
        return match ($operationType) {
            'embeddings' => ObservationType::EMBEDDING,
            'moderation' => ObservationType::SPAN,
            default => ObservationType::GENERATION,
        };
    }

    /**
     * Build input array from request DTO.
     */
    private function buildInput(PrismRequestDto $requestDto): array
    {
        $input = [];

        if ($requestDto->prompt !== null) {
            $input['prompt'] = $requestDto->prompt;
        }

        if ($requestDto->messages !== null) {
            $input['messages'] = $requestDto->messages;
        }

        if ($requestDto->systemPrompt !== null) {
            $input['system_prompt'] = $requestDto->systemPrompt;
        }

        if ($requestDto->additionalInput !== null) {
            $input = array_merge($input, $requestDto->additionalInput);
        }

        return $input;
    }

    /**
     * Build output array from response DTO.
     */
    private function buildOutput(PrismResponseDto $responseDto): array
    {
        $output = [];

        if ($responseDto->text !== null) {
            $output['text'] = $responseDto->text;
        }

        if ($responseDto->message !== null) {
            $output['message'] = $responseDto->message;
        }

        if ($responseDto->choices !== null) {
            $output['choices'] = $responseDto->choices;
        }

        if ($responseDto->additionalOutput !== null) {
            $output = array_merge($output, $responseDto->additionalOutput);
        }

        return $output;
    }
}
