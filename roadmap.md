# Roadmap

Laravel AI Pricing keeps one narrow responsibility: produce trustworthy, provenance-aware cost evidence without owning application persistence.

## Version 0.1 release

- [x] Decimal-safe cost calculation.
- [x] Explicit complete, partial, and unavailable results.
- [x] Provider-reported, configured, OpenRouter, and Portkey resolution.
- [x] Immutable pricing snapshots and provenance.
- [x] Cached, offline, and last-known-good operation.
- [x] Laravel service-provider integration and catalog sync command.
- [x] Publish `v0.1.0` to GitHub and Packagist.
- [x] Verify clean Laravel 12 and 13 consumer installations from the tagged package.

Laravel AI's current public response objects do not expose provider-reported monetary cost. The package already accepts authoritative cost when an observation supplies it; native Laravel AI capture can be completed when the SDK surfaces that value without relying on provider-specific internals.

## Planned

### Custom pricing sources

Allow applications and packages to register additional `PricingCatalog` implementations while preserving deterministic source precedence and provenance.

### Additional provider-native catalogs

Add narrowly scoped catalog integrations where a provider publishes authoritative, machine-readable pricing data and its unit semantics can be normalized without guesswork.

### Freshness and diagnostics

Expose catalog age, source health, last successful retrieval, last-known-good usage, and incomplete pricing diagnostics without requiring consumers to inspect cache payloads.

### Quote inspection command

Add a read-only Artisan command for inspecting the resolved identity, rates, source, freshness, completeness, and sample quote for a provider/model pair.

## Not planned

- Database models or migrations.
- Prompt, output, or account-data collection.
- Automatic currency conversion.
- Treating missing pricing as zero.
