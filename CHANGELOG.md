# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Reviewed `openrouter:openai/gpt-6-luna-272k` conservative pricing basis for default-tier OpenAI token/cache usage and Exa auto search. Existing Terra rates are unchanged.
- Optional `reasoning_token_semantic` (`inclusive`, the default, or `exclusive`) on laravel/ai observations, which folds a reasoning count that sits outside the reported output count into it before pricing.

### Changed

- Reasoning tokens now bill exactly once, as a partition of the output count that every provider reports inclusive of its reasoning subset: `(output − reasoning) × output rate + reasoning × reasoning rate` when a `reasoning_tokens` rate is published, and the whole inclusive output count at the output rate when none is. Previously every unit with a rate billed additively, so any catalog with a reasoning rate (including OpenRouter's `internal_reasoning` mapping) double-billed reasoning. Migration: a published `reasoning_tokens` rate of `0` now means reasoning is free; under the old additive semantics `0` was a common way to spell "no extra charge", and such catalogs must remove the rate or their reasoning bills at $0. A zero OpenRouter `internal_reasoning` price is no longer mapped to a rate at all. Raw `reasoning_tokens` usage with no published rate no longer marks a quote partial.
- laravel/ai 0.x Gemini and xAI observations report the output count exclusive of reasoning (the 1.0 SDK folds reasoning into the output total itself). The adapter now folds reasoning into the output count for those drivers by default so their reasoning bills at the output rate instead of silently under-billing. The fold never applies to the 1.0 dialect. Pass `reasoning_token_semantic: 'inclusive'` to keep such a payload unfolded, and note that a `Usage` object constructed directly must carry reasoning-inclusive `output_tokens`.
- Raw Bedrock Converse usage (camelCase `inputTokens`, which excludes cached and cache-written tokens) is now read as the laravel/ai 1.0 inclusive dialect and bills `input − cached − cache_write` as uncached input. Pass `input_token_semantic: 'exclusive'` for raw payloads whose input count is already cache-exclusive.
- Raw Anthropic `cache_creation_input_tokens` (and its camelCase spelling) now bills as `cache_write_input_tokens`; previously the field was dropped for raw payloads and cache-written input was never billed.

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

[Unreleased]: https://github.com/jkudish/laravel-ai-pricing/compare/v0.1.2...main
[0.1.2]: https://github.com/jkudish/laravel-ai-pricing/compare/v0.1.1...v0.1.2
[0.1.1]: https://github.com/jkudish/laravel-ai-pricing/releases/tag/v0.1.1
[0.1.0]: https://github.com/jkudish/laravel-ai-pricing/releases/tag/v0.1.0
