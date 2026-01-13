<?php

declare(strict_types=1);

namespace Langfuse\Observability\Services;

use Langfuse\Support\Enums\ObservationType;
use OpenTelemetry\API\Trace\SpanKind;

/**
 * Service for mapping observation types to OpenTelemetry span kinds
 */
class AttributeMapperService
{
    /**
     * Map ObservationType to OpenTelemetry SpanKind
     */
    public function mapObservationTypeToSpanKind(ObservationType $type): int
    {
        return match ($type) {
            ObservationType::GENERATION, ObservationType::EMBEDDING => SpanKind::KIND_CLIENT,
            ObservationType::AGENT, ObservationType::TOOL, ObservationType::CHAIN => SpanKind::KIND_INTERNAL,
            ObservationType::RETRIEVER => SpanKind::KIND_CLIENT,
            default => SpanKind::KIND_INTERNAL,
        };
    }
}
