# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Reviewed provider-parity pricing identities for Brave Search, Perplexity Search and Agent Medium, Exa Research, Parallel Turbo and Research Pro, Valyu Research Standard, and xAI Grok 4.6 search tools.

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

[Unreleased]: https://github.com/jkudish/laravel-ai-pricing/compare/v0.1.1...main
[0.1.1]: https://github.com/jkudish/laravel-ai-pricing/releases/tag/v0.1.1
[0.1.0]: https://github.com/jkudish/laravel-ai-pricing/releases/tag/v0.1.0
