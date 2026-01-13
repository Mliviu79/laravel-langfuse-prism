<?php

declare(strict_types=1);

namespace Langfuse\Support\Enums;

enum ObservationType: string
{
    case SPAN = 'span';
    case GENERATION = 'generation';
    case EVENT = 'event';
    case AGENT = 'agent';
    case TOOL = 'tool';
    case CHAIN = 'chain';
    case RETRIEVER = 'retriever';
    case EMBEDDING = 'embedding';
    case EVALUATOR = 'evaluator';
    case GUARDRAIL = 'guardrail';

    /**
     * Get all observation types that are generation-like (support model parameters, usage details, etc.)
     */
    public static function getGenerationLikeTypes(): array
    {
        return [
            self::GENERATION,
            self::EMBEDDING,
        ];
    }

    /**
     * Get all observation types that are span-like (general purpose spans)
     */
    public static function getSpanLikeTypes(): array
    {
        return [
            self::SPAN,
            self::AGENT,
            self::TOOL,
            self::CHAIN,
            self::RETRIEVER,
            self::EVALUATOR,
            self::GUARDRAIL,
        ];
    }

    /**
     * Check if this observation type supports generation features
     */
    public function isGenerationLike(): bool
    {
        return in_array($this, self::getGenerationLikeTypes(), true);
    }

    /**
     * Check if this observation type is a span-like type
     */
    public function isSpanLike(): bool
    {
        return in_array($this, self::getSpanLikeTypes(), true);
    }

    /**
     * Get a human-readable description of the observation type
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::SPAN => 'General-purpose span for any operation',
            self::GENERATION => 'AI/LLM generation span with model tracking',
            self::EVENT => 'Instantaneous event marker',
            self::AGENT => 'Agent reasoning blocks that act on tools using LLM guidance',
            self::TOOL => 'External tool calls, e.g., calling a weather API',
            self::CHAIN => 'Connecting LLM application steps, e.g. passing context from retriever to LLM',
            self::RETRIEVER => 'Data retrieval steps, e.g. vector store or database queries',
            self::EMBEDDING => 'LLM embedding calls, typically used before retrieval',
            self::EVALUATOR => 'Assessing relevance, correctness, or helpfulness of LLM outputs',
            self::GUARDRAIL => 'Protection against jailbreaks or offensive content',
        };
    }
}