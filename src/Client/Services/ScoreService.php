<?php

declare(strict_types=1);

namespace Langfuse\Client\Services;

use Langfuse\Api\Contracts\ApiClientInterface;
use Langfuse\Scoring\Enums\ScoreDataType;
use Langfuse\Scoring\Score;
use Langfuse\Support\Contracts\IdGeneratorInterface;

/**
 * Service for score operations
 */
class ScoreService
{
    public function __construct(
        private readonly ApiClientInterface $apiClient,
        private readonly IdGeneratorInterface $idGenerator
    ) {}

    /**
     * Create a score
     */
    public function createScore(
        string $name,
        float|int|bool|string $value,
        ?string $traceId = null,
        ?string $observationId = null,
        ?string $scoreId = null,
        ?string $comment = null,
        ?string $configId = null,
    ): Score {
        $scoreId = $scoreId ?? $this->idGenerator->generateScoreId();

        // Infer data type from value if not provided
        $dataType = match (true) {
            is_bool($value) => ScoreDataType::BOOLEAN,
            is_numeric($value) => ScoreDataType::NUMERIC,
            default => ScoreDataType::CATEGORICAL,
        };

        $scoreData = [
            'id' => $scoreId,
            'name' => $name,
            'value' => $value,
            'dataType' => $dataType->value,
            'traceId' => $traceId,
            'observationId' => $observationId,
            'comment' => $comment,
            'configId' => $configId,
        ];

        // Remove null values
        $scoreData = array_filter($scoreData, fn ($val) => $val !== null);

        try {
            $this->apiClient->createScore($scoreData);
        } catch (\Throwable $e) {
            if (function_exists('report')) {
                report($e);
            }
        }

        return new Score(
            id: $scoreId,
            name: $name,
            value: $value,
            traceId: $traceId ?? 'unknown',
            observationId: $observationId,
            comment: $comment,
            configId: $configId,
            dataType: $dataType,
        );
    }
}
