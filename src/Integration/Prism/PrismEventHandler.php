<?php

declare(strict_types=1);

namespace Langfuse\Integration\Prism;

use DateTime;
use Langfuse\Client\Contracts\LangfuseClientInterface;
use Langfuse\Observability\Contracts\SpanInterface;
use Langfuse\Support\Enums\ObservationType;
use Langfuse\Support\Enums\SpanLevel;

/**
 * Event handler for Prism events to enable Langfuse tracing
 */
class PrismEventHandler
{
    private array $activeGenerations = [];

    public function __construct(
        private readonly LangfuseClientInterface $langfuse
    ) {
    }

    /**
     * Handle the prompt executing event
     */
    public function handlePromptExecuting($event): void
    {
        if (!config('langfuse.prism.auto_trace', true)) {
            return;
        }

        $requestId = $this->getRequestId($event);

        $generation = $this->langfuse->startSpan(
            name: $this->generateSpanName($event),
            type: ObservationType::GENERATION,
            input: $this->extractInput($event),
            metadata: $this->extractMetadata($event),
        );

        // Store the generation for completion handling
        $this->activeGenerations[$requestId] = [
            'generation' => $generation,
            'start_time' => new DateTime(),
        ];

        // Add model parameters if available
        if ($this->hasModelParameters($event)) {
            $generation->update(
                modelParameters: $this->getModelParameters($event)
            );
        }
    }

    /**
     * Handle the prompt executed event
     */
    public function handlePromptExecuted($event): void
    {
        if (!config('langfuse.prism.auto_trace', true)) {
            return;
        }

        $requestId = $this->getRequestId($event);

        if (!isset($this->activeGenerations[$requestId])) {
            return;
        }

        $generationData = $this->activeGenerations[$requestId];
        $generation = $generationData['generation'];
        $startTime = $generationData['start_time'];

        try {
            if ($this->isSuccessful($event)) {
                $this->handleSuccessfulExecution($generation, $event, $startTime);
            } else {
                $this->handleFailedExecution($generation, $event);
            }
        } finally {
            $generation->end();
            unset($this->activeGenerations[$requestId]);
        }
    }

    /**
     * Get request ID from event
     */
    private function getRequestId($event): string
    {
        // Extract unique request ID from event
        if (method_exists($event, 'getId')) {
            return $event->getId();
        }

        if (isset($event->requestId)) {
            return $event->requestId;
        }

        if (method_exists($event, 'getRequest') && method_exists($event->getRequest(), 'getId')) {
            return $event->getRequest()->getId();
        }

        return uniqid('prism_', true);
    }

    /**
     * Generate span name from event
     */
    private function generateSpanName($event): string
    {
        $provider = $this->getProvider($event) ?? 'unknown';
        $model = $this->getModel($event) ?? 'unknown';

        return "prism-{$provider}-{$model}";
    }

    /**
     * Extract input data from event
     */
    private function extractInput($event): array
    {
        $input = [];

        // Extract prompt/messages based on event structure
        if (method_exists($event, 'getPrompt')) {
            $prompt = $event->getPrompt();
            if (is_string($prompt)) {
                $input['prompt'] = $prompt;
            } elseif (is_array($prompt)) {
                $input['messages'] = $prompt;
            }
        }

        if (method_exists($event, 'getMessages')) {
            $input['messages'] = $event->getMessages();
        }

        if (method_exists($event, 'getSystemPrompt')) {
            $input['system_prompt'] = $event->getSystemPrompt();
        }

        // Fallback to generic input extraction
        if (empty($input) && method_exists($event, 'getInput')) {
            $input = $event->getInput();
        }

        return $input;
    }

    /**
     * Extract metadata from event
     */
    private function extractMetadata($event): array
    {
        return [
            'provider' => $this->getProvider($event),
            'langfuse_sdk' => 'langfuse-php',
            'prism_version' => $this->getPrismVersion(),
            'event_type' => get_class($event),
        ];
    }

    /**
     * Get provider from event
     */
    private function getProvider($event): ?string
    {
        if (method_exists($event, 'getProvider')) {
            return $event->getProvider();
        }

        if (method_exists($event, 'getRequest') && method_exists($event->getRequest(), 'getProvider')) {
            return $event->getRequest()->getProvider();
        }

        return null;
    }

    /**
     * Get model from event
     */
    private function getModel($event): ?string
    {
        if (method_exists($event, 'getModel')) {
            return $event->getModel();
        }

        if (method_exists($event, 'getRequest') && method_exists($event->getRequest(), 'getModel')) {
            return $event->getRequest()->getModel();
        }

        return null;
    }

    /**
     * Check if event has model parameters
     */
    private function hasModelParameters($event): bool
    {
        return method_exists($event, 'getParameters') ||
               (method_exists($event, 'getRequest') && method_exists($event->getRequest(), 'getParameters'));
    }

    /**
     * Get model parameters from event
     */
    private function getModelParameters($event): ?array
    {
        if (method_exists($event, 'getParameters')) {
            return $event->getParameters();
        }

        if (method_exists($event, 'getRequest') && method_exists($event->getRequest(), 'getParameters')) {
            return $event->getRequest()->getParameters();
        }

        return null;
    }

