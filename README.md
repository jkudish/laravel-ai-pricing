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

### Reasoning tokens

OpenAI, Anthropic, Gemini, and OpenRouter all bill reasoning as part of the output count. The package prices the output family as a single partition, never as additive units, and expects the reported `output_tokens` count to be inclusive of reasoning.

A `reasoning_tokens` rate is the price for reasoning tokens and **replaces** the output rate for them:

- omitting the rate means the output rate applies (the whole inclusive `output_tokens` count bills at the output rate, so an incomplete catalog can never bill reasoning twice);
- `0` means reasoning is free — a deliberate claim, not "no extra charge".

When a rate is published, the family prices as `(output_tokens - reasoning_tokens) x output rate + reasoning_tokens x reasoning rate`. A zero OpenRouter `internal_reasoning` price means the model bills reasoning inside its completion price, so it is not mapped to a rate at all and reasoning settles at the output rate.

Catalogs written under the pre-0.2 additive semantics that used a `0` reasoning rate to mean "no extra charge" must **remove** that rate when upgrading, or their reasoning tokens will bill at $0.

One dialect reports the output count **exclusive** of reasoning: the laravel/ai 0.x Gemini and xAI drivers kept the thought or reasoning count outside the completion count (the 1.0 SDK folds it into the output total itself). The adapter folds those payloads by default. Payloads from any other source whose output count excludes reasoning can declare that with the same payload key the input semantic uses:

```php
$cost = AiPricing::cost([
    ...$response->toArray(),
    'reasoning_token_semantic' => 'exclusive',
]);
```

Omitting the semantic bills the payload as inclusive unless it is a 0.x Gemini or xAI observation. A `Usage` object constructed directly must carry reasoning-inclusive `output_tokens`; adjust the count yourself before constructing it if your source reports reasoning separately.

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
4. OpenRouter's public model catalog for OpenRouter models, unless the package snapshot fallback-blocks the identity.
5. The package's reviewed pricing snapshot.
6. Portkey's public provider catalog as a fallback.
7. An unavailable result.

The first compatible price wins. An identity the package snapshot fallback-blocks skips both remote catalogs, OpenRouter's and Portkey's, so only a provider-reported cost, a configured price, observation-native pricing or the snapshot itself can price it. The package does not convert currencies. A USD result only uses USD pricing.

The OpenRouter catalog only prices what a model lookup can apply. A model whose catalog entry lists pricing `overrides` (prompt-length or time-of-day tiers) is not priced from its base tier, and the Portkey fallback is skipped for it too, so it resolves to unavailable unless you configure a price or the snapshot has a basis for it. A catalog that publishes a 1-hour cache-write rate prices `cache_write_input_tokens_5m` and `cache_write_input_tokens_1h` instead of the aggregate. Google cache writes stay unpriced, and the per-token `image` and `audio` prices are not applied to image or audio-second counts.

### Built-in API product SKUs

Non-model APIs use a documented pricing SKU in the existing `model` field. The package includes a reviewed, versioned USD snapshot for products that do not publish a suitable machine-readable runtime catalog:

The snapshot-level `retrieved_at` records when the package snapshot was assembled. Individual `notes` retain an earlier per-SKU source review date when an unchanged entry was carried forward.

