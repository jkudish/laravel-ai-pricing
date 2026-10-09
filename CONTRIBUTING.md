# Contributing

Thanks for considering a contribution to Laravel AI Pricing.

## Before opening an issue

- Search existing issues and discussions.
- Use Discussions for questions and early feature ideas.
- Use the bug template for reproducible defects.
- Report security issues privately according to [SECURITY.md](SECURITY.md).

## Development setup

```bash
git clone https://github.com/jkudish/laravel-ai-pricing.git
cd laravel-ai-pricing
composer install
```

Run the complete local verification suite:

```bash
composer test
composer analyse
composer lint:check
composer validate --strict
composer audit
```

## Updating the pricing snapshot

The reviewed snapshot lives in `resources/pricing/provider-skus.php`. Follow `.agents/skills/fetching-provider-pricing` for research and sourcing, then check each item before opening a pull request:

- [ ] Increment `version` by one.
- [ ] Set `retrieved_at` to the UTC time the snapshot was assembled, as an ISO 8601 string with a `+00:00` offset.
- [ ] Start every changed entry's `notes` with `Checked YYYY-MM-DD.`, the date its rates were verified against the primary source. Leave the date of unchanged entries alone.
- [ ] Add any new review date to the regex in the `retains a per-SKU source review date` test in `tests/Unit/PackagePricingSourceTest.php`. Update the tests that pin `version` and `retrieved_at` in the same file.
- [ ] Publish a price that depends on geography, service tier, context length or endpoint as a qualified billing basis, such as `openai:gpt-6.1-sol-standard-short`, never as an alias of the provider's model ID. Add the generic identity to `fallback_blocked` so it stays unavailable instead of falling back to a cheaper remote rate.
- [ ] Use `aliases` only for provider-documented moving aliases that resolve to one versioned model at one price, such as `typesafe:jev-latest`. An alias must never target a fallback-blocked identity.
- [ ] Keep every identity in `fallback_blocked` out of `prices`. The only exceptions are the bounded OpenRouter `-272k` bases, which are pinned to the snapshot on purpose; the invariant test lists them.
- [ ] Use a TTL-specific unit, such as `cache_write_input_tokens_5m` or `cache_write_input_tokens_1h`, when the provider bills cache writes by TTL.
- [ ] Record amounts and divisors as decimal strings, add exact-decimal tests for each new entry, and update the README tables and the changelog.

## Pull requests

- Keep changes focused and include tests for behavioral changes.
- Preserve decimal arithmetic, provenance, and explicit uncertainty guarantees.
- Do not introduce persistence or application-owned models into the package.
- Update the README or changelog when the public API or supported behavior changes.
- Ensure all automated checks pass before requesting review.

By participating, you agree to follow the [Code of Conduct](CODE_OF_CONDUCT.md).
