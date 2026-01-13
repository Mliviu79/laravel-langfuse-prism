<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Langfuse Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the Langfuse PHP SDK. Most settings can be overridden
    | via environment variables for security and deployment flexibility.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | API Credentials
    |--------------------------------------------------------------------------
    |
    | Your Langfuse API credentials. These should be set via environment
    | variables for security. Get these from your Langfuse dashboard.
    |
    */
    'public_key' => env('LANGFUSE_PUBLIC_KEY'),
    'secret_key' => env('LANGFUSE_SECRET_KEY'),

    /*
    |--------------------------------------------------------------------------
    | API Host
    |--------------------------------------------------------------------------
    |
    | The Langfuse API host URL. Defaults to the Langfuse cloud service.
    | Change this if you're using a self-hosted instance.
    |
    */
    'host' => env('LANGFUSE_HOST', 'https://cloud.langfuse.com'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | Timeout in seconds for API requests. Adjust based on your network
    | conditions and requirements.
    |
    */
    'timeout' => (int) env('LANGFUSE_TIMEOUT', 5),

    /*
    |--------------------------------------------------------------------------
    | Debug Mode
    |--------------------------------------------------------------------------
    |
    | Enable debug logging for troubleshooting. This will log additional
    | information about SDK operations.
    |
    */
    'debug' => (bool) env('LANGFUSE_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Tracing Configuration
    |--------------------------------------------------------------------------
    |
    | Controls whether tracing is enabled and other tracing-related settings.
    | - tracing_enabled: Master switch for all tracing functionality
    | - environment: Environment name for grouping traces
    | - sample_rate: Percentage of traces to sample (0.0 to 1.0)
    |
    */
    'tracing_enabled' => (bool) env('LANGFUSE_TRACING_ENABLED', true),
    'environment' => env('LANGFUSE_TRACING_ENVIRONMENT', 'production'),
    'sample_rate' => (float) env('LANGFUSE_SAMPLE_RATE', 1.0),

    /*
    |--------------------------------------------------------------------------
    | Release Information
    |--------------------------------------------------------------------------
    |
    | Optional release version or hash for grouping traces by deployment.
    | Useful for tracking performance across different releases.
    |
    */
    'release' => env('LANGFUSE_RELEASE'),

    /*
    |--------------------------------------------------------------------------
    | Media Upload Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for handling media uploads (images, documents, etc.).
    | - thread_count: Number of background threads for media processing
    |
    */
    'media_upload_thread_count' => (int) env('LANGFUSE_MEDIA_UPLOAD_THREAD_COUNT', 1),

    /*
    |--------------------------------------------------------------------------
    | Additional Headers
    |--------------------------------------------------------------------------
    |
    | Additional HTTP headers to include with all API requests.
    | Useful for proxy authentication or custom routing.
    |
    */
    'additional_headers' => [],

    /*
    |--------------------------------------------------------------------------
    | Blocked Instrumentation Scopes
    |--------------------------------------------------------------------------
    |
    | List of instrumentation scope names to block from being exported.
    | Useful for filtering out spans from specific libraries or frameworks.
    |
    */
    'blocked_instrumentation_scopes' => [],

    /*
    |--------------------------------------------------------------------------
    | Prism Integration
    |--------------------------------------------------------------------------
    |
    | Configuration for automatic Prism observability integration.
    |
    */
    'prism' => [
        'auto_trace' => (bool) env('LANGFUSE_PRISM_AUTO_TRACE', true),
        'trace_model_params' => (bool) env('LANGFUSE_PRISM_TRACE_MODEL_PARAMS', true),
        'trace_usage' => (bool) env('LANGFUSE_PRISM_TRACE_USAGE', true),
        'trace_cost' => (bool) env('LANGFUSE_PRISM_TRACE_COST', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | OpenTelemetry Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for OpenTelemetry OTLP exporter.
    |
    | otel_enabled: Set to false to completely disable the built-in OpenTelemetry
    | integration. Useful if you're using another OTEL package like
    | keepsuit/laravel-opentelemetry. When disabled, a null tracer is used.
    |
    */
    'otel_enabled' => env('LANGFUSE_OTEL_ENABLED', true),
    'otel_endpoint' => env('LANGFUSE_OTEL_ENDPOINT', env('LANGFUSE_HOST', 'https://cloud.langfuse.com') . '/api/public/otel'),
    'otel_protocol' => env('LANGFUSE_OTEL_PROTOCOL', env('OTEL_EXPORTER_OTLP_PROTOCOL', 'http/json')),

    /*
    |--------------------------------------------------------------------------
    | OpenTelemetry Span Processor Configuration
    |--------------------------------------------------------------------------
    |
    | The SDK automatically chooses the optimal processor based on context:
    | - HTTP Requests: BatchSpanProcessor (non-blocking, efficient)
    | - Queue Jobs/CLI: SimpleSpanProcessor (blocking, reliable)
    |
    | SimpleSpanProcessor:
    | - Exports each span immediately when it ends (blocks ~50ms)
    | - Used automatically for queue workers and CLI commands
    | - Ensures spans are exported before job terminates
    | - Reliability > Performance
    |
    | BatchSpanProcessor:
    | - Accumulates spans and exports in batches (non-blocking)
    | - Used automatically for HTTP requests
    | - Exports in background every N ms or when batch fills
    | - Performance > Latency
    |
    | You can override the automatic detection:
    | - Set to true: Always use SimpleProcessor (immediate export)
    | - Set to false: Always use BatchProcessor (batched export)
    | - Leave unset: Auto-detect based on php_sapi_name()
    |
    */
    'otel_use_simple_processor' => env('LANGFUSE_OTEL_USE_SIMPLE_PROCESSOR'),
    'otel_max_queue_size' => (int) env('LANGFUSE_OTEL_MAX_QUEUE_SIZE', 2048),
    'otel_schedule_delay' => (int) env('LANGFUSE_OTEL_SCHEDULE_DELAY', 1000), // Changed from 5000 to 1000ms
    'otel_export_timeout' => (int) env('LANGFUSE_OTEL_EXPORT_TIMEOUT', 30000),
    'otel_max_export_batch_size' => (int) env('LANGFUSE_OTEL_MAX_EXPORT_BATCH_SIZE', 512),
];