| Provider | SKU | Usage units |
| --- | --- | --- |
| `brave` | `answers` | `queries`, `input_tokens`, `output_tokens` |
| `brave` | `search` | `requests` |
| `dataforseo` | `chatgpt-llm-scraper-standard`, `chatgpt-llm-scraper-live` | `requests` |
| `dataforseo` | `gemini-llm-scraper-standard`, `gemini-llm-scraper-live` | `requests` |
| `dataforseo` | `google-ai-mode-standard`, `google-ai-mode-live` | `requests` |
| `exa` | `search` | `requests`, `additional_results`, `summary_pages` |
| `exa` | `research` | `agent_compute_units`, `searches` |
| `kagi` | `fastgpt` | `uncached_queries` |
| `you` | `answer` | `requests` |
| `you` | `research-lite`, `research-standard`, `research-deep`, `research-exhaustive` | `requests` |
| `perplexity` | `agent-low`, `agent-medium`, `agent-high` | `web_searches`, `fetch_url_requests`, `people_searches`, `finance_searches`, `sandbox_sessions`, `sandbox_searches` |
| `perplexity` | `search` | `requests` |
| `parallel` | `turbo` | `requests`, `additional_results` |
| `parallel` | `research-pro` | `processor_requests` |
| `valyu` | `research-standard` | `research_requests`, `screenshot_urls`, `code_executions`, `additional_deliverables` |
| `typesafe` | `jev-1.13.0` (aliases `jev-latest`, `jev-preview`) | `input_tokens`, `output_tokens` (free) |
| `xai` | `grok-4.6` | `searches`, `x_search_posts`, `x_search_profiles` |

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
| `serpbase/search` | `serpbase:search` | Configured-only: use the account's actual price per credit, not a public headline minimum. |
| `serpbase/news` | `serpbase:news` | Configured-only: use the account's actual price per credit, not a public headline minimum. |
| `exa/research` | `exa:research` | Built-in Auto-effort ACU and search rates; fixed efforts and enrichment require their applicable rates. |
| `gemini-deep/research` | `gemini:deep-research` | Unavailable: model, intermediate token, and tool quantities are provider-controlled. |
| `grok-x-only/x`, `grok-combined/combined` | `xai:grok-4.6` | Built-in current Web Search and per-item X Search rates only; context-tiered model tokens remain partial or unavailable. |
| `parallel/search` | `parallel:search` | Configured-only: the selectable mode and additional-result count determine the price. |
| `parallel/turbo` | `parallel:turbo` | Built-in fixed Turbo request and additional-result rates. |
| `parallel/research` (pro) | `parallel:research-pro` | Built-in fixed rate per successful pro processor run; other processors need distinct configured identities. |
| `valyu/search` | `valyu:search` | Unavailable: every result can use a differently priced source class. |
| `valyu/research` (standard) | `valyu:research-standard` | Built-in Standard base and optional-tool rates; provider-reported response cost is preferred. |
| `firecrawl-search/search` | `firecrawl:search` | Configured-only: the API uses fixed credits, but USD per credit depends on the account plan. |

Configured-only and unavailable identities are recorded as fallback-blocked policy, not zero-value price entries. This prevents a coincidentally named Portkey model from supplying an incompatible price, while still allowing provider-reported actual cost or an explicitly configured application rate. Configure the exact identity and account rate when you can prove its applicability.

The DataForSEO SKU names identify products and modes; they are not assertions about the underlying model. For these SKUs, `requests: 1` means one restricted billable task/result-page submission. A normal-priority Standard submission is billed once and its later retrieval GETs are free, so do not count those GETs as additional requests. The built-in prices cover only the base normal Standard and Live operations reviewed from DataForSEO's official pages on 2026-09-06. They exclude high-priority, bulk, HTML, rectangle, and other surcharged options. LLM Scraper pricing does not apply to LLM Responses.

These prices are estimates, not invoices. Provider-reported actual cost still wins, failures may be charged, and missing usage produces an unavailable result rather than a zero quote. This package supplies pricing identities only; it does not implement DataForSEO task submission, polling, or retrieval.

Perplexity Agent presets route across models and tools, so the preset SKUs contain only stable tool rates. A quote with tool usage and unpriced routed-model units is partial; model-only usage is unavailable. Prefer the completed response's provider-reported `usage.cost.total_cost` whenever present. The package does not treat representative preset runs as fixed prices or maximums.

