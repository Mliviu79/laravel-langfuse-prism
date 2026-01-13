<?php

declare(strict_types=1);

namespace Langfuse\Observability\Concerns;

use DateTime;
use Langfuse\Support\Enums\ObservationType;
use Langfuse\Support\Enums\SpanLevel;
use OpenTelemetry\API\Trace\SpanInterface as OtelSpanInterface;
use OpenTelemetry\API\Trace\StatusCode;

/**
 * Concern for setting span attributes on OpenTelemetry spans.
 *
 * Provides reusable methods for setting Langfuse-specific and
 * OpenTelemetry semantic convention attributes on spans.
 */
trait HasSpanAttributes
{
    /**
     * Set input attributes on OpenTelemetry span
     */
    protected function setInputAttributes(OtelSpanInterface $otelSpan, mixed $input, bool $isRootSpan = false): void
    {
        if ($input === null) {
            return;
        }

        $inputJson = json_encode($input);
        // Set Langfuse-specific attribute as JSON
        $otelSpan->setAttribute('langfuse.observation.input', $inputJson);

        // input.value should be a simple string, not JSON (per Langfuse OTEL docs)
        $inputString = is_string($input) ? $input : $inputJson;
        $otelSpan->setAttribute('input.value', $inputString);

        // Also set gen_ai.prompt for better compatibility with LLM observability
        $otelSpan->setAttribute('gen_ai.prompt', $inputString);

        // If root span, also set trace input
        if ($isRootSpan) {
            $otelSpan->setAttribute('langfuse.trace.input', $inputJson);
        }
    }

    /**
     * Set output attributes on OpenTelemetry span
     */
    protected function setOutputAttributes(OtelSpanInterface $otelSpan, mixed $output, bool $isRootSpan = false): void
    {
        if ($output === null) {
            return;
        }

        $outputJson = json_encode($output);
        // Set Langfuse-specific attribute as JSON
        $otelSpan->setAttribute('langfuse.observation.output', $outputJson);

        // output.value should be a simple string, not JSON (per Langfuse OTEL docs)
        $outputString = is_string($output) ? $output : $outputJson;
        $otelSpan->setAttribute('output.value', $outputString);

        // Also set gen_ai.completion for better compatibility with LLM observability
        $otelSpan->setAttribute('gen_ai.completion', $outputString);

        // If root span, also set trace output
        if ($isRootSpan) {
            $otelSpan->setAttribute('langfuse.trace.output', $outputJson);
        }
    }

    /**
     * Set metadata attributes on OpenTelemetry span
     */
    protected function setMetadataAttributes(OtelSpanInterface $otelSpan, ?array $metadata): void
    {
        if ($metadata === null) {
            return;
        }

        foreach ($metadata as $key => $value) {
            $otelSpan->setAttribute(
                "langfuse.observation.metadata.{$key}",
                is_scalar($value) ? $value : json_encode($value)
            );
        }
    }

    /**
     * Set model attributes on OpenTelemetry span
     */
    protected function setModelAttributes(OtelSpanInterface $otelSpan, ?string $model, ?array $modelParameters = null): void
    {
        if ($model !== null) {
            $otelSpan->setAttribute('gen_ai.request.model', $model);
            $otelSpan->setAttribute('langfuse.observation.model.name', $model);
        }

        if ($modelParameters !== null) {
            // Set individual gen_ai attributes for OpenTelemetry compatibility
            foreach ($modelParameters as $key => $value) {
                $otelSpan->setAttribute(
                    "gen_ai.request.{$key}",
                    is_scalar($value) ? $value : json_encode($value)
                );
            }
            // Also set Langfuse-specific attribute as JSON string
            $otelSpan->setAttribute('langfuse.observation.model.parameters', json_encode($modelParameters));
        }
    }

    /**
     * Set usage attributes on OpenTelemetry span
     */
    protected function setUsageAttributes(OtelSpanInterface $otelSpan, ?array $usageDetails): void
    {
        if ($usageDetails === null) {
            return;
        }

        // Set individual gen_ai attributes for OpenTelemetry compatibility
        if (isset($usageDetails['input']) || isset($usageDetails['prompt_tokens'])) {
            $inputTokens = $usageDetails['input'] ?? $usageDetails['prompt_tokens'] ?? 0;
            $otelSpan->setAttribute('gen_ai.usage.input_tokens', $inputTokens);
        }
        if (isset($usageDetails['output']) || isset($usageDetails['completion_tokens'])) {
            $outputTokens = $usageDetails['output'] ?? $usageDetails['completion_tokens'] ?? 0;
            $otelSpan->setAttribute('gen_ai.usage.output_tokens', $outputTokens);
        }
        if (isset($usageDetails['total']) || isset($usageDetails['total_tokens'])) {
            $totalTokens = $usageDetails['total'] ?? $usageDetails['total_tokens'] ?? 0;
            $otelSpan->setAttribute('gen_ai.usage.total_tokens', $totalTokens);
        }

        // Set Langfuse-specific attribute as JSON string
        $otelSpan->setAttribute('langfuse.observation.usage_details', json_encode($usageDetails));
    }

