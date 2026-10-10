# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.2.3] - 2026-10-10

### Added

- Reviewed popular-model pricing (pricing snapshot v12, retrieved 2026-10-10): 171 billing bases checked against each provider's primary source, plus the `anthropic:claude-opus-4-5` and `claude-haiku-4-5` aliases. Each basis names the conditions its rates apply to, and callers must enforce them:
  - Anthropic, from <https://platform.claude.com/docs/en/about-claude/pricing>: global-geography bases for Fable and Mythos 5.1 and 5 (USD 10/M input, 50/M output), Opus 5 and 4.8 (`-global-standard`, 5/25), Opus 4.7 and 4.6 (5/25), Sonnet 4.6 (3/15), and the pinned `claude-opus-4-5-20251101` (5/25) and `claude-haiku-4-5-20251001` (1/5), each with cache reads, TTL-specific cache writes and web search at USD 10 per 1,000.
  - Gemini Developer API, standard tier, from <https://ai.google.dev/gemini-api/docs/pricing>: 3.8 and 3.6 Flash as dated `-standard-2026` (0.75/3.75) and `-standard-2027` (1.50/7.50) bases for Google's price doubling, which the package treats as 2027-01-01T00:00Z because Google states no timezone; 3.5 and 3.1 Flash-Lite; 3 Flash Preview; 3.1 Pro Preview and its customtools endpoint split at 200,000 prompt tokens; and 2.5 Pro, Flash and Flash-Lite. Gemini 3 bases price Google Search grounding queries (`web_searches`, USD 14 per 1,000). Cache storage, Maps grounding and Gemini 2.5 grounding are excluded.
  - OpenAI direct, standard tier, from <https://developers.openai.com/api/docs/pricing>: `-standard-short`/`-long` bases split at 272,000 input tokens for GPT-6 Astra, GPT-6 Sol, GPT-5.6 Sol (promotional pricing guaranteed only through 2026-11-21), Terra and Luna, GPT-5.5, GPT-5.5 Pro, GPT-5.4 and GPT-5.4 Pro, and single `-standard` bases for GPT-5.4 mini, GPT-5.2, GPT-5.2 Pro, GPT-4.1, GPT-4.1 mini, GPT-4o, GPT-4o mini and `chat-latest`. Before GPT-5.6, OpenAI charges no cache-write premium, so those bases price cache writes at the uncached input rate.
  - DeepSeek direct: `deepseek-flash-peak` (V4.1-Flash, 0.30/1.20) and the optional `deepseek-flash-off-peak` and `deepseek-v4-pro-off-peak` bases at half the peak rate.
  - Z.AI direct: GLM-5.3 FlashX, 5.2, 5.1, 5, 4.7, 4.6 and 4.5-Air.
  - Kimi international API under the new `moonshot` provider: Kimi K3 (with TTL cache writes), K2.7 Code, K2.7 Code HighSpeed and K2.6.
  - Qwen on Alibaba Model Studio, Singapore region, International deployment scope, under the new `dashscope` provider: 25 `-intl` bases for Qwen 3.8 Max and Flash, 3.7 Max, Plus and Flash, Qwen3 Max, Qwen Plus (thinking and non-thinking), Qwen Flash and the Qwen3 Coder models, split by input-token tier. Cache hits use the 20% implicit-cache rate, a deliberate overstatement for explicit hits; Qwen 3.8 cache hits stay unpriced.
  - OpenRouter conservative bound bases, each priced at the most expensive endpoint default routing can choose and fallback-blocked like the existing `-272k` bases: 13 Anthropic and 36 OpenAI `-standard-max` bases at the regional 1.1x rate, 6 Gemini bases, and 25 DeepSeek, Qwen, Kimi and GLM bases. The open-lab bases take each rate from the worst default-routed endpoint, so re-review them when their endpoints churn.