Parallel Turbo includes ten results; pass only results above ten as `additional_results`. Exa Research's built-in rates apply to Auto effort (`agent_compute_units` plus `searches`). Valyu Standard Research must include optional tool units when used. The xAI entry intentionally omits model-token rates because Grok 4.6 bills a higher tier once a prompt reaches 200,000 tokens (≥ 200k) and US regional requests bill at 1.1x. Its search-tool rates were re-reviewed against [docs.x.ai/developers/pricing](https://docs.x.ai/developers/pricing) on 2026-09-26: Web Search bills USD 5 per 1,000 calls (`searches`), while X Search has billed per item fetched since the 2026-09-21 change — USD 5 per 1,000 posts returned by a search or thread fetch, including parent and quoted posts (`x_search_posts`), and USD 10 per 1,000 profiles returned by a user search (`x_search_profiles`). Map the reported `usage.server_side_tool_usage_details` fields onto these units: `web_search_calls` to `searches`, `x_posts_fetched` to `x_search_posts`, and `x_users_fetched` to `x_search_profiles`. Never map the `x_search_calls` count onto the per-item units: it counts tool calls, not fetched items, and xAI no longer bills X Search per call. `searches` is preserved as the Web Search call unit, and its meaning has narrowed accordingly: callers that previously passed X Search calls as `searches` must migrate to `x_search_posts` and `x_search_profiles`, or their quotes no longer match xAI's per-item billing. Because the reviewed snapshot precedes the Portkey fallback, this partial xAI entry deliberately prevents incompatible flat fallback token rates from being applied to `grok-4.6`; token-only usage is unavailable, and search plus token usage is partial.

