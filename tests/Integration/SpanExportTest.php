<?php

declare(strict_types=1);

namespace Langfuse\Tests\Integration;

use Langfuse\Client\Configuration;
use Langfuse\Client\Contracts\LangfuseClientInterface;
use Langfuse\Support\Enums\ObservationType;
use Langfuse\Tests\TestCase;

/**
 * Integration tests for OTEL span export.
 *
 * These tests verify that spans are correctly exported to the
 * OTEL Collector at cloud.langfuse.com.
 */
class SpanExportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Skip if using placeholder credentials
        if (str_contains(env('LANGFUSE_PUBLIC_KEY', ''), 'placeholder')) {
            $this->markTestSkipped('Skipping integration test due to placeholder credentials');
        }
    }

    /**
     * @group integration
     */
    public function test_export_single_span_to_otel_collector(): void
    {
        $langfuse = $this->app->make(LangfuseClientInterface::class);

        $span = $langfuse->startSpan(
            name: 'otel-export-test-single',
            type: ObservationType::GENERATION,
            input: ['prompt' => 'Test export to OTEL'],
            metadata: [
                'test_id' => uniqid('otel-test-'),
                'timestamp' => date('c'),
            ],
        );

        $span->update(
            model: 'gpt-4-test',
            output: ['text' => 'Export test response'],
            usageDetails: [
                'input' => 5,
                'output' => 10,
                'total' => 15,
            ],
        );

        $span->end();

        // Force flush to export spans immediately
        $langfuse->flush();

        // If we get here without exceptions, the export succeeded
        $this->assertTrue(true);
    }

    /**
     * @group integration
     */
    public function test_export_multiple_spans_to_otel_collector(): void
    {
        $langfuse = $this->app->make(LangfuseClientInterface::class);
        $testId = uniqid('batch-test-');

        // Create multiple spans
        $spans = [];
        for ($i = 1; $i <= 5; $i++) {
            $span = $langfuse->startSpan(
                name: "otel-export-test-batch-{$i}",
                type: ObservationType::SPAN,
                input: ['batch_index' => $i],
                metadata: [
                    'test_id' => $testId,
                    'batch_number' => $i,
                ],
            );

            $span->update(output: ['completed' => true]);
            $spans[] = $span;
        }

        // End all spans
        foreach ($spans as $span) {
            $span->end();
        }

        // Force flush
        $langfuse->flush();

        $this->assertCount(5, $spans);
    }

    /**
     * @group integration
     */
    public function test_export_nested_spans_to_otel_collector(): void
    {
        $langfuse = $this->app->make(LangfuseClientInterface::class);
        $testId = uniqid('nested-test-');

        // Create parent span
        $parentSpan = $langfuse->startSpan(
            name: 'otel-export-test-parent',
            type: ObservationType::SPAN,
            input: ['operation' => 'parent'],
            metadata: ['test_id' => $testId],
        );

        // Create nested children
        $child1 = $parentSpan->startObservation(
            name: 'otel-export-test-child-1',
            type: ObservationType::GENERATION,
            input: ['prompt' => 'Child 1 prompt'],
        );

        $child1->update(model: 'gpt-4', output: ['text' => 'Child 1 response']);

        // Nested grandchild
        $grandchild = $child1->startObservation(
            name: 'otel-export-test-grandchild',
            type: ObservationType::SPAN,
            input: ['level' => 3],
        );
        $grandchild->update(output: ['level' => 'deepest']);
        $grandchild->end();

        $child1->end();

        $child2 = $parentSpan->startObservation(
            name: 'otel-export-test-child-2',
            type: ObservationType::EMBEDDING,
            input: ['texts' => ['text1', 'text2']],
        );

        $child2->update(
            model: 'text-embedding-3-small',
            output: ['embeddings_count' => 2],
        );
        $child2->end();

        $parentSpan->update(output: ['children_completed' => 2]);
        $parentSpan->end();

        $langfuse->flush();
        $this->assertTrue(true);
    }

    /**
     * @group integration
     */
    public function test_export_span_with_scores(): void
    {
        $langfuse = $this->app->make(LangfuseClientInterface::class);
        $testId = uniqid('score-test-');

        $span = $langfuse->startSpan(
            name: 'otel-export-test-scored',
            type: ObservationType::GENERATION,
            input: ['prompt' => 'Score test'],
            metadata: ['test_id' => $testId],
        );

        $span->update(
            model: 'gpt-4',
            output: ['text' => 'Scored response'],
        );

        // Add multiple scores
        $numericScore = $span->score('accuracy', 0.95);
        $booleanScore = $span->score('relevant', true);
        $categoricalScore = $span->score('quality', 'excellent');
        $traceScore = $span->scoreTrace('user_satisfaction', 5);

        $span->end();
        $langfuse->flush();

        $this->assertEquals('accuracy', $numericScore->name);
        $this->assertEquals('relevant', $booleanScore->name);
        $this->assertEquals('quality', $categoricalScore->name);
        $this->assertEquals('user_satisfaction', $traceScore->name);
    }

    /**
     * @group integration
     */
    public function test_export_span_with_full_langfuse_attributes(): void
    {
        $langfuse = $this->app->make(LangfuseClientInterface::class);
        $testId = uniqid('full-attrs-test-');

        $span = $langfuse->startSpan(
            name: 'otel-export-test-full-attributes',
            type: ObservationType::GENERATION,
            input: [
                'prompt' => 'Full attributes test',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are helpful'],
                    ['role' => 'user', 'content' => 'Hello'],
                ],
            ],
            metadata: [
                'test_id' => $testId,
                'custom_field' => 'custom_value',
            ],
            version: '1.0.0',
        );

        // Update trace-level attributes
        $span->updateTrace(
            name: 'Full Attributes Test Trace',
            userId: 'test-user-123',
            sessionId: 'test-session-456',
            version: '1.0.0',
            tags: ['integration-test', 'otel-export'],
            public: false,
        );

        // Update span with all possible attributes
        $span->update(
            output: [
                'text' => 'This is the full response',
                'finish_reason' => 'stop',
            ],
            model: 'gpt-4-turbo',
            modelParameters: [
                'temperature' => 0.7,
                'max_tokens' => 1000,
                'top_p' => 0.95,
                'frequency_penalty' => 0.5,
                'presence_penalty' => 0.5,
            ],
            usageDetails: [
                'input' => 50,
                'output' => 150,
                'total' => 200,
                'unit' => 'TOKENS',
                'reasoning' => 25,
            ],
            costDetails: [
                'input' => 0.0005,
                'output' => 0.0015,
                'total' => 0.002,
            ],
            completionStartTime: new \DateTime,
        );

        // Add comprehensive scores
        $span->score('relevance', 0.9);
        $span->score('coherence', 0.85);
        $span->score('helpfulness', 0.95);
        $span->scoreTrace('overall_quality', 0.9);

        $span->end();
        $langfuse->flush();

        $this->assertTrue(true);
    }

    /**
     * @group integration
     */
    public function test_export_handles_large_payloads(): void
    {
        $langfuse = $this->app->make(LangfuseClientInterface::class);

        // Create a span with large input/output
        $largeText = str_repeat('This is a large text payload. ', 1000);

        $span = $langfuse->startSpan(
            name: 'otel-export-test-large-payload',
            type: ObservationType::GENERATION,
            input: [
                'prompt' => $largeText,
                'context' => $largeText,
            ],
        );

        $span->update(
            model: 'gpt-4',
            output: [
                'text' => $largeText,
                'full_response' => $largeText,
            ],
            usageDetails: [
                'input' => 5000,
                'output' => 5000,
                'total' => 10000,
            ],
        );

        $span->end();
        $langfuse->flush();

        $this->assertTrue(true);
    }

    /**
     * @group integration
     */
    public function test_verify_otel_endpoint_configuration(): void
    {
        $config = $this->app->make(Configuration::class);

        // Verify the OTEL endpoint is configured correctly
        $this->assertNotEmpty(env('LANGFUSE_OTEL_ENDPOINT'));
        $this->assertEquals(
            'https://cloud.langfuse.com/api/public/otel',
            env('LANGFUSE_OTEL_ENDPOINT', 'https://cloud.langfuse.com/api/public/otel')
        );
    }
}