- The normalized, Claude, gateway and Laravel AI adapters map Anthropic's and OpenRouter's `usage.server_tool_use.web_search_requests` to `web_searches`, so searches on a basis with a search rate are billed instead of silently omitted. The Laravel AI adapter reads the count from each step's raw response, as it does for the Anthropic cache-write TTL split.
- Reviewed `openrouter:google/gemini-3.1-flash-lite-standard-max` pricing (pricing snapshot v11, retrieved 2026-10-09). It is a fallback-blocked bound basis for Gemini 3.1 Flash Lite under OpenRouter default routing at the standard tier, priced at the regional Vertex rates every default-routed request can hit: USD 0.275/M input, 1.65/M output, 0.0275/M cached input, and USD 0.014 per native Google Search grounding query (`web_searches`). The rates were checked against the dated OpenRouter endpoints catalog, OpenRouter's routing, caching and web-search guides, and <https://ai.google.dev/gemini-api/docs/pricing>. Cache writes stay unpriced because OpenRouter's catalog and guide disagree on them. Flex, priority, audio input and non-native search engines are excluded. The generic `openrouter:google/gemini-3.1-flash-lite` identity is fallback-blocked (see Changed).
- README section on the built-in model billing bases: the qualified identities, the caller's duty to enforce geography, service tier, context length and endpoint, the fallback-blocked generic identities, and the TTL cache-write units.
- CONTRIBUTING checklist for updating the pricing snapshot.
- Snapshot invariant test: a `fallback_blocked` identity is either unpriced or a deliberately bound basis on a named allowlist (the OpenRouter `-272k`, `-standard-max` and Gemini 3.1 Pro bound bases), so any new overlap fails, and no alias targets a fallback-blocked identity.

### Changed

- **Behavior change:** pricing snapshot v12 fallback-blocks the generic and dated identities below, so they no longer take a Portkey or live OpenRouter catalog price. A response that reports one of them, such as `gpt-4o` or `gpt-4.1`, now quotes unavailable. Quote the qualified basis you enforced, or configure a price for the generic identity:
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
- **Behavior change:** the live OpenRouter catalog no longer prices a model whose entry lists pricing `overrides` (prompt-length or time-of-day tiers) from its base tier, and the Portkey fallback is skipped for it, so it quotes unavailable unless a configured price or snapshot basis covers it. A catalog that publishes a 1-hour cache-write rate now prices `cache_write_input_tokens_5m` and `_1h`, so an OpenRouter Claude response that reports only the aggregate cache-write count quotes partial instead of billing every write at the 5-minute rate.
- `anthropic:claude-sonnet-5-global` now prices cache reads (USD 0.20/M), TTL cache writes (2.50/M and 4/M) and web search. Its 2/10 launch price is now Anthropic's standard price, so cached Sonnet 5 requests quote complete instead of partial.
- **Behavior change:** an identity in the package snapshot's `fallback_blocked` list now also skips the live OpenRouter catalog, not just the Portkey fallback. Provider-reported cost, configured prices, observation-native pricing and the snapshot still apply. `openrouter:google/gemini-3.1-flash-lite` is newly fallback-blocked. It previously took the live catalog's AI Studio headline rate (USD 0.25/M input, 1.50/M output, storage-only 0.0833/M cache write) even when default routing could bill the regional 1.1x rate, so `AiPricing::cost()` and `quote()` for it now return unavailable. Quote `openrouter:google/gemini-3.1-flash-lite-standard-max` or configure the generic identity instead. No other blocked identity was priced by the live catalog, so nothing else changes.
- The cost calculator settles `cache_write_input_tokens`, `cache_write_input_tokens_5m` and `cache_write_input_tokens_1h` as one family and never bills the TTL split on top of the aggregate. TTL rates price the split. A price with only a generic cache-write rate keeps billing the reported aggregate at that rate, as before. A generic count with no split stays a missing unit on a TTL-only basis. A reported aggregate, including zero, that is smaller than its split is contradictory: the whole family is reported missing and nothing in it is billed.

### Fixed

- The live OpenRouter catalog priced Google cache writes from a storage-only rate, and priced image and audio counts at the per-token `image` and `audio` rates. Google cache writes now stay missing, and those fields no longer map to a unit.
- Anthropic responses with cache writes now price completely on the Anthropic 5.5 billing bases. Previously the adapters folded every cache write into the generic `cache_write_input_tokens` count, which those bases do not price, so any such response quoted partial. The normalized, Claude and Laravel AI adapters now map Anthropic's `usage.cache_creation.ephemeral_5m_input_tokens` and `ephemeral_1h_input_tokens` to `cache_write_input_tokens_5m` and `cache_write_input_tokens_1h`, next to the unchanged aggregate. laravel/ai drops this split from its normalized usage, so for non-streamed responses the Laravel AI adapter reads it from the raw HTTP response, summed across steps. It uses the split only when it adds up to the reported aggregate. Streamed responses still quote partial on these bases.

### Known limitations