TypeSafe Jev prices laravel/ai `ClassificationResponse` objects directly: `AiPricing::cost($response)` reads the `typesafe` provider, the versioned model the API reported (for example `jev-1.13.0`), and the input and output token counts. The reviewed rate is USD 0.042 per million input tokens from [docs.typesafe.ai/models](https://docs.typesafe.ai/models) (checked 2026-09-28). TypeSafe documents output tokens as free, so the entry publishes an explicit zero `output_tokens` rate and the reported output count keeps a quote complete instead of partial. The snapshot's source-local `aliases` map lets `quote('typesafe', 'jev-latest', ...)` use the rate of the version the alias currently points to while keeping `jev-latest` as the quoted identity. Aliases move when TypeSafe ships a new release; any versioned model the snapshot has not reviewed, such as a future `jev-1.14.0`, stays unavailable until its rate is added.

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

SerpBase Search and News are also unavailable by default. Configure each
identity with the account's current `credits` rate before using a hard budget:

```php
'prices' => [
    'serpbase:search' => [
        'credits' => ['amount' => '0.00047', 'currency' => 'USD'],
    ],
    'serpbase:news' => [
        'credits' => ['amount' => '0.00047', 'currency' => 'USD'],
    ],
],
```

The example amount is illustrative only. Do not derive either configured rate
from a public minimum; use the effective price for the consuming account.

### Built-in model billing bases

Some model prices depend on facts the model name does not carry: inference geography, service tier, prompt length, or the API endpoint. The snapshot publishes those prices as qualified billing bases. A billing basis is a pricing identity that names the conditions its rates apply to. It is not a provider model ID or an alias of one.

| Provider | Billing basis | Applies only when | Usage units |
| --- | --- | --- | --- |
| `anthropic` | `claude-opus-5-5-global-standard` | Global geography, standard speed, synchronous Messages API | `input_tokens`, `output_tokens`, `cached_input_tokens`, `cache_write_input_tokens_5m`, `cache_write_input_tokens_1h` |
| `anthropic` | `claude-sonnet-5-5-global-standard` | Global geography, synchronous Messages API | Same as Opus 5.5 |
| `anthropic` | `claude-haiku-5-5-global-short`, `claude-haiku-5-5-global-long` | Global geography, synchronous Messages API; prompts up to 100,000 tokens (`-short`) or above (`-long`), counting cache reads and writes | Same as Opus 5.5 |
| `openai` | `gpt-6.1-sol-standard-short`, `gpt-6.1-sol-standard-long` | Direct API, standard service tier; up to 272,000 input tokens (`-short`) or above (`-long`) | `input_tokens`, `output_tokens`, `cached_input_tokens`, `cache_write_input_tokens` |
| `openai` | `gpt-6-luna-standard-short`, `gpt-6-luna-standard-long` | Direct generation API, standard service tier; same 272,000-token threshold | Same as GPT-6.1 Sol |
| `openai` | `decisions-gpt-6-luna-short`, `decisions-gpt-6-luna-long` | `POST /v1/decisions` (public beta) with GPT-6 Luna; same 272,000-token threshold | `input_tokens`; output and cache units are published as explicit zero rates |
| `zai` | `glm-5.3`, `glm-5.3-flash` | Direct Z.AI pay-as-you-go API, not the Coding Plan or OpenRouter | `input_tokens`, `cached_input_tokens`, `output_tokens` |
| `deepseek` | `deepseek-v4-pro-peak` | Direct API, priced at the peak rate as a conservative reservation; off-peak is half price | `input_tokens`, `cached_input_tokens`, `output_tokens` |
| `cloudflare` | `@cf/cloudflare/clef`, `@cf/cloudflare/clef-flash` | Workers AI paid usage at the retail rate; the shared daily free allowance is not subtracted per request | `input_tokens` |
| `openrouter` | `google/gemini-3.1-flash-lite-standard-max` | OpenRouter default routing at the standard service tier, priced at the most expensive default-routed endpoint (regional Vertex, 1.1x); never with flex or priority (`service_tier`, `:floor`, `:nitro` or tier-suffixed provider slugs) | `input_tokens`, `output_tokens`, `cached_input_tokens`, `web_searches` (native Google Search grounding queries only) |
| `anthropic` | `claude-fable-5-1-global`, `claude-mythos-5-1-global`, `claude-fable-5-global`, `claude-mythos-5-global`, `claude-opus-4-7-global`, `claude-opus-4-6-global`, `claude-sonnet-5-global`, `claude-sonnet-4-6-global` | Global geography, synchronous Messages API | Same as Opus 5.5, plus `web_searches` |
| `anthropic` | `claude-opus-5-global-standard`, `claude-opus-4-8-global-standard` | Global geography, standard speed, synchronous Messages API | Same as Opus 5.5, plus `web_searches` |
| `anthropic` | `claude-opus-4-5-20251101`, `claude-haiku-4-5-20251001` (aliases `claude-opus-4-5`, `claude-haiku-4-5`) | Synchronous Messages API; these models have one Claude API price | Same as Opus 5.5, plus `web_searches` |
| `gemini` | `gemini-3.8-flash-standard-2026`/`-2027`, `gemini-3.6-flash-standard-2026`/`-2027` | Gemini Developer API, paid standard tier; `-2026` for requests before 2027-01-01T00:00Z, `-2027` from then | `input_tokens`, `output_tokens`, `cached_input_tokens`, `web_searches` (Google Search grounding queries) |
| `gemini` | `gemini-3.5-flash-lite-standard`, `gemini-3.1-flash-lite-standard`, `gemini-3-flash-preview-standard` | Paid standard tier; Flash-Lite 3.1 and 3 Flash Preview exclude audio input | Same as Gemini 3.8 Flash |
| `gemini` | `gemini-3.1-pro-preview-standard-short`/`-long`, `gemini-3.1-pro-preview-customtools-standard-short`/`-long` | Paid standard tier; prompts up to 200,000 tokens (`-short`) or above (`-long`), counting cached tokens | Same as Gemini 3.8 Flash |
| `gemini` | `gemini-2.5-pro-standard-short`/`-long`, `gemini-2.5-flash-standard`, `gemini-2.5-flash-lite-standard` | Paid standard tier, no grounding; same 200,000-token threshold for Pro; Flash and Flash-Lite exclude audio input | `input_tokens`, `output_tokens`, `cached_input_tokens` |
| `openai` | `gpt-6-astra-`, `gpt-6-sol-`, `gpt-5.6-sol-`, `gpt-5.6-terra-`, `gpt-5.6-luna-`, `gpt-5.5-` and `gpt-5.4-standard-short`/`-long` | Direct API, standard service tier; up to 272,000 input tokens (`-short`) or above (`-long`) | Same as GPT-6.1 Sol, plus `web_searches` |
| `openai` | `gpt-5.5-pro-`, `gpt-5.4-pro-standard-short`/`-long`, `gpt-5.2-pro-standard` | Direct API, standard service tier | `input_tokens`, `output_tokens`, `web_searches` |
| `openai` | `gpt-5.4-mini-standard`, `gpt-5.2-standard` | Direct API, standard service tier, all prompt lengths | Same as GPT-6.1 Sol, plus `web_searches` |
| `openai` | `gpt-4.1-standard`, `gpt-4.1-mini-standard`, `gpt-4o-standard`, `gpt-4o-mini-standard`, `chat-latest-standard` | Direct API, standard service tier, no web search | Same as GPT-6.1 Sol |
| `deepseek` | `deepseek-flash-peak`, `deepseek-flash-off-peak`, `deepseek-v4-pro-off-peak` | Direct API; peak as a conservative reservation, off-peak only for requests known to run off-peak | `input_tokens`, `cached_input_tokens`, `output_tokens` |
| `zai` | `glm-5.3-flashx`, `glm-5.2`, `glm-5.1`, `glm-5`, `glm-4.7`, `glm-4.6`, `glm-4.5-air` | Direct Z.AI pay-as-you-go API | `input_tokens`, `cached_input_tokens`, `output_tokens` |
| `moonshot` | `kimi-k3`, `kimi-k2.7-code`, `kimi-k2.7-code-highspeed`, `kimi-k2.6` | Kimi international API (`api.moonshot.ai`, USD) | `input_tokens`, `cached_input_tokens`, `output_tokens`; Kimi K3 adds `cache_write_input_tokens_5m` and `_1h` |
| `dashscope` | `qwen3.8-max-intl`, `qwen3.8-flash-intl`, `qwen3.7-max-intl`, and the tiered `qwen3.7-plus-intl-*`, `qwen3.7-flash-intl-*`, `qwen3-max-intl-*`, `qwen-plus-intl-{thinking,nonthinking}-*`, `qwen-flash-intl-*`, `qwen3-coder-plus-intl-*`, `qwen3-coder-flash-intl-*` | Alibaba Model Studio, Singapore region, International deployment scope; the suffix names the request's input-token tier (`-32k`, `-128k`, `-256k`, `-1m`) | `input_tokens`, `cached_input_tokens` (20% implicit-cache rate; unpriced for Qwen 3.8), `cache_write_input_tokens`, `output_tokens` |
| `openrouter` | `anthropic/claude-*-standard-max` (13 bases, including Haiku 5.5 `-standard-short-max`/`-standard-long-max` at 100,000 prompt tokens) | Default routing at the standard tier, priced at the regional 1.1x endpoints; never fast mode | `input_tokens`, `output_tokens`, `cached_input_tokens`, `cache_write_input_tokens_5m`, `cache_write_input_tokens_1h`, `web_searches` |
| `openrouter` | `google/gemini-3.6-flash-standard-max-2026`, `google/gemini-3.5-flash-lite-standard-max`, `google/gemini-3.1-pro-preview[-customtools]-standard-short`/`-long` | Default routing at the standard tier; Pro split at 200,000 prompt tokens | `input_tokens`, `output_tokens`, `cached_input_tokens`, `web_searches` |
| `openrouter` | `openai/*-standard-max-short`/`-long` and `-standard-max` (36 bases, including the `-pro` IDs) | Default routing without flex, fast or ultrafast, priced at the regional 1.1x endpoints; `-short` below 272,000 prompt tokens | `input_tokens`, `output_tokens`, `cached_input_tokens`, GPT-5.6 and later `cache_write_input_tokens`, reasoning models `web_searches` |
| `openrouter` | `deepseek/*`, `qwen/*`, `moonshotai/*` and `z-ai/*-standard-max` (25 bases) | Default routing without opt-in tier endpoints, priced at the worst default-routed endpoint | `input_tokens`, `cached_input_tokens`, `output_tokens`; some Qwen and Kimi bases add `cache_write_input_tokens` |

`openrouter:google/gemini-3.1-flash-lite-standard-max` is a conservative bound basis, like the OpenRouter `-272k` bases. OpenRouter's default routing for Gemini 3.1 Flash Lite load-balances between Google AI Studio and Vertex global (USD 0.25/M input, 1.50/M output) and the regional Vertex `us` and `eu` endpoints (0.275/M and 1.65/M), so the generic model ID has no single rate. The basis bills every request at the regional rates, which can overstate a request served at the global rate by 10%. Cache writes are deliberately unpriced, because OpenRouter's catalog and its prompt-caching guide disagree on the Gemini cache-write price. A `cache_write_input_tokens` count therefore keeps the quote partial; implicit caching writes nothing. Map `usage.server_tool_use.web_search_requests` to `web_searches` only when the native engine ran: an `auto` request with domain filters falls back to Exa, which is a different, unpriced unit. The generic `openrouter:google/gemini-3.1-flash-lite` identity is fallback-blocked: the live OpenRouter catalog would price it at the AI Studio headline rate and a storage-only cache-write rate, so it resolves to unavailable unless you configure a price.

The package cannot see where or how a request ran, so the caller must enforce each basis's conditions and choose the basis that matches the request. Send Claude requests to global inference geography, send OpenAI requests on the standard tier, choose `-short` or `-long` from the request's actual prompt length, and use a Decisions basis only for the Decisions endpoint. A basis applied to a request that breaks its conditions produces a wrong number, not an unavailable one. Each entry's `notes` in `resources/pricing/provider-skus.php` lists what it excludes, such as US geography (1.1x), fast mode, batch, flex, regional processing uplifts and server tools.

The OpenRouter `-standard-max` bases are conservative bound bases too. Each prices every request at the most expensive endpoint OpenRouter's default routing can choose, so a request served by a cheaper endpoint is overstated. The open-lab bases take each rate from the worst default-routed endpoint, so they can exceed any single endpoint. Endpoint sets change often; re-review a basis when its endpoints churn. For a completed OpenRouter request, prefer the provider-reported `usage.cost`.

The generic identities that cannot select a tier are fallback-blocked: `anthropic:claude-opus-5-5`, `claude-sonnet-5-5` and `claude-haiku-5-5`; `openai:gpt-6.1-sol`, `gpt-6-luna` and `decisions`; `deepseek:deepseek-v4`, `deepseek-v4-pro`, `deepseek-v4-flash` and `deepseek-v4-flash-vision-exp`; `openrouter:google/gemini-3.1-flash-lite`; and the generics listed in the behavior change below. They resolve to unavailable, and neither the OpenRouter catalog nor the Portkey fallback is consulted for them, so a completed response that reports the provider's generic model ID is not priced at a cheaper tier by accident. Provider-reported cost and configured prices still apply. To cost such a response, pass the basis you enforced as the model:

```php
// The application sent this request to global inference with at most 100,000 prompt tokens.
$cost = AiPricing::cost([
    'provider' => 'anthropic',
    'model' => 'claude-haiku-5-5-global-short',
    'usage' => $response->usage,
    'raw' => $response->raw,     // Lets the adapter read Anthropic's cache TTL split.
    'steps' => $response->steps,
]);

$quote = AiPricing::quote('openai', 'gpt-6.1-sol-standard-long', new Usage([
    'input_tokens' => 300_000,
    'output_tokens' => 4_000,
]));
```

**Behavior change (pricing snapshot v12):** these generic and dated identities are newly fallback-blocked and no longer take a Portkey or live OpenRouter catalog price. A response that reports one of them, such as `gpt-4o` or `gpt-4.1`, now quotes unavailable. Quote the qualified basis you enforced, or configure a price for the generic identity.

- Native OpenAI generics: `openai:gpt-6-astra`, `gpt-6-sol`, `gpt-5.6-sol`, `gpt-5.6-terra`, `gpt-5.6-luna`, `gpt-5.6-cyber`, `gpt-5.5`, `gpt-5.5-pro`, `gpt-5.4`, `gpt-5.4-pro`, `gpt-5.4-mini`, `gpt-5.2`, `gpt-5.2-pro`, `gpt-4.1`, `gpt-4.1-mini`, `gpt-4o`, `gpt-4o-mini`, `chat-latest`, `gpt-rosalind-research`, `gpt-daybreak-blue-latest`, `gpt-daybreak-red-latest` and `gpt-5-search-api`.
- Native OpenAI dated IDs: `openai:gpt-5.5-2026-04-23`, `gpt-5.5-pro-2026-04-23`, `gpt-5.4-2026-03-05`, `gpt-5.4-pro-2026-03-05`, `gpt-5.4-mini-2026-03-17`, `gpt-5.2-2025-12-11`, `gpt-5.2-pro-2025-12-11`, `gpt-4.1-2025-04-14`, `gpt-4.1-mini-2025-04-14`, `gpt-4o-2024-08-06`, `gpt-4o-2024-11-20` and `gpt-4o-mini-2024-07-18`.
- Native Anthropic generics: `anthropic:claude-fable-5-1`, `claude-mythos-5-1`, `claude-fable-5`, `claude-mythos-5`, `claude-opus-5`, `claude-opus-4-8`, `claude-opus-4-7`, `claude-opus-4-6` and `claude-sonnet-4-6`.
- Native Gemini generics: `gemini:gemini-3.8-flash`, `gemini-3.7-flash`, `gemini-3.6-flash`, `gemini-3.5-flash`, `gemini-3.5-flash-lite`, `gemini-3.1-flash-lite`, `gemini-3.1-pro-preview`, `gemini-3.1-pro-preview-customtools`, `gemini-3-flash-preview`, `gemini-2.5-pro`, `gemini-2.5-flash`, `gemini-2.5-flash-lite`, `gemini-flash-latest`, `gemini-flash-lite-latest` and `gemini-pro-latest`.
- DeepSeek: `deepseek:deepseek-flash`.
- Qwen on DashScope: `dashscope:qwen3.8-max`, `qwen3.8-max-0902`, `qwen3.8-flash`, `qwen3.7-max`, `qwen3.7-max-2026-05-20`, `qwen3.7-max-2026-06-08`, `qwen3.7-plus`, `qwen3.7-plus-2026-05-26`, `qwen3.7-flash`, `qwen3.7-flash-2026-07-15`, `qwen3-max`, `qwen3-max-2026-01-23`, `qwen3-max-2025-09-23`, `qwen-plus`, `qwen-plus-latest`, `qwen-plus-2025-12-01`, `qwen-plus-2025-09-11`, `qwen-plus-2025-07-28`, `qwen-flash`, `qwen-flash-2025-07-28`, `qwen3-coder-plus`, `qwen3-coder-plus-2025-09-23`, `qwen3-coder-plus-2025-07-22`, `qwen3-coder-flash` and `qwen3-coder-flash-2025-07-28`.
- OpenRouter OpenAI: `openrouter:openai/gpt-6.1-sol`, `gpt-6.1-sol-pro`, `gpt-6-astra`, `gpt-6-astra-pro`, `gpt-6-sol`, `gpt-6-sol-pro`, `gpt-6-luna`, `gpt-6-luna-pro`, `gpt-5.6-sol`, `gpt-5.6-sol-pro`, `gpt-5.6-terra`, `gpt-5.6-terra-pro`, `gpt-5.6-luna`, `gpt-5.6-luna-pro`, `gpt-5.5`, `gpt-5.4`, `gpt-5.4-mini`, `gpt-4.1`, `gpt-4.1-mini`, `gpt-4o-mini`, `gpt-oss-120b` and `gpt-oss-20b`.
- OpenRouter Anthropic: `openrouter:anthropic/claude-fable-5`, `claude-opus-5.5`, `claude-opus-5`, `claude-opus-4.8`, `claude-opus-4.7`, `claude-opus-4.6`, `claude-opus-4.5`, `claude-sonnet-5.5`, `claude-sonnet-5`, `claude-sonnet-4.6`, `claude-haiku-4.5` and `claude-haiku-5.5`.
- OpenRouter Gemini: `openrouter:google/gemini-3.6-flash`, `gemini-3.5-flash-lite`, `gemini-3.5-flash`, `gemini-3.1-pro-preview`, `gemini-3.1-pro-preview-customtools`, `gemini-2.5-pro` and `gemini-2.5-pro-preview`.
- OpenRouter DeepSeek, Qwen, Kimi and GLM: `openrouter:deepseek/deepseek-v4.1-flash`, `deepseek-v4-pro-0813`, `deepseek-v4-pro`, `deepseek-v4-flash`, `deepseek-v4-flash-0731`, `deepseek-v3.2`; `qwen/qwen3.7-plus`, `qwen3.7-max`, `qwen3.7-flash`, `qwen3.8-2.4t-a95b`, `qwen3.8-27b`, `qwen3-coder-flash`, `qwen3-coder`, `qwen-plus`; `moonshotai/kimi-k3`, `kimi-k2.7-code`, `kimi-k2.6`; and `z-ai/glm-5.3`, `glm-5.3-flash`, `glm-5.2`, `glm-5.1`, `glm-5`, `glm-4.7`, `glm-4.6`, `glm-4.5-air`.

Anthropic bills cache writes by TTL: a 5-minute write and a 1-hour write have different rates. The Anthropic bases therefore price `cache_write_input_tokens_5m` and `cache_write_input_tokens_1h` and publish no generic `cache_write_input_tokens` rate. The adapters map Anthropic's `usage.cache_creation.ephemeral_5m_input_tokens` and `ephemeral_1h_input_tokens` to those units and keep the aggregate `cache_creation_input_tokens` as `cache_write_input_tokens`. The calculator treats the three units as one family and never bills the split on top of the aggregate:

- When every reported TTL count has a TTL rate, the split bills at those rates.
- When the price only has a generic `cache_write_input_tokens` rate, as remote catalogs do, the reported aggregate bills at that rate, as it did before the split was mapped.
- An aggregate with no split cannot be priced on an Anthropic basis. It stays a missing unit and the quote is partial, because the package does not guess which TTL was written.

laravel/ai normalizes Anthropic usage to a single `cacheWriteInputTokens` count. For a non-streamed response, the adapter recovers the split from the response's `raw` HTTP response, summed across `steps` for tool loops, and uses it only when it adds up to the reported aggregate. Streamed and serialized responses carry no raw response, so their cache writes keep a partial quote on an Anthropic basis. Callers that record the split themselves can pass `cache_write_input_tokens_5m` and `cache_write_input_tokens_1h` directly.

Web searches bill per search on the bases that publish a `web_searches` rate, which is the provider's native search rate. Anthropic and OpenRouter report the count as `usage.server_tool_use.web_search_requests`; the normalized, Claude and gateway adapters read it from the usage, and the Laravel AI adapter reads it from the raw response of each step. An Anthropic count maps to `web_searches`. OpenRouter's count covers every engine it ran (native, Exa, Firecrawl, Parallel or Perplexity, whose fees depend on the engine, mode and result count) without saying which, so it maps to the distinct `openrouter_web_searches` unit. No snapshot basis prices that unit, so the quote is partial rather than complete at the wrong rate. When your OpenRouter request pinned the native engine on a model that supports native search, declare it on the observation and the count prices as `web_searches`:

```php
$cost = AiPricing::cost([
    'provider' => 'openrouter',
    'model' => 'anthropic/claude-sonnet-5.5-standard-max',
    'usage' => $response->usage,
    'raw' => $response->raw,
    'steps' => $response->steps,
    'web_search_engine' => 'native', // The request sent engine "native".
]);
```

A provider-reported `usage.cost` still wins, and a `web_searches` or `openrouter_web_searches` count you pass yourself is used as given.

Known limitations:

- OpenAI usage reports no search count, so the adapters do not count OpenAI web searches yet. On an OpenAI basis with a `web_searches` rate, pass the number of `web_search_call` items whose action is `search`, or those searches go unbilled.
- Streamed and serialized laravel/ai responses carry no raw response, so their Anthropic cache-write TTL split and web search counts are not recovered.
- laravel/ai's `openai-compatible` driver drops cache-write counts (Kimi `cache_write_tokens`, Qwen `cache_creation_input_tokens`). Through that driver, cache writes on the `moonshot:kimi-k3` and `dashscope:*` bases bill as uncached input: exact for Kimi 5-minute writes, but short for Kimi 1-hour writes (3.00/M) and Qwen explicit cache creation (25%).
- OpenRouter web search counts do not say which engine ran, so they stay unpriced (`openrouter_web_searches`) and keep the quote partial unless the observation declares `web_search_engine: 'native'`.
- Gemini cache writes on OpenRouter stay unpriced, both in the live catalog and on the snapshot bases, so a Gemini cache-write count keeps the quote partial.

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

Configured prices are used before remote catalogs. A configured identity replaces the snapshot definition; its units are not merged with built-in units. For example, an `xai:grok-4.6` override that still prices search tools must include every search unit it needs to bill — `searches`, `x_search_posts`, and `x_search_profiles` — because configuring only some of them leaves the rest unpriced. Provider-reported cost still takes precedence because it describes the completed request.

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