    /**
     * Set cost attributes on OpenTelemetry span
     */
    protected function setCostAttributes(OtelSpanInterface $otelSpan, ?array $costDetails): void
    {
        if ($costDetails === null) {
            return;
        }

        // Set total cost for gen_ai conventions
        if (isset($costDetails['total'])) {
            $otelSpan->setAttribute('gen_ai.usage.cost', $costDetails['total']);
        }

        // Set Langfuse-specific attribute as JSON string
        $otelSpan->setAttribute('langfuse.observation.cost_details', json_encode($costDetails));
    }

    /**
     * Set level and status on OpenTelemetry span
     */
    protected function setLevelAttributes(OtelSpanInterface $otelSpan, ?SpanLevel $level, ?string $statusMessage = null): void
    {
        if ($level === null) {
            return;
        }

        $otelSpan->setAttribute('langfuse.observation.level', $level->value);

        // Set OpenTelemetry status based on level
        if ($level === SpanLevel::ERROR) {
            $otelSpan->setStatus(StatusCode::STATUS_ERROR, $statusMessage ?? 'Error occurred');
        } elseif ($level === SpanLevel::WARNING) {
            $otelSpan->setStatus(StatusCode::STATUS_OK, $statusMessage ?? '');
        }
    }

    /**
     * Set observation type attributes on OpenTelemetry span
     */
    protected function setObservationTypeAttributes(OtelSpanInterface $otelSpan, ObservationType $type, string $spanId): void
    {
        $otelSpan->setAttribute('langfuse.observation.type', $type->value);
        $otelSpan->setAttribute('langfuse.observation.id', $spanId);
        // Mark span for Langfuse export (used by OTEL Collector filter)
        $otelSpan->setAttribute('langfuse.export', 'true');
    }

    /**
     * Set trace-level attributes on OpenTelemetry span
     */
    protected function setTraceAttributes(
        OtelSpanInterface $otelSpan,
        ?string $name = null,
        ?string $userId = null,
        ?string $sessionId = null,
        ?string $version = null,
        mixed $input = null,
        mixed $output = null,
        ?array $metadata = null,
        ?array $tags = null,
        ?bool $public = null
    ): void {
        if ($name !== null) {
            $otelSpan->setAttribute('langfuse.trace.name', $name);
        }

        if ($userId !== null) {
            $otelSpan->setAttribute('langfuse.trace.user_id', $userId);
        }

        if ($sessionId !== null) {
            $otelSpan->setAttribute('langfuse.trace.session_id', $sessionId);
        }

        if ($version !== null) {
            $otelSpan->setAttribute('langfuse.trace.version', $version);
        }

        if ($input !== null) {
            $otelSpan->setAttribute('langfuse.trace.input', json_encode($input));
        }

        if ($output !== null) {
            $otelSpan->setAttribute('langfuse.trace.output', json_encode($output));
        }

        if ($metadata !== null) {
            foreach ($metadata as $key => $value) {
                $otelSpan->setAttribute(
                    "langfuse.trace.metadata.{$key}",
                    is_scalar($value) ? $value : json_encode($value)
                );
            }
        }

        if ($tags !== null) {
            $otelSpan->setAttribute('langfuse.trace.tags', json_encode($tags));
        }

        if ($public !== null) {
            $otelSpan->setAttribute('langfuse.trace.public', $public);
        }
    }

    /**
     * Set prompt attributes on OpenTelemetry span
     */
    protected function setPromptAttributes(OtelSpanInterface $otelSpan, ?string $promptName, ?int $promptVersion): void
    {
        if ($promptName !== null) {
            $otelSpan->setAttribute('langfuse.observation.prompt.name', $promptName);
        }

        if ($promptVersion !== null) {
            $otelSpan->setAttribute('langfuse.observation.prompt.version', $promptVersion);
        }
    }

    /**
     * Set completion start time attribute on OpenTelemetry span
     */
    protected function setCompletionStartTimeAttribute(OtelSpanInterface $otelSpan, ?DateTime $completionStartTime): void
    {
        if ($completionStartTime !== null) {
            $otelSpan->setAttribute('langfuse.observation.completion_start_time', $completionStartTime->format('c'));
        }
    }

    /**
     * Set version attribute on OpenTelemetry span
     */
    protected function setVersionAttribute(OtelSpanInterface $otelSpan, ?string $version): void
    {
        if ($version !== null) {
            $otelSpan->setAttribute('langfuse.observation.version', $version);
        }
    }

    /**
     * Set status message attribute on OpenTelemetry span
     */
    protected function setStatusMessageAttribute(OtelSpanInterface $otelSpan, ?string $statusMessage): void
    {
        if ($statusMessage !== null) {
            $otelSpan->setAttribute('langfuse.observation.status_message', $statusMessage);
        }
    }

    /**
     * Set parent observation ID attribute on OpenTelemetry span
     */
    protected function setParentObservationIdAttribute(OtelSpanInterface $otelSpan, ?string $parentObservationId): void
    {
        if ($parentObservationId !== null) {
            $otelSpan->setAttribute('langfuse.observation.parent_id', $parentObservationId);
        }
    }
}