- OpenAI usage reports no web search count, so the adapters do not count OpenAI web searches. On an OpenAI basis with a `web_searches` rate, pass the count yourself or those searches go unbilled.
- laravel/ai's `openai-compatible` driver drops cache-write counts (Kimi `cache_write_tokens`, Qwen `cache_creation_input_tokens`), so through that driver cache writes on the `moonshot:kimi-k3` and `dashscope:*` bases bill as uncached input: short by 3.00/M for Kimi 1-hour writes and by 25% for Qwen explicit cache creation.
- Gemini cache writes on OpenRouter stay unpriced, so a Gemini cache-write count keeps an OpenRouter quote partial.
- Streamed and serialized laravel/ai responses carry no raw response, so their web search counts and Anthropic cache-write TTL split are not recovered.

## [0.2.2] - 2026-10-09

### Added

- Reviewed evaluation-model and decision-API pricing (pricing snapshot v10, retrieved 2026-10-09). Tier-dependent prices are published as qualified billing bases, not provider aliases; callers must enforce the geography, service tier, context length and endpoint each one names:
  - Anthropic, global geography, synchronous Messages API: `claude-opus-5-5-global-standard` (USD 4/M input, 20/M output), `claude-sonnet-5-5-global-standard` (2/10), and `claude-haiku-5-5-global-short` (0.10/0.50, prompts up to 100,000 tokens) and `-global-long` (0.50/2.50, above that; the prompt length counts cache reads and writes). Cache reads plus TTL-specific `cache_write_input_tokens_5m` and `cache_write_input_tokens_1h` units, from <https://platform.claude.com/docs/en/about-claude/pricing>.
  - OpenAI direct, standard tier: `gpt-6.1-sol-standard-short`/`-long` (2/10 up to 272,000 input tokens; 4/15 above) and `gpt-6-luna-standard-short`/`-long` (0.10/0.50; 0.20/0.75), from <https://developers.openai.com/api/docs/pricing>.
  - OpenAI Decisions (public beta) with GPT-6 Luna: `decisions-gpt-6-luna-short`/`-long`, input only at 0.10/M and 0.20/M, with explicit zero output and cache rates, from <https://developers.openai.com/api/docs/guides/decisions>.
  - Z.AI direct: `zai:glm-5.3` (1.4/4.4) and `zai:glm-5.3-flash` (0.15/0.50), with cached input; cache storage is deliberately unpriced while Z.AI lists it as limited-time free.
  - DeepSeek direct: `deepseek-v4-pro-peak` (1.32/M cache-miss input, 3.96/M output) as a conservative peak-rate reservation basis; off-peak is half price.
  - Cloudflare Workers AI: `@cf/cloudflare/clef` (0.24/M input) and `clef-flash` (0.09/M input), input only, with no invented output rate.
- Generic identities that cannot select a billing tier are fallback-blocked and stay unavailable: `anthropic:claude-opus-5-5`, `claude-sonnet-5-5`, `claude-haiku-5-5`; `openai:gpt-6.1-sol`, `gpt-6-luna`, `decisions`; and `deepseek:deepseek-v4`, `deepseek-v4-pro`, `deepseek-v4-flash`, `deepseek-v4-flash-vision-exp`. The legacy DeepSeek Flash names now run V4.1-Flash and must not be priced as the retired V4-Flash model.

## [0.2.1] - 2026-09-28

### Added

- Reviewed `typesafe:jev-1.13.0` pricing (pricing snapshot v9): USD 0.042 per million input tokens from <https://docs.typesafe.ai/models> (checked 2026-09-28), with output tokens published as an explicit zero rate because TypeSafe documents them as free. `AiPricing::cost()` prices a laravel/ai 1.0 `ClassificationResponse` from its reported versioned model, and `AiPricing::quote()` prices `typesafe` usage before dispatch. Unreviewed Jev versions stay unavailable.
- Source-local snapshot `aliases`: a documented moving alias borrows the rates and provenance of the versioned identity it points to while keeping its own requested identity. `typesafe:jev-latest` and `typesafe:jev-preview` map to `typesafe:jev-1.13.0`. An alias whose target is not priced resolves to unavailable.

## [0.2.0] - 2026-09-26

This is a breaking release under 0.x semver: reasoning tokens now bill exactly once as a partition of the inclusive output count, a published `reasoning_tokens` rate of `0` means reasoning is free, and raw Bedrock usage is now read as the laravel/ai 1.0 inclusive dialect. Migration notes are inline below.

