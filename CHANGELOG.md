# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.2.0] - 2026-09-26

This is a breaking release under 0.x semver: reasoning tokens now bill exactly once as a partition of the inclusive output count, a published `reasoning_tokens` rate of `0` means reasoning is free, and raw Bedrock usage is now read as the laravel/ai 1.0 inclusive dialect. Migration notes are inline below.

### Added

- Reviewed `anthropic:claude-sonnet-5-global` token pricing (USD 2/M input, USD 10/M output) for Claude Sonnet 5 on its default global routing, with generic `anthropic:claude-sonnet-5` and explicit `anthropic:claude-sonnet-5-us` fallback-blocked so the global rate never applies to ambiguous or US-routed execution (`inference_geo: us` costs 1.1x). Cache and web-search units stay absent until their exact billed usage and applicable rates exist.
- Reviewed `openrouter:openai/gpt-5.6-terra-272k` worst-case long-context billing basis (pricing snapshot v6): every completion priced at the `>=272,000`-prompt override rates of the default-tier OpenAI endpoints, with the Exa engine fee as a distinct `exa_search_requests` unit. The generic Terra identity stays unpriced and fallback-blocked so only the exact bound identity resolves.
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
- Pricing snapshot v8 re-reviews the `xai:grok-4.6` search-tool rates against <https://docs.x.ai/developers/pricing> (accessed 2026-09-26) after the September 21, 2026 X Search billing change. Web Search still bills USD 5 per 1,000 calls, and `searches` is preserved as its unit — the platform's current Grok usage is web-only — with its meaning narrowed to Web Search calls only. X Search bills per item fetched: `x_search_posts` at USD 5 per 1,000 posts returned by a search or thread fetch (parent and quoted posts included) and `x_search_profiles` at USD 10 per 1,000 profiles returned by a user search; map the reported `usage.server_side_tool_usage_details` fields `web_search_calls`, `x_posts_fetched`, and `x_users_fetched` onto those units and never map `x_search_calls` onto them. Migration: X Search usage passed as `searches` no longer matches xAI's per-item billing; pass post and profile counts instead.

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

[Unreleased]: https://github.com/jkudish/laravel-ai-pricing/compare/v0.2.0...main
[0.2.0]: https://github.com/jkudish/laravel-ai-pricing/compare/v0.1.2...v0.2.0
[0.1.2]: https://github.com/jkudish/laravel-ai-pricing/compare/v0.1.1...v0.1.2
[0.1.1]: https://github.com/jkudish/laravel-ai-pricing/releases/tag/v0.1.1
[0.1.0]: https://github.com/jkudish/laravel-ai-pricing/releases/tag/v0.1.0
