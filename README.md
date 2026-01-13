# Laravel Langfuse Prism

[![Latest Version on Packagist](https://img.shields.io/packagist/v/mliviu79/laravel-langfuse-prism.svg?style=flat-square)](https://packagist.org/packages/mliviu79/laravel-langfuse-prism)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/mliviu79/laravel-langfuse-prism/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/mliviu79/laravel-langfuse-prism/actions?query=workflow%3Atests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/mliviu79/laravel-langfuse-prism.svg?style=flat-square)](https://packagist.org/packages/mliviu79/laravel-langfuse-prism)

A comprehensive PHP SDK for the [Langfuse](https://langfuse.com) observability platform, featuring native integration with **Laravel** and **Laravel Prism**.

This package provides a seamless way to trace your LLM applications, monitor costs, and debug complex AI workflows directly within your Laravel application.

## ✨ Features

- 🚀 **Deep Prism Integration**: Automatically traces all Prism LLM operations (text, embeddings, images, audio).
- 🔍 **Full Observability**: Detailed span hierarchy, latency tracking, and error monitoring.
- 💰 **Cost & Usage Tracking**: Automatic token counting and cost calculation for supported models.
- ⚡ **Performance**: Non-blocking OpenTelemetry-based tracing with queued background exports.
- 🎨 **Laravel Native**: Service provider, Facade, config publication, and queue integration.
- 📊 **Media Support**: Handles image, document, and audio inputs/outputs.

## 📦 Installation

```bash
composer require mliviu79/laravel-langfuse-prism
```

### Laravel Setup

1. Publish the configuration file:

```bash
php artisan vendor:publish --tag=langfuse-config
```

2. Add your Langfuse credentials to `.env`:

```env
LANGFUSE_PUBLIC_KEY=pk-lf-your-public-key
LANGFUSE_SECRET_KEY=sk-lf-your-secret-key
LANGFUSE_HOST=https://cloud.langfuse.com
```

## 🚀 Quick Start with Prism

The package automatically detects Prism events. Simply enable it in your config (enabled by default):

```php
// config/langfuse.php
'prism' => [
    'auto_trace' => true,
    'trace_model_params' => true,
    'trace_usage' => true,
    'trace_cost' => true,
],
```

Now, any Prism call is automatically traced:

```php
use Prism\Prism\Facades\Prism;

// This operation will be automatically traced in Langfuse
$response = Prism::text()
    ->using('anthropic', 'claude-3-opus')
    ->withPrompt('Explain quantum computing like I am 5')
    ->generate();
```

### Manual Tracing

For non-Prism logic or custom workflows, use the Facade:

```php
use Langfuse\Integration\Laravel\Facades\Langfuse;

$span = Langfuse::startSpan('my-custom-operation');

try {
    // ... your code ...
    $span->end();
} catch (\Exception $e) {
    $span->update(level: 'ERROR', statusMessage: $e->getMessage());
    $span->end();
    throw $e;
}
```

## ⚠️ Maintenance & Philosophy

This package was originally refactored from `laravel-langfuse` to specifically solve our needs for **native Laravel Prism integration** and **robust OpenTelemetry support**.

We are sharing this in the spirit of open source, hoping it helps others with similar requirements. However, please note:

- **Updates:** We maintain this package primarily to support our own production applications. Updates will be released as our needs evolve.
- **Contributions:** We welcome Pull Requests! If you need a feature we don't use, please submit a PR. We are happy to merge community contributions that align with the package's goals.
- **Forking:** If your use case diverges significantly from ours, we encourage you to fork the repository and adapt it to your needs.

We aim to keep the package stable and usable, but we may not prioritize features that aren't relevant to our internal use cases.

## 🤝 Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## 📄 License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

## 🏆 Credits

- **Original Creator:** [Hanan](mailto:hanan@showdigs.com)
- **Maintainer:** [Liviu Muresan](mailto:muresan_liviu@yahoo.com)

Built with ❤️ for the Laravel community.