### Added

- Reviewed `anthropic:claude-sonnet-5-global` token pricing (USD 2/M input, USD 10/M output) for Claude Sonnet 5 on its default global routing, with generic `anthropic:claude-sonnet-5` and explicit `anthropic:claude-sonnet-5-us` fallback-blocked so the global rate never applies to ambiguous or US-routed execution (`inference_geo: us` costs 1.1x). Cache and web-search units stay absent until their exact billed usage and applicable rates exist.
- Reviewed `openrouter:openai/gpt-5.6-terra-272k` worst-case long-context billing basis (pricing snapshot v6): every completion priced at the `>=272,000`-prompt override rates of the default-tier OpenAI endpoints, with the Exa engine fee as a distinct `exa_search_requests` unit. The generic `openrouter:openai/gpt-5.6-terra` identity is not priced by this snapshot and may resolve through a remote fallback catalog; the bound `-272k` identity is fallback-blocked so only the reviewed snapshot (or explicit configured override) prices it.
- `PricingSnapshot` now records the fingerprint algorithm that produced it — a public `algorithm` property, `sha256:rates-jsonb-order@1` for newly constructed snapshots — and gains `PricingSnapshot::fromPersisted()`, which rehydrates a stored snapshot by verifying its fingerprint under the algorithm the persisted form declares, or under every historical algorithm (`sha256:rates-jsonb-order@1`, `sha256:recursive-ksort@0`, `sha256:plain@0`) when it declares none. The rehydrated snapshot keeps the stored fingerprint and the algorithm that verified it; unknown or non-verifying fingerprints fail closed. Migration: verify stored snapshots through `fromPersisted()` rather than by comparing a freshly constructed snapshot's fingerprint, because the constructor fingerprints under the current algorithm and a stored snapshot may have been persisted under an earlier one.
- Reviewed `openrouter:openai/gpt-6-luna-272k` conservative pricing basis for default-tier OpenAI token/cache usage and Exa auto search (pricing snapshot v7). It leaves the Terra long-context basis added in this release unchanged.
- Optional `reasoning_token_semantic` (`inclusive`, the default, or `exclusive`) on laravel/ai observations, which folds a reasoning count that sits outside the reported output count into it before pricing.

### Changed

