<h1 align="center">Laravel AI Pricing</h1>

<p align="center">
  <strong>Trustworthy AI cost attribution for Laravel, with decimal-safe math, explicit uncertainty, and immutable pricing provenance.</strong>
</p>

<p align="center">
  <a href="https://github.com/jkudish/laravel-ai-pricing/actions/workflows/run-tests.yml"><img src="https://github.com/jkudish/laravel-ai-pricing/actions/workflows/run-tests.yml/badge.svg" alt="Tests"></a>
  <a href="https://github.com/jkudish/laravel-ai-pricing/actions/workflows/quality.yml"><img src="https://github.com/jkudish/laravel-ai-pricing/actions/workflows/quality.yml/badge.svg" alt="Quality"></a>
  <a href="LICENSE.md"><img src="https://img.shields.io/github/license/jkudish/laravel-ai-pricing" alt="License"></a>
</p>

AI providers report usage and pricing in different shapes, units, currencies, and levels of completeness. Laravel AI Pricing normalizes that evidence and resolves a cost quote without pretending missing information is zero.

The package has no database, records no prompts or outputs, performs no currency conversion, and never requires pricing to be available for the caller to continue.

## Installation

> **Pre-release:** version 0.1 is feature-complete and being prepared for its first Packagist release.

Once version 0.1 is published:

```bash
composer require jkudish/laravel-ai-pricing
```

The service provider is discovered automatically. Publish the configuration only when you need custom prices, endpoints, cache settings, or offline mode:

```bash
php artisan vendor:publish --tag=laravel-ai-pricing-config
```

## Quick start

```php
use Jkudish\LaravelAiPricing\Contracts\CostResolver;
use Jkudish\LaravelAiPricing\ValueObjects\ModelIdentity;
use Jkudish\LaravelAiPricing\ValueObjects\PricingObservation;
use Jkudish\LaravelAiPricing\ValueObjects\Usage;

$quote = app(CostResolver::class)->resolve(new PricingObservation(
    identity: new ModelIdentity('openrouter', 'google/gemini-3-flash'),
    usage: Usage::tokens(input: 1_000, output: 250),
));

$quote->completeness;        // complete, partial, or unavailable
$quote->cost?->amount;       // Brick\Math\BigDecimal
$quote->source;              // configured, provider_native, portkey, ...
$quote->snapshot?->fingerprint;
```

Missing pricing returns an unavailable quote. A partially priced workload reports the calculated amount and the missing usage units.

## Why this package?

| Problem | Laravel AI Pricing |
| --- | --- |
| Binary floating-point drift | Uses `brick/math` decimals throughout |
| Provider/model routing | Preserves requested and effective identities |
| Changing catalog prices | Captures an immutable pricing snapshot and retrieval time |
| Missing rates | Reports `partial` or `unavailable`, never a misleading zero |
| Multiple possible sources | Applies a documented resolution order |
| CI and offline evaluation | Uses cached catalogs and last-known-good fallback |
| Sensitive workload data | Sends only provider/model catalog requests, never prompts or outputs |

## Resolution order

The first compatible source wins:

1. Provider-reported cost supplied with the observation.
2. Application-configured pricing.
3. Provider-native pricing supplied with the observation.
4. OpenRouter's public model catalog for OpenRouter identities.
5. Portkey's public provider catalogs as a fallback.
6. An explicit unavailable quote.

Catalog pricing in another currency is not silently converted or attributed.

## Configure prices

Rates use a normalized `provider:model` key. Amounts and divisors should be decimal strings when precision matters.

```php
// config/ai-pricing.php
'prices' => [
    'openrouter:google/gemini-3-flash' => [
        'input_tokens' => [
            'amount' => '0.50',
            'per' => '1000000',
            'currency' => 'USD',
        ],
        'output_tokens' => [
            'amount' => '3.00',
            'per' => '1000000',
            'currency' => 'USD',
        ],
    ],
],
```

The package understands token units such as `input_tokens`, `output_tokens`, `cached_input_tokens`, `cache_write_input_tokens`, and `reasoning_tokens`. Custom usage units are preserved and can be priced through configuration.

## Catalog sync and offline operation

Prewarm the configured remote catalogs:

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

Offline mode performs no network retrieval. It uses configured prices and previously cached last-known-good catalogs. Remote-derived quotes retain the sanitized source URL, retrieval time, normalized definition, and snapshot fingerprint.

Consumers should persist the quote alongside their own evidence instead of assuming today's catalog describes an earlier workload.

## Observation adapters

`NormalizedObservationAdapter` accepts normalized Codex, Claude, Amp, gateway, and generic observations while preserving known and custom usage units. `LaravelAiObservationAdapter` uses Laravel AI's public response properties through structural adaptation and has no hard runtime dependency on `laravel/ai`. It deliberately does not inspect private SDK state.

For synchronous Laravel AI text responses, the Laravel adapter also reads public raw responses. OpenRouter's authoritative `usage.cost` is summed across every generation step and takes precedence over catalog pricing. Laravel AI v0.10.3 or newer is required for public raw step responses; v0.10.2 and earlier continue to resolve from normalized usage and catalogs. If any step is missing cost, the adapter falls back to usage-based resolution rather than presenting a partial amount as the total.

```php
$observation = (new LaravelAiObservationAdapter)->adapt($response);
$quote = app(CostResolver::class)->resolve($observation);
```

OpenAI, Anthropic, Gemini, Groq, DeepSeek, Mistral, xAI, Cohere, Bedrock, and the other built-in providers currently expose billable usage rather than per-response money. Their observations therefore use the same normalized usage contract and resolve against configured or remote catalog pricing. Bedrock text usage includes input, output, cache-read, and cache-write tokens and can resolve against Portkey's `bedrock` catalog when the model ID matches; inference-profile aliases and region-specific rates can be supplied through configured prices. Laravel AI embedding responses map their exposed token count to input-token usage, while image and transcription responses use their public `Usage` objects.

When a custom driver reports prompt tokens inclusively or exclusively in a way the adapter cannot infer, pass `inputTokenSemantic` (or `input_token_semantic`) as `inclusive` or `exclusive` on the structural observation. Derived aggregate fields such as `totalTokens` are ignored because pricing is calculated from the individual billable units.

Laravel AI audio and reranking responses currently expose provider/model identity but no usage quantity. Consumers can record a workload-specific unit such as `audio_seconds` or `rerank_requests` through `NormalizedObservationAdapter` and price it with an application-configured rate. The package does not infer missing quantities or assign a monetary cost to local Ollama workloads, but Ollama token usage is retained and can be priced explicitly when a consumer has a meaningful internal rate. Custom Laravel AI drivers can inject an explicit response cost path through `LaravelAiProviderCostExtractor`; arbitrary fields are never guessed to be authoritative cost.

Laravel AI does not preserve raw streaming bodies. For OpenRouter streams, capture the `X-Generation-Id` header without reading the stream and retrieve authoritative cost from OpenRouter's generation endpoint after completion. Failed or blocked requests may be billed without exposing usage; consumers should not record them as zero-cost.

## Precision and rounding

Aggregate precise `Money` values before applying an explicit boundary:

```php
use Brick\Math\RoundingMode;
use Jkudish\LaravelAiPricing\Enums\RoundingBoundary;

$display = $quote->cost?->at(
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
