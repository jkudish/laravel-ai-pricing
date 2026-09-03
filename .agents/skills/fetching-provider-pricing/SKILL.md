---
name: fetching-provider-pricing
description: Researches, normalizes, snapshots, and verifies provider pricing safely. Use when adding or refreshing model, API SKU, request, token, or tool rates in this package.
---

# Fetching Provider Pricing

Update pricing without turning uncertain or account-specific terms into false totals.

## Workflow

1. Identify the observed provider and stable model or product SKU. Keep source-specific aliases inside that source; never rewrite the public `ModelIdentity` or configured-price key.
2. Find the provider's primary documentation. Prefer a documented machine-readable catalog suitable for runtime use. Never scrape pricing HTML at runtime.
3. Record each billing component separately using the provider's semantics: tokens, requests, successful requests, searches, results above an included allowance, summaries, or tool invocations.
4. Normalize each component to an existing arbitrary `Usage` key and represent every amount/divisor as a decimal string. Do not infer missing quantities or encode an estimate as a fixed maximum.
5. If no stable machine-readable catalog exists, update `resources/pricing/provider-skus.php`. Increment its version, set the UTC retrieval time, retain `effective_at: null` when the provider publishes no effective date, and add a primary source URL and any material qualification.
6. Preserve precedence: provider-reported actual cost, application configuration, observation-native pricing, official runtime catalogs, package snapshot, Portkey, unavailable.
7. Review drift by comparing every identity, unit, amount, divisor, currency, qualification, and effective date against the primary source. Read [provider sources](reference/provider-sources.md) for discovery starting points and product caveats.
8. Add deterministic offline tests for exact decimals, provenance, completeness, precedence, and unknown units. Mock every network response. Run no paid or generative provider request and expose no credential.

## Safety rules

- A missing required rate or quantity is partial or unavailable, never zero.
- Provider-reported response totals outrank estimates.
- Account-plan rates must be configured for enforcement; label any package retail example informational.
- Cached/free conditional billing needs a billable usage unit, not a universal per-call charge.
- Reject runtime HTML scraping, credentialed fixture refreshes, floats, and copied third-party summaries.