    /**
     * Check if execution was successful
     */
    private function isSuccessful($event): bool
    {
        if (method_exists($event, 'isSuccessful')) {
            return $event->isSuccessful();
        }

        if (method_exists($event, 'getException')) {
            return $event->getException() === null;
        }

        if (method_exists($event, 'hasResponse')) {
            return $event->hasResponse();
        }

        return true; // Default to successful
    }

    /**
     * Handle successful execution
     */
    private function handleSuccessfulExecution(SpanInterface $generation, $event, DateTime $startTime): void
    {
        $updateData = [
            'output' => $this->extractOutput($event),
        ];

        // Add usage if available
        if (config('langfuse.prism.trace_usage', true) && $this->hasUsage($event)) {
            $updateData['usageDetails'] = $this->extractUsage($event);
        }

        // Add cost if available
        if (config('langfuse.prism.trace_cost', true) && $this->hasCost($event)) {
            $updateData['costDetails'] = $this->extractCost($event);
        }

        // Estimate completion start time
        if ($this->hasTimingInfo($event)) {
            $updateData['completionStartTime'] = $this->getCompletionStartTime($event, $startTime);
        }

        $generation->update(...$updateData);
    }

    /**
     * Handle failed execution
     */
    private function handleFailedExecution(SpanInterface $generation, $event): void
    {
        $exception = $this->getException($event);

        $generation->update(
            level: SpanLevel::ERROR,
            statusMessage: $exception ? $exception->getMessage() : 'Unknown error',
            metadata: $exception ? [
                'error' => [
                    'type' => get_class($exception),
                    'message' => $exception->getMessage(),
                    'code' => $exception->getCode(),
                ],
            ] : ['error' => 'Unknown error occurred']
        );
    }

    /**
     * Extract output from event
     */
    private function extractOutput($event): array
    {
        if (method_exists($event, 'getResponse')) {
            $response = $event->getResponse();

            if (method_exists($response, 'getText')) {
                return ['text' => $response->getText()];
            }

            if (method_exists($response, 'getContent')) {
                return ['content' => $response->getContent()];
            }
        }

        if (method_exists($event, 'getOutput')) {
            return $event->getOutput();
        }

        return [];
    }

    /**
     * Check if event has usage information
     */
    private function hasUsage($event): bool
    {
        return method_exists($event, 'getUsage') ||
               (method_exists($event, 'getResponse') && method_exists($event->getResponse(), 'getUsage'));
    }

    /**
     * Extract usage information
     */
    private function extractUsage($event): array
    {
        $usage = null;

        if (method_exists($event, 'getUsage')) {
            $usage = $event->getUsage();
        } elseif (method_exists($event, 'getResponse') && method_exists($event->getResponse(), 'getUsage')) {
            $usage = $event->getResponse()->getUsage();
        }

        if (!$usage) {
            return [];
        }

        return [
            'prompt_tokens' => method_exists($usage, 'getPromptTokens') ? $usage->getPromptTokens() : 0,
            'completion_tokens' => method_exists($usage, 'getCompletionTokens') ? $usage->getCompletionTokens() : 0,
            'total_tokens' => method_exists($usage, 'getTotalTokens') ? $usage->getTotalTokens() : 0,
        ];
    }

    /**
     * Check if event has cost information
     */
    private function hasCost($event): bool
    {
        return method_exists($event, 'getCost') ||
               (method_exists($event, 'getResponse') && method_exists($event->getResponse(), 'getCost'));
    }

    /**
     * Extract cost information
     */
    private function extractCost($event): array
    {
        $cost = null;

        if (method_exists($event, 'getCost')) {
            $cost = $event->getCost();
        } elseif (method_exists($event, 'getResponse') && method_exists($event->getResponse(), 'getCost')) {
            $cost = $event->getResponse()->getCost();
        }

        if (!$cost) {
            return [];
        }

        return [
            'input' => method_exists($cost, 'getInputCost') ? $cost->getInputCost() : 0,
            'output' => method_exists($cost, 'getOutputCost') ? $cost->getOutputCost() : 0,
            'total' => method_exists($cost, 'getTotalCost') ? $cost->getTotalCost() : 0,
        ];
    }

    /**
     * Check if event has timing information
     */
    private function hasTimingInfo($event): bool
    {
        return method_exists($event, 'getTimingInfo') ||
               method_exists($event, 'getCompletionStartTime');
    }

    /**
     * Get completion start time
     */
    private function getCompletionStartTime($event, DateTime $startTime): ?DateTime
    {
        if (method_exists($event, 'getCompletionStartTime')) {
            return $event->getCompletionStartTime();
        }

        // Estimate based on total time (assume 10% processing overhead)
        return (clone $startTime)->modify('+0.1 seconds');
    }

    /**
     * Get exception from event
     */
    private function getException($event): ?\Throwable
    {
        if (method_exists($event, 'getException')) {
            return $event->getException();
        }

        return null;
    }

    /**
     * Get Prism version
     */
    private function getPrismVersion(): string
    {
        return 'unknown';
    }
}