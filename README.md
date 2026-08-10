# Laravel AI Pricing

Experimental, provenance-aware cost attribution for Laravel AI workloads. The plain-PHP core uses `brick/math` decimals; Laravel supplies configuration, cache, HTTP, and the `ai:pricing:sync` command. The package has no database and performs no currency conversion. The public API may change before the first stable release.

## Development installation

Use a local Composer path repository while developing both packages:

```json
{
    "repositories": [
        {"type": "path", "url": "../laravel-ai-pricing", "options": {"symlink": true}}
    ]
}
```

Alternatively configure this GitHub repository as a Composer VCS repository.

```bash
composer require jkudish/laravel-ai-pricing:@dev
```

The service provider is discovered automatically. Publishing configuration is optional:

```bash
php artisan vendor:publish --tag=laravel-ai-pricing-config
```

## Resolve cost

```php
use Jkudish\LaravelAiPricing\Contracts\CostResolver;
use Jkudish\LaravelAiPricing\ValueObjects\ModelIdentity;
use Jkudish\LaravelAiPricing\ValueObjects\PricingObservation;
use Jkudish\LaravelAiPricing\ValueObjects\Usage;

$quote = app(CostResolver::class)->resolve(new PricingObservation(
    identity: new ModelIdentity('openrouter', 'google/gemini-3-flash'),
    usage: Usage::tokens(input: 1_000, output: 250),
));
```

Quotes explicitly report `complete`, `partial`, or `unavailable`, with their source and immutable pricing snapshot. Missing pricing never blocks the caller.

Resolution order is provider-reported cost, application-configured pricing, provider-native/OpenRouter metadata, Portkey metadata, then unavailable. Rates use a normalized `provider:model` key:

```php
'prices' => [
    'openrouter:google/gemini-3-flash' => [
        'input_tokens' => ['amount' => '0.50', 'per' => '1000000', 'currency' => 'USD'],
        'output_tokens' => ['amount' => '3.00', 'per' => '1000000', 'currency' => 'USD'],
    ],
],
```

Aggregate precise `Money` values before applying a named `RoundingBoundary` with `Money::at()`. Mixed currencies throw, USD is the default, and v0.1 has no FX.

## Catalog sync and offline operation

```bash
php artisan ai:pricing:sync
```

The command caches the OpenRouter official Models API and Portkey catalog through Laravel Cache. Set `ai-pricing.offline` to `true` to prevent network retrieval and rely on configured prices and existing cached catalogs. Catalog requests contain no prompts, outputs, fixtures, account data, or credentials. Provider/model identifiers are the only workload identifiers the pricing layer may transmit.

Set `ai-pricing.portkey.providers` to the providers that `ai:pricing:sync` should prewarm. On-demand fallback lookup fetches only the requested provider's public Portkey catalog. Portkey publishes prices as cents per unit; the adapter converts them to USD-denominated decimal rates before any calculation.

Remote-derived quotes record the source URL, retrieval time, normalized definition, and SHA-256 snapshot fingerprint. Consumers should persist the quote with their evidence instead of assuming today's catalog describes an earlier run.

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

## Compatibility

The runtime package supports PHP 8.4+, Laravel 12 and 13, and has no dependency on Pest. Its development suite uses Pest 5/PHPUnit 13 with Laravel 13/Testbench 11. Pest 5 currently requires Symfony Process 8.1 while Laravel 12/Testbench 10 requires Symfony Process 7.2, so CI verifies Laravel 12 through a clean consumer installation without this repository's development dependencies.
