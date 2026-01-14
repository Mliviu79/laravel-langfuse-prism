<?php

declare(strict_types=1);

namespace Langfuse\Integration\Prism\Providers;

use DateTime;
use Generator;
use Langfuse\Integration\Prism\Services\PrismTracingService;
use Prism\Prism\Audio\AudioResponse as TextToSpeechResponse;
use Prism\Prism\Audio\SpeechToTextRequest;
use Prism\Prism\Audio\TextResponse as SpeechToTextResponse;
use Prism\Prism\Audio\TextToSpeechRequest;
use Prism\Prism\Embeddings\Request as EmbeddingsRequest;
use Prism\Prism\Embeddings\Response as EmbeddingsResponse;
use Prism\Prism\Images\Request as ImagesRequest;
use Prism\Prism\Images\Response as ImagesResponse;
use Prism\Prism\Moderation\Request as ModerationRequest;
use Prism\Prism\Moderation\Response as ModerationResponse;
use Prism\Prism\Providers\Provider;
use Prism\Prism\Structured\Request as StructuredRequest;
use Prism\Prism\Structured\Response as StructuredResponse;
use Prism\Prism\Streaming\Events\StreamEndEvent;
use Prism\Prism\Streaming\Events\StreamEvent;
use Prism\Prism\Streaming\Events\TextDeltaEvent;
use Prism\Prism\Text\Request as TextRequest;
use Prism\Prism\Text\Response as TextResponse;
use Throwable;

/**
 * Decorator for Prism providers that adds automatic Langfuse tracing.
 *
 * Wraps all Prism operations (text, structured, embeddings, images, audio, moderation)
 * with Langfuse observability tracing.
 */
class LangfuseTracingProvider extends Provider
{
    public function __construct(
        private readonly Provider $provider,
        private readonly PrismTracingService $tracingService,
    ) {
    }

    public function text(TextRequest $request): TextResponse
    {
        if (!$this->shouldTrace()) {
            return $this->provider->text($request);
        }

        $startTime = new DateTime();
        $span = $this->tracingService->startTrace($request, 'text');

        try {
            $response = $this->provider->text($request);
            $this->tracingService->updateWithSuccess($span, $response, $startTime);

            return $response;
        } catch (Throwable $e) {
            $this->tracingService->updateWithError($span, $e);
            throw $e;
        } finally {
            $span->end();
        }
    }

    public function structured(StructuredRequest $request): StructuredResponse
    {
        if (!$this->shouldTrace()) {
            return $this->provider->structured($request);
        }

        $startTime = new DateTime();
        $span = $this->tracingService->startTrace($request, 'structured');

        try {
            $response = $this->provider->structured($request);
            $this->tracingService->updateWithSuccess($span, $response, $startTime);

            return $response;
        } catch (Throwable $e) {
            $this->tracingService->updateWithError($span, $e);
            throw $e;
        } finally {
            $span->end();
        }
    }

    public function embeddings(EmbeddingsRequest $request): EmbeddingsResponse
    {
        if (!$this->shouldTrace()) {
            return $this->provider->embeddings($request);
        }

        $startTime = new DateTime();
        $span = $this->tracingService->startTrace($request, 'embeddings');

        try {
            $response = $this->provider->embeddings($request);
            $this->tracingService->updateWithSuccess($span, $response, $startTime);

            return $response;
        } catch (Throwable $e) {
            $this->tracingService->updateWithError($span, $e);
            throw $e;
        } finally {
            $span->end();
        }
    }

    public function images(ImagesRequest $request): ImagesResponse
    {
        if (!$this->shouldTrace()) {
            return $this->provider->images($request);
        }

        $startTime = new DateTime();
        $span = $this->tracingService->startTrace($request, 'images');

        try {
            $response = $this->provider->images($request);
            $span->update(output: [
                'images_generated' => count($response->images),
            ]);

            return $response;
        } catch (Throwable $e) {
            $this->tracingService->updateWithError($span, $e);
            throw $e;
        } finally {
            $span->end();
        }
    }

    public function moderation(ModerationRequest $request): ModerationResponse
    {
        if (!$this->shouldTrace()) {
            return $this->provider->moderation($request);
        }

        $startTime = new DateTime();
        $span = $this->tracingService->startTrace($request, 'moderation');

        try {
            $response = $this->provider->moderation($request);

            // Add moderation-specific output
            $span->update(output: [
                'flagged' => $response->isFlagged(),
                'flagged_count' => count($response->flagged()),
                'results_count' => count($response->results),
            ]);

            return $response;
        } catch (Throwable $e) {
            $this->tracingService->updateWithError($span, $e);
            throw $e;
        } finally {
            $span->end();
        }
    }

    public function textToSpeech(TextToSpeechRequest $request): TextToSpeechResponse
    {
        if (!$this->shouldTrace()) {
            return $this->provider->textToSpeech($request);
        }

        $startTime = new DateTime();
        $span = $this->tracingService->startTrace($request, 'text-to-speech');

        try {
            $response = $this->provider->textToSpeech($request);
            $span->update(output: ['audio_generated' => true]);

            return $response;
        } catch (Throwable $e) {
            $this->tracingService->updateWithError($span, $e);
            throw $e;
        } finally {
            $span->end();
        }
    }

    public function speechToText(SpeechToTextRequest $request): SpeechToTextResponse
    {
        if (!$this->shouldTrace()) {
            return $this->provider->speechToText($request);
        }

        $startTime = new DateTime();
        $span = $this->tracingService->startTrace($request, 'speech-to-text');

        try {
            $response = $this->provider->speechToText($request);
            $span->update(output: [
                ['role' => 'assistant', 'content' => $response->text],
            ]);

            return $response;
        } catch (Throwable $e) {
            $this->tracingService->updateWithError($span, $e);
            throw $e;
        } finally {
            $span->end();
        }
    }

    /**
     * @return Generator<StreamEvent>
     */
    public function stream(TextRequest $request): Generator
    {
        if (!$this->shouldTrace()) {
            yield from $this->provider->stream($request);

            return;
        }

        $startTime = new DateTime();
        $span = $this->tracingService->startTrace($request, 'text-stream');
        $aggregatedText = '';
        $aggregatedUsage = null;

        try {
            foreach ($this->provider->stream($request) as $chunk) {
                if ($chunk instanceof TextDeltaEvent) {
                    $aggregatedText .= $chunk->delta;
                }

                if ($chunk instanceof StreamEndEvent && $chunk->usage) {
                    $aggregatedUsage = $chunk->usage;
                }
                yield $chunk;
            }

            // Update span with aggregated data in GenAI completion format
            $updateData = ['output' => [
                ['role' => 'assistant', 'content' => $aggregatedText],
            ]];

            if ($aggregatedUsage) {
                $usageDetails = [
                    'input' => $aggregatedUsage->promptTokens,
                    'output' => $aggregatedUsage->completionTokens,
                    'total' => $aggregatedUsage->promptTokens + $aggregatedUsage->completionTokens,
                    'unit' => 'TOKENS',
                ];

                if (isset($aggregatedUsage->thoughtTokens)) {
                    $usageDetails['reasoning'] = $aggregatedUsage->thoughtTokens;
                }

                $updateData['usageDetails'] = $usageDetails;
            }

            $span->update(...$updateData);
        } catch (Throwable $e) {
            $this->tracingService->updateWithError($span, $e);
            throw $e;
        } finally {
            $span->end();
        }
    }

    /**
     * Determine if tracing should be enabled for this operation.
     */
    private function shouldTrace(): bool
    {
        return config('langfuse.tracing_enabled', true)
            && config('langfuse.prism.auto_trace', true);
    }
}
