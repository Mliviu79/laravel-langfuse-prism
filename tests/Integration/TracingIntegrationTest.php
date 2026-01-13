<?php

declare(strict_types=1);

namespace Langfuse\Tests\Integration;

use DateTime;
use Langfuse\Client\Contracts\LangfuseClientInterface;
use Langfuse\Observability\Contracts\SpanInterface;
use Langfuse\Observability\Contracts\TracerInterface;
use Langfuse\Observability\Spans\OpenTelemetrySpan;
use Langfuse\Support\Enums\ObservationType;
use Langfuse\Support\Enums\SpanLevel;
use Langfuse\Tests\TestCase;

/**
 * Integration tests for the full tracing workflow.
 * 
 * These tests verify that spans are created, updated, and exported
 * to the OTEL Collector at cloud.langfuse.com.
 */
class TracingIntegrationTest extends TestCase
{
    private LangfuseClientInterface $langfuse;
    private TracerInterface $tracer;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Use real services from the container
        $this->langfuse = $this->app->make(LangfuseClientInterface::class);
        $this->tracer = $this->app->make(TracerInterface::class);
    }

    protected function tearDown(): void
    {
        // Ensure all spans are flushed
        $this->langfuse->flush();
        parent::tearDown();
    }

    /**
     * @group integration
     */
    public function test_create_simple_span(): void
    {
        $span = $this->langfuse->startSpan(
            name: 'integration-test-simple-span',
            type: ObservationType::SPAN,
            input: ['test' => 'input'],
        );

        $this->assertInstanceOf(SpanInterface::class, $span);
        $this->assertNotEquals('null', $span->getId());
        $this->assertNotEquals('null', $span->getTraceId());
        $this->assertEquals('integration-test-simple-span', $span->getName());

        $span->end();
    }

    /**
     * @group integration
     */
    public function test_create_generation_span_with_full_attributes(): void
    {
        $span = $this->langfuse->startSpan(
            name: 'integration-test-generation',
            type: ObservationType::GENERATION,
            input: ['prompt' => 'What is the capital of France?'],
            metadata: [
                'user_id' => 'test-user-123',
                'session_id' => 'test-session-456',
            ],
        );

        // Update with model and output
        $span->update(
            output: ['text' => 'The capital of France is Paris.'],
            model: 'gpt-4',
            modelParameters: [
                'temperature' => 0.7,
                'max_tokens' => 100,
            ],
            usageDetails: [
                'input' => 10,
                'output' => 15,
                'total' => 25,
                'unit' => 'TOKENS',
            ],
        );

        $span->end();

        // Flush to ensure export
        $this->langfuse->flush();

        $this->assertTrue(true); // Test passed if no exception
    }

    /**
     * @group integration
     */
    public function test_create_parent_child_spans(): void
    {
        // Create parent span
        $parentSpan = $this->langfuse->startSpan(
            name: 'integration-test-parent',
            type: ObservationType::SPAN,
            input: ['operation' => 'parent'],
        );

        $parentId = $parentSpan->getId();
        $traceId = $parentSpan->getTraceId();

        // Create child span
        $childSpan = $parentSpan->startObservation(
            name: 'integration-test-child',
            type: ObservationType::GENERATION,
            input: ['prompt' => 'Child operation'],
        );

        // Child should share the same trace ID
        $this->assertEquals($traceId, $childSpan->getTraceId());
        
        // Update and end child
        $childSpan->update(
            output: ['result' => 'Child completed'],
            model: 'gpt-4',
        );
        $childSpan->end();

        // Update and end parent
        $parentSpan->update(
            output: ['result' => 'Parent completed'],
        );
        $parentSpan->end();

        $this->langfuse->flush();
        $this->assertTrue(true);
    }

    /**
     * @group integration
     */
    public function test_add_score_to_span(): void
    {
        $span = $this->langfuse->startSpan(
            name: 'integration-test-scored-span',
            type: ObservationType::GENERATION,
            input: ['prompt' => 'Score test'],
        );

        $span->update(
            output: ['response' => 'Test response'],
            model: 'gpt-4',
        );

        // Add observation-level score
        $score = $span->score(
            name: 'accuracy',
            value: 0.95,
            comment: 'High accuracy response'
        );

        $this->assertNotNull($score);
        $this->assertEquals('accuracy', $score->name);
        $this->assertEquals(0.95, $score->value);
        $this->assertEquals($span->getTraceId(), $score->traceId);
        $this->assertEquals($span->getId(), $score->observationId);

        $span->end();
        $this->langfuse->flush();
    }

    /**
     * @group integration
     */
    public function test_add_trace_level_score(): void
    {
        $span = $this->langfuse->startSpan(
            name: 'integration-test-trace-scored',
            type: ObservationType::SPAN,
            input: ['operation' => 'trace score test'],
        );

        // Add trace-level score
        $score = $span->scoreTrace(
            name: 'user_satisfaction',
            value: true,
            comment: 'User liked the response'
        );

        $this->assertNotNull($score);
        $this->assertEquals('user_satisfaction', $score->name);
        $this->assertTrue($score->value);
        $this->assertEquals($span->getTraceId(), $score->traceId);
        $this->assertNull($score->observationId); // Trace-level has no observation

        $span->end();
        $this->langfuse->flush();
    }

    /**
     * @group integration
     */
    public function test_update_trace_attributes(): void
    {
        $span = $this->langfuse->startSpan(
            name: 'integration-test-trace-update',
            type: ObservationType::SPAN,
        );

        // Update trace-level attributes
        $span->updateTrace(
            name: 'Custom Trace Name',
            userId: 'user-123',
            sessionId: 'session-456',
            version: '1.0.0',
            input: ['initial' => 'input'],
            output: ['final' => 'output'],
            metadata: ['env' => 'testing'],
            tags: ['integration-test', 'tracing'],
            public: false,
        );

        $span->end();
        $this->langfuse->flush();
        $this->assertTrue(true);
    }

    /**
     * @group integration
     */
    public function test_span_with_error_status(): void
    {
        $span = $this->langfuse->startSpan(
            name: 'integration-test-error-span',
            type: ObservationType::GENERATION,
            input: ['prompt' => 'This will fail'],
        );

        // Simulate an error
        $span->update(
            level: SpanLevel::ERROR,
            statusMessage: 'API rate limit exceeded',
            metadata: [
                'error' => [
                    'type' => 'RateLimitException',
                    'code' => 429,
                ],
            ],
        );

        $span->end();
        $this->langfuse->flush();
        $this->assertTrue(true);
    }

    /**
     * @group integration
     */
    public function test_multiple_spans_in_sequence(): void
    {
        $spans = [];

        // Create multiple spans
        for ($i = 1; $i <= 3; $i++) {
            $span = $this->langfuse->startSpan(
                name: "integration-test-sequence-{$i}",
                type: ObservationType::SPAN,
                input: ['iteration' => $i],
            );

            $span->update(
                output: ['result' => "Completed iteration {$i}"],
            );

            $spans[] = $span;
        }

        // End all spans
        foreach ($spans as $span) {
            $span->end();
        }

        $this->langfuse->flush();
        $this->assertCount(3, $spans);
    }

    /**
     * @group integration
     */
    public function test_embedding_span_type(): void
    {
        $span = $this->langfuse->startSpan(
            name: 'integration-test-embedding',
            type: ObservationType::EMBEDDING,
            input: ['texts' => ['Hello world', 'Goodbye world']],
        );

        $span->update(
            model: 'text-embedding-3-small',
            output: [
                'embeddings_count' => 2,
                'dimensions' => 1536,
            ],
            usageDetails: [
                'input' => 8,
                'output' => 0,
                'total' => 8,
                'unit' => 'TOKENS',
            ],
        );

        $span->end();
        $this->langfuse->flush();
        $this->assertEquals(ObservationType::EMBEDDING, $span->getType());
    }

    /**
     * @group integration
     */
    public function test_complex_nested_trace(): void
    {
        // Root span
        $rootSpan = $this->langfuse->startSpan(
            name: 'integration-test-complex-root',
            type: ObservationType::SPAN,
            input: ['operation' => 'complex workflow'],
        );

        // First level child - pre-processing
        $preprocessSpan = $rootSpan->startObservation(
            name: 'preprocess',
            type: ObservationType::SPAN,
            input: ['step' => 'preprocessing'],
        );
        $preprocessSpan->update(output: ['status' => 'preprocessed']);
        $preprocessSpan->end();

        // First level child - LLM call
        $llmSpan = $rootSpan->startObservation(
            name: 'llm-call',
            type: ObservationType::GENERATION,
            input: ['prompt' => 'Process this data'],
        );
        
        $llmSpan->update(
            model: 'gpt-4',
            output: ['text' => 'Processed result'],
            usageDetails: ['input' => 20, 'output' => 50, 'total' => 70],
        );
        
        // Add score to LLM call
        $llmSpan->score('quality', 0.9);
        $llmSpan->end();

        // First level child - post-processing
        $postprocessSpan = $rootSpan->startObservation(
            name: 'postprocess',
            type: ObservationType::SPAN,
            input: ['step' => 'postprocessing'],
        );
        $postprocessSpan->update(output: ['status' => 'completed']);
        $postprocessSpan->end();

        // Complete root span
        $rootSpan->update(output: ['workflow' => 'completed']);
        $rootSpan->scoreTrace('success', true);
        $rootSpan->end();

        $this->langfuse->flush();
        $this->assertTrue(true);
    }

    /**
     * @group integration
     */
    public function test_flush_and_shutdown(): void
    {
        $span = $this->langfuse->startSpan(
            name: 'integration-test-flush-shutdown',
            type: ObservationType::SPAN,
        );

        $span->end();

        // Test flush
        $this->langfuse->flush();

        // Test shutdown (should not throw)
        $this->langfuse->shutdown();

        $this->assertTrue(true);
    }
}
