<h1 align="center">Laravel AI Pricing</h1>

<p align="center">
  <strong>Trustworthy AI cost attribution for Laravel.</strong>
</p>

<p align="center">
  <a href="https://github.com/jkudish/laravel-ai-pricing/actions/workflows/run-tests.yml"><img src="https://github.com/jkudish/laravel-ai-pricing/actions/workflows/run-tests.yml/badge.svg" alt="Tests"></a>
  <a href="https://github.com/jkudish/laravel-ai-pricing/actions/workflows/quality.yml"><img src="https://github.com/jkudish/laravel-ai-pricing/actions/workflows/quality.yml/badge.svg" alt="Quality"></a>
  <a href="LICENSE.md"><img src="https://img.shields.io/github/license/jkudish/laravel-ai-pricing" alt="License"></a>
</p>

Laravel AI Pricing tells you what an AI request cost, or what a future request is expected to cost. Use it after a Laravel AI response with `cost()`. Use `quote()` before a request when you need an estimate for a budget, model picker, or guardrail.

## Installation

> **Pre-release:** version 0.1 is feature-complete and being prepared for its first Packagist release.

Once version 0.1 is published, install the package with Composer:

```bash
composer require jkudish/laravel-ai-pricing
```

Laravel discovers the service provider automatically. You do not need to publish the configuration to get started.

## Costing Laravel AI responses

Call `AiPricing::cost()` after an agent has completed its request:

```php
use Jkudish\LaravelAiPricing\Facades\AiPricing;

$response = (new ReceiptOcrAgent)->prompt($prompt);

$cost = AiPricing::cost($response);

$cost->amount;       // Brick\Math\BigDecimal, or null
$cost->currency;     // "USD", or null
$cost->source;       // Where the amount came from
$cost->completeness; // complete, partial, or unavailable
```

`cost()` reads the response's public provider, model, and usage data. It then returns a `CostQuote`.

If `$cost->amount` is `null`, the package did not have enough compatible pricing information. It does not return `0` for an unknown cost.

### What does “cost” mean?

A cost result is either provider-reported or calculated:

| Result | Meaning |
| --- | --- |
| `provider_reported` | The provider returned a monetary amount with the response. This is the closest thing to an invoice amount. |
| `configured` | Your application supplied the model's rates in `config/ai-pricing.php`. |
| `provider_native` or `portkey` | The package multiplied the response's billable usage by a published price from a remote catalog. |
| `unavailable` | No compatible price was found. |

Most providers return token counts, not dollars. For those responses, Laravel AI Pricing calculates an amount from the token usage and a price list. That calculated result is useful for application accounting, budgets, and reporting. It is not a replacement for a provider invoice.

## Price catalogs

A price catalog is a list of published rates for provider models. A rate says how much one unit costs, such as one million input tokens or one million output tokens.

When a response has usage but no provider-reported amount, the package looks up the response's provider and model in a catalog, then calculates:

```text
cost = input usage × input rate + output usage × output rate
```

The package checks prices in this order:

1. A provider-reported cost attached to the response.
2. A price configured by your application.
3. Provider-native pricing attached to the observation.
4. OpenRouter's public model catalog for OpenRouter models.
5. Portkey's public provider catalog as a fallback.
6. An unavailable result.

The first compatible price wins. The package does not convert currencies. A USD result only uses USD pricing.

## Default behavior

You can call `AiPricing::cost()` without configuration.

- The default currency is USD.
- Laravel AI text and embedding responses are adapted from their public usage and metadata.
- OpenRouter text responses can use provider-reported `usage.cost` when Laravel AI exposes it.
- Other built-in providers usually expose usage only. The package uses a compatible catalog price when one is available.
- The package does not store prompts or model output, create database records, or make foreign-exchange conversions.

If you need an exact application rate, a region-specific Bedrock rate, or a known internal rate for a local model, configure the price yourself.

## Configuring prices

Publish the configuration file when your application needs its own price list:

```bash
php artisan vendor:publish --tag=laravel-ai-pricing-config
```

Prices use a `provider:model` key. Use decimal strings for rates and divisors:

```php
// config/ai-pricing.php
'prices' => [
    'openai:gpt-5-mini' => [
        'input_tokens' => [
            'amount' => '0.25',
            'per' => '1000000',
            'currency' => 'USD',
        ],
        'output_tokens' => [
            'amount' => '2.00',
            'per' => '1000000',
            'currency' => 'USD',
        ],
    ],
],
```

Configured prices are used before remote catalogs. Provider-reported cost still takes precedence because it describes the completed request.

## Quoting a request before it runs

Use `quote()` when you know the provider, model, and expected usage before a request is sent:

```php
use Jkudish\LaravelAiPricing\Facades\AiPricing;
use Jkudish\LaravelAiPricing\ValueObjects\Usage;

$quote = AiPricing::quote(
    provider: 'openai',
    model: 'gpt-5-mini',
    usage: Usage::tokens(input: 1_000, output: 250),
);

if ($quote->amount !== null && $quote->amount->isLessThan('0.01')) {
    // Continue with this estimated request.
}
```

A quote uses the same pricing sources and calculation rules as `cost()`, but it has no completed response and no provider-reported amount. Use a cost result for accounting after a request finishes. Use a quote to decide whether to send a request.

## Streaming responses

For streamed responses, wait until the stream has been consumed before resolving its cost:

```php
$response = (new ReceiptOcrAgent)->stream($prompt);

$response->then(function ($response): void {
    $cost = AiPricing::cost($response);

    // Persist $cost->toArray() with your application's job or agent-run record.
});

foreach ($response as $event) {
    // Send each stream event to the client.
}
```

## Recording costs

Store the result with the application record that represents the request, job, evaluation, or agent run:

```php
$response = $agent->prompt($prompt);
$cost = AiPricing::cost($response);

$pricing = [
    'provider' => $response->meta->provider,
    'model' => $response->meta->model,
    'pricing' => $cost->toArray(),
];
```

`toArray()` includes the cost object, source, completeness, missing units, pricing snapshot, and provenance when available. Saving that data preserves how you arrived at an earlier result, even if a catalog changes later.

Failed or blocked requests can still be billable when a provider does not expose usage. Do not record those requests as zero-cost.

## Catalog caching and offline mode

Prewarm the remote catalogs your application uses:

```bash
php artisan ai:pricing:sync
```

```php
// config/ai-pricing.php
'offline' => true,
'cache_store' => null,
'cache_ttl' => 86_400,
'portkey' => [
    'endpoint' => 'https://configs.portkey.ai/pricing/{provider}.json',
    'providers' => ['openai', 'anthropic'],
],
```

Offline mode makes no network requests. It uses configured prices and cached catalogs from earlier successful lookups.

## Precision and rounding

Amounts use `brick/math` decimals. Round only when you cross a display or billing boundary:

```php
use Brick\Math\RoundingMode;
use Jkudish\LaravelAiPricing\Enums\RoundingBoundary;

$display = $cost->cost?->at(
    RoundingBoundary::Display,
    RoundingMode::HalfUp,
);
```

Mixed-currency arithmetic throws an exception. Version 0.1 does not perform foreign-exchange conversion.

## Compatibility

- PHP 8.4 and newer.
- Laravel 13 for the full development and test matrix.
- Laravel 12 for clean consumer installation, package discovery, and command registration.
- No runtime dependency on Pest or Laravel AI.

## Stability

The package follows Semantic Versioning. The public API may evolve between minor releases before `1.0.0`; breaking changes will be documented in the [changelog](CHANGELOG.md).

## Roadmap

See [roadmap.md](roadmap.md) for planned custom catalogs, provider-native sources, pricing freshness diagnostics, and quote inspection tooling.

## Contributing

Contributions are welcome. See [CONTRIBUTING.md](CONTRIBUTING.md) and the [Code of Conduct](CODE_OF_CONDUCT.md).

## Security

Please report vulnerabilities privately according to [SECURITY.md](SECURITY.md).

## Sponsoring

If this package helps your work, consider [sponsoring its development](https://github.com/sponsors/jkudish).

## License

Laravel AI Pricing is open-source software licensed under the [MIT license](LICENSE.md).
