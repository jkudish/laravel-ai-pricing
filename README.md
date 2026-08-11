<h1 align="center">Laravel AI Pricing</h1>

<p align="center">
  <strong>Trustworthy AI cost attribution for Laravel.</strong>
</p>

<p align="center">
  <a href="https://github.com/jkudish/laravel-ai-pricing/actions/workflows/run-tests.yml"><img src="https://github.com/jkudish/laravel-ai-pricing/actions/workflows/run-tests.yml/badge.svg" alt="Tests"></a>
  <a href="https://github.com/jkudish/laravel-ai-pricing/actions/workflows/quality.yml"><img src="https://github.com/jkudish/laravel-ai-pricing/actions/workflows/quality.yml/badge.svg" alt="Quality"></a>
  <a href="LICENSE.md"><img src="https://img.shields.io/github/license/jkudish/laravel-ai-pricing" alt="License"></a>
</p>

Laravel AI Pricing turns a completed Laravel AI response into a cost result you can record, display, or use to enforce a budget. It uses provider-reported cost when Laravel AI exposes it and otherwise attributes cost from the response's billable usage and a versioned pricing source.

## Install

> **Pre-release:** version 0.1 is feature-complete and being prepared for its first Packagist release.

Once version 0.1 is published:

```bash
composer require jkudish/laravel-ai-pricing
```

The service provider is discovered automatically. Start without publishing configuration.

## Use it with Laravel AI

After your agent finishes, ask the package for the cost:

```php
use Jkudish\LaravelAiPricing\Facades\AiPricing;

$response = (new ReceiptOcrAgent)->prompt($prompt);

$cost = AiPricing::cost($response);

$cost->amount;               // Brick\Math\BigDecimal, or null when pricing is unavailable
$cost->currency;             // "USD", or null
$cost->source;               // configured, provider_reported, provider_native, ...
$cost->completeness;         // complete, partial, or unavailable
```

`cost()` is for a completed response. It adapts Laravel AI's public response data, including the effective provider, model, and usage, then resolves the best compatible price. The returned `CostQuote` keeps the underlying `Money` value in `$cost->cost`, while `$cost->amount` and `$cost->currency` are convenient read-only aliases.

For a streamed response, resolve the cost only after the stream has been consumed:

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

## What works by default

No configuration is required to call `AiPricing::cost()`.

- The default currency is USD.
- Laravel AI text and embedding responses are adapted from their public usage and metadata.
- If a matching public catalog price is available, the result is attributed from usage.
- When Laravel AI supplies usage, missing or incomplete pricing returns `unavailable` or `partial`; it never invents a zero cost.
- The package stores no prompts or model outputs, has no database, and performs no currency conversion.

For synchronous OpenRouter text responses on Laravel AI v0.10.3 or later, `usage.cost` in each public raw generation step is treated as provider-reported cost. It wins over every catalog. Other built-in providers currently expose billable usage rather than per-response money, so the package resolves their cost from usage and a configured or remote catalog.

## Configure application prices

Use application pricing when you need a specific model, region, inference profile, internal rate, or deterministic offline behavior. Publish the config:

```bash
php artisan vendor:publish --tag=laravel-ai-pricing-config
```

Rates use a normalized `provider:model` key. Use decimal strings for values and divisors that need precision:

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

Configured prices take precedence over catalog pricing, but never replace a compatible provider-reported cost. This makes configured rates appropriate for Bedrock aliases and region-specific models, or for a known internal price of local workloads such as Ollama.

## Use it in agents, jobs, and evaluations

Treat a result as request evidence, not a current price lookup. Persist it with the work your agent performed:

```php
$response = $agent->prompt($prompt);
$cost = AiPricing::cost($response);

$pricing = [
    'provider' => $response->meta->provider,
    'model' => $response->meta->model,
    'pricing' => $cost->toArray(),
];

// Store $pricing with the application record that represents this run.
```

`toArray()` includes a `cost` object with amount and currency (or `null`), plus source, completeness, missing units, pricing snapshot, and provenance when available. A later catalog update therefore does not rewrite the evidence you recorded for an earlier request.

This fits naturally in queued jobs, agent loops, evaluation suites, and budget reporting. For workloads with no Laravel AI response, use the lower-level resolver and an explicit `PricingObservation`.

## Estimate before a request

Use `quote()` when you want an estimate before a request is sent. It never claims to be what a provider charged:

```php
use Jkudish\LaravelAiPricing\Facades\AiPricing;
use Jkudish\LaravelAiPricing\ValueObjects\Usage;

$quote = AiPricing::quote(
    provider: 'openai',
    model: 'gpt-5-mini',
    usage: Usage::tokens(input: 1_000, output: 250),
);

if ($quote->amount !== null && $quote->amount->isLessThan('0.01')) {
    // Safe to continue according to this estimate.
}
```

Use the completed response's `cost()` result for accounting. A quote is useful for guardrails, model selection, and expected-cost UI.

## Pricing sources and accuracy

The first compatible source wins:

1. Provider-reported cost supplied with the response.
2. Application-configured pricing.
3. Provider-native pricing supplied with the observation.
4. OpenRouter's public model catalog for OpenRouter identities.
5. Portkey's public provider catalogs as a fallback.
6. An explicit unavailable result.

Catalog pricing in another currency is not silently converted or attributed. Decimal-safe arithmetic is used throughout; apply rounding explicitly at your display or billing boundary.

Laravel AI's built-in OpenAI, Anthropic, Gemini, Groq, DeepSeek, Mistral, xAI, Cohere, and Bedrock providers currently provide usage quantities rather than response-level monetary cost. The package can match those provider and model identities to catalog prices. Embeddings expose input-token usage; image and transcription responses use their public `Usage` objects. Audio and reranking responses expose no billable quantity today, so supply an explicit workload unit and configured rate if you have one. The package does not infer a monetary cost for local Ollama execution.

Failed or blocked requests may still be billable while provider usage is unavailable. Do not record those as zero-cost.

## Catalogs and offline operation

Prewarm the remote catalogs you use:

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

Offline mode performs no network retrieval. It uses configured prices and previously cached last-known-good catalogs. Remote-derived results retain the sanitized source URL, retrieval time, normalized definition, and snapshot fingerprint.

## Precision and rounding

Aggregate precise `Money` values before applying an explicit boundary:

```php
use Brick\Math\RoundingMode;
use Jkudish\LaravelAiPricing\Enums\RoundingBoundary;

$display = $cost->cost?->at(
    RoundingBoundary::Display,
    RoundingMode::HalfUp,
);
```

Mixed-currency arithmetic throws. USD is the default and version 0.1 performs no foreign exchange conversion.

## Compatibility

- PHP 8.4 and newer.
- Laravel 13 for the full development and test matrix.
- Laravel 12 for clean consumer installation, package discovery, and command registration.
- No runtime dependency on Pest or Laravel AI.

The development suite uses Pest 5, PHPUnit 13, Laravel 13, and Testbench 11. Because Pest 5's Symfony Process constraint conflicts with Laravel 12's development stack, CI verifies Laravel 12 through a clean consumer installation without this repository's development dependencies.

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