- laravel/ai 1.0 usage dialect is now supported: the renamed `inputTokens`/`outputTokens` keys report the input count as an inclusive total, so such payloads bill `input − cached − cache_write` as uncached input instead of being treated as cache-exclusive 0.x counts, which double-billed cached and cache-written tokens. The dialect is detected by the renamed input keys appearing without legacy prompt keys or provider-native cache-creation keys; an explicit `input_token_semantic` overrides detection, and payloads carrying both key shapes or provider-native naming keep their historical interpretation.
- `brick/math` compatibility widened to `^0.20 || ^1.0` alongside the existing `^0.14.2`–`^0.19` range. brick/math 1.0.0 is byte-identical to 0.20.0, and the package only feeds validated decimal, integer, or BigDecimal values into it, so both ends of the widened range behave identically.
- Reasoning tokens now bill exactly once, as a partition of the output count that every provider reports inclusive of its reasoning subset: `(output − reasoning) × output rate + reasoning × reasoning rate` when a `reasoning_tokens` rate is published, and the whole inclusive output count at the output rate when none is. Previously every unit with a rate billed additively, so any catalog with a reasoning rate (including OpenRouter's `internal_reasoning` mapping) double-billed reasoning. Migration: a published `reasoning_tokens` rate of `0` now means reasoning is free; under the old additive semantics `0` was a common way to spell "no extra charge", and such catalogs must remove the rate or their reasoning bills at $0. A zero OpenRouter `internal_reasoning` price is no longer mapped to a rate at all. Raw `reasoning_tokens` usage with no published rate no longer marks a quote partial.
- laravel/ai 0.x Gemini and xAI observations report the output count exclusive of reasoning (the 1.0 SDK folds reasoning into the output total itself). The adapter now folds reasoning into the output count for those drivers by default so their reasoning bills at the output rate instead of silently under-billing. The fold never applies to the 1.0 dialect. Pass `reasoning_token_semantic: 'inclusive'` to keep such a payload unfolded, and note that a `Usage` object constructed directly must carry reasoning-inclusive `output_tokens`.
- Raw Bedrock Converse usage (camelCase `inputTokens`, which excludes cached and cache-written tokens) is now read as the laravel/ai 1.0 inclusive dialect and bills `input − cached − cache_write` as uncached input. Pass `input_token_semantic: 'exclusive'` for raw payloads whose input count is already cache-exclusive.
- Raw Anthropic `cache_creation_input_tokens` (and its camelCase spelling) now bills as `cache_write_input_tokens`; previously the field was dropped for raw payloads and cache-written input was never billed.
- Driver validation now also applies to laravel/ai 1.0-dialect payloads: an empty or non-string `driver` value throws an `InvalidArgumentException` instead of being silently ignored when no driver heuristic was needed.
- Pricing snapshot v8 re-reviews the `xai:grok-4.6` search-tool rates against <https://docs.x.ai/developers/pricing> (accessed 2026-09-26) after the September 21, 2026 X Search billing change. Web Search still bills USD 5 per 1,000 calls, and `searches` is preserved as its unit, with its meaning narrowed to Web Search calls only. X Search bills per item fetched: `x_search_posts` at USD 5 per 1,000 posts returned by a search or thread fetch (parent and quoted posts included) and `x_search_profiles` at USD 10 per 1,000 profiles returned by a user search; map the reported `usage.server_side_tool_usage_details` fields `web_search_calls`, `x_posts_fetched`, and `x_users_fetched` onto those units and never map `x_search_calls` onto them. Migration: X Search usage passed as `searches` no longer matches xAI's per-item billing; pass post and profile counts instead.

## [0.1.2] - 2026-09-15

### Added

- Reviewed provider-parity pricing identities for Brave Search, Perplexity Search and Agent Medium, Exa Research, Parallel Turbo and Research Pro, Valyu Research Standard, and xAI Grok 4.6 search tools.
- Configured-only SerpBase Search and News pricing identities, with account-specific credit rates required before hard-budget use.

### Changed

- Bound `brick/math` compatibility to the versions supported by Laravel 13.
- Grok 4.6 token-only quotes now remain unavailable instead of using incompatible flat token pricing from the Portkey fallback.

## [0.1.1] - 2026-09-09

### Added

- Reviewed base pricing for six DataForSEO LLM Scraper and Google AI Mode Standard/Live product SKUs.
- Reviewed package pricing for Brave Answers, Exa Search, Kagi FastGPT, You.com Answer and Research tiers, and Perplexity Agent tool usage, plus a configured-only SearchAPI pricing identity.
- Source-local Portkey provider aliases for Laravel Gemini, xAI, and exact-match Perplexity model identities.
- Project guidance for safely researching, snapshotting, and verifying provider pricing.

### Changed

- Accept `brick/math` 0.14 and higher.

## [0.1.0] - 2026-08-11

### Added

- Decimal-safe AI cost attribution using `brick/math`.
- Explicit complete, partial, and unavailable cost states.
- Provider-reported, configured, OpenRouter, and Portkey pricing resolution.
- Immutable pricing snapshots with provenance and SHA-256 fingerprints.
- Requested and effective model identity tracking.
- Laravel AI, Codex, Claude, Amp, gateway, and normalized observation adapters.
- Cached catalogs, last-known-good fallback, and offline operation.
- `ai:pricing:sync` for prewarming remote pricing catalogs.
- `AiPricing::cost()` for completed Laravel AI responses and `AiPricing::quote()` for pre-request estimates.
- Laravel 13 test-suite support and Laravel 12 clean-consumer installation support on PHP 8.4 and newer.

[Unreleased]: https://github.com/jkudish/laravel-ai-pricing/compare/v0.2.3...main
[0.2.3]: https://github.com/jkudish/laravel-ai-pricing/compare/v0.2.2...v0.2.3
[0.2.2]: https://github.com/jkudish/laravel-ai-pricing/compare/v0.2.1...v0.2.2
[0.2.1]: https://github.com/jkudish/laravel-ai-pricing/compare/v0.2.0...v0.2.1
[0.2.0]: https://github.com/jkudish/laravel-ai-pricing/compare/v0.1.2...v0.2.0
[0.1.2]: https://github.com/jkudish/laravel-ai-pricing/compare/v0.1.1...v0.1.2
[0.1.1]: https://github.com/jkudish/laravel-ai-pricing/releases/tag/v0.1.1
[0.1.0]: https://github.com/jkudish/laravel-ai-pricing/releases/tag/v0.1.0
