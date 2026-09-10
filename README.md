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

Install the package with Composer:

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
5. The package's reviewed pricing snapshot.
6. Portkey's public provider catalog as a fallback.
7. An unavailable result.

The first compatible price wins. The package does not convert currencies. A USD result only uses USD pricing.

### Built-in API product SKUs

Non-model APIs use a documented pricing SKU in the existing `model` field. The package includes a reviewed, versioned USD snapshot for products that do not publish a suitable machine-readable runtime catalog:

| Provider | SKU | Usage units |
| --- | --- | --- |
| `brave` | `answers` | `queries`, `input_tokens`, `output_tokens` |
| `brave` | `search` | `requests` |
| `dataforseo` | `chatgpt-llm-scraper-standard`, `chatgpt-llm-scraper-live` | `requests` |
| `dataforseo` | `gemini-llm-scraper-standard`, `gemini-llm-scraper-live` | `requests` |
| `dataforseo` | `google-ai-mode-standard`, `google-ai-mode-live` | `requests` |
| `exa` | `search` | `requests`, `additional_results`, `summary_pages` |
| `exa` | `research` | `exa:agent_compute_units`, `searches` |
| `kagi` | `fastgpt` | `uncached_queries` |
| `you` | `answer` | `requests` |
| `you` | `research-lite`, `research-standard`, `research-deep`, `research-exhaustive` | `requests` |
| `perplexity` | `agent-low`, `agent-medium`, `agent-high` | `web_searches`, `fetch_url_requests`, `people_searches`, `finance_searches`, `sandbox_sessions`, `sandbox_searches` |
| `perplexity` | `search` | `requests` |
| `parallel` | `turbo` | `requests`, `additional_results` |
| `parallel` | `research-pro` | `processor_requests` |
| `valyu` | `research-standard` | `research_requests`, `screenshot_urls`, `code_executions`, `additional_deliverables` |
| `xai` | `grok-4.6` | `searches` |

The Exa request rate includes up to ten results; pass only results above ten as `additional_results`. Pass `uncached_queries: 0` for a free cached Kagi response. You.com Frontier Research has negotiated usage and is intentionally unavailable.

The PHP v2 parity profiles use these pricing identities and dispositions:

| Provider/profile | Pricing identity | Disposition |
| --- | --- | --- |
| `brave-search/search` | `brave:search` | Built-in fixed request rate. |
| `tavily/search` | `tavily:search` | Configured-only: USD per credit depends on the account plan; Basic uses one credit and Advanced uses two. |
| `perplexity-search/search` | `perplexity:search` | Built-in fixed rate per successful request. |
| `perplexity-deep-research/research` (medium) | `perplexity:agent-medium` | Built-in stable tool rates only; routed model usage remains partial or unavailable. |
| `jina-search/search` | `jina:search` | Unavailable: Jina publishes token consumption without a stable public USD conversion. |
| `serpapi/search` | `serpapi:search` | Configured-only: rates and speed multipliers depend on the account plan. |
| `exa/research` | `exa:research` | Built-in Auto-effort ACU and search rates; fixed efforts and enrichment require their applicable rates. |
| `gemini-deep/research` | `gemini:deep-research` | Unavailable: model, intermediate token, and tool quantities are provider-controlled. |
| `grok-x-only/x`, `grok-combined/combined` | `xai:grok-4.6` | Built-in current search-tool rate only; context-tiered model tokens remain partial or unavailable. |
| `parallel/search` | `parallel:search` | Configured-only: the selectable mode and additional-result count determine the price. |
| `parallel/turbo` | `parallel:turbo` | Built-in fixed Turbo request and additional-result rates. |
| `parallel/research` (pro) | `parallel:research-pro` | Built-in fixed rate per successful pro processor run; other processors need distinct configured identities. |
| `valyu/search` | `valyu:search` | Unavailable: every result can use a differently priced source class. |
| `valyu/research` (standard) | `valyu:research-standard` | Built-in Standard base and optional-tool rates; provider-reported response cost is preferred. |
| `firecrawl-search/search` | `firecrawl:search` | Configured-only: the API uses fixed credits, but USD per credit depends on the account plan. |

Configured-only and unavailable identities are intentionally absent from the package snapshot, so unresolved usage returns unavailable rather than a fabricated zero. Configure the exact identity and account rate when you can prove its applicability.

The DataForSEO SKU names identify products and modes; they are not assertions about the underlying model. For these SKUs, `requests: 1` means one restricted billable task/result-page submission. A normal-priority Standard submission is billed once and its later retrieval GETs are free, so do not count those GETs as additional requests. The built-in prices cover only the base normal Standard and Live operations reviewed from DataForSEO's official pages on 2026-09-06. They exclude high-priority, bulk, HTML, rectangle, and other surcharged options. LLM Scraper pricing does not apply to LLM Responses.

These prices are estimates, not invoices. Provider-reported actual cost still wins, failures may be charged, and missing usage produces an unavailable result rather than a zero quote. This package supplies pricing identities only; it does not implement DataForSEO task submission, polling, or retrieval.

Perplexity Agent presets route across models and tools, so the preset SKUs contain only stable tool rates. A quote with tool usage and unpriced routed-model units is partial; model-only usage is unavailable. Prefer the completed response's provider-reported `usage.cost.total_cost` whenever present. The package does not treat representative preset runs as fixed prices or maximums.

Parallel Turbo includes ten results; pass only results above ten as `additional_results`. Exa Research's built-in rates apply to Auto effort (`exa:agent_compute_units` plus `searches`). Valyu Standard Research must include optional tool units when used. The xAI entry intentionally omits model-token rates because Grok 4.6 pricing changes above the context threshold; the current X Search billing model is also scheduled to change on September 21, 2026.

SearchAPI charges successful searches at an account-plan-specific rate, so `searchapi:search` is unavailable by default. Configure the `successful_search_request` unit with your account rate before enforcing a budget. For example, if your account charges USD 4 per 1,000 successful requests:

```php
'prices' => [
    'searchapi:search' => [
        'successful_search_request' => [
            'amount' => '4', // Replace with your account plan's rate.
            'per' => '1000',
            'currency' => 'USD',
        ],
    ],
],
```

After configuration, the resolver returns a Complete configured quote. A Google AI Overview workflow that makes two successful SearchAPI requests must pass `successful_search_request: 2`.

Snapshot entries include source and retrieval metadata in `resources/pricing/provider-skus.php`. Future rate changes should follow `.agents/skills/fetching-provider-pricing`; runtime code never scrapes provider pricing pages.

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
