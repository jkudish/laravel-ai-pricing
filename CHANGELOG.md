# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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

[Unreleased]: https://github.com/jkudish/laravel-ai-pricing/compare/v0.1.0...main
[0.1.0]: https://github.com/jkudish/laravel-ai-pricing/releases/tag/v0.1.0
