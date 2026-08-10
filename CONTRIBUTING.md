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

## Pull requests

- Keep changes focused and include tests for behavioral changes.
- Preserve decimal arithmetic, provenance, and explicit uncertainty guarantees.
- Do not introduce persistence or application-owned models into the package.
- Update the README or changelog when the public API or supported behavior changes.
- Ensure all automated checks pass before requesting review.

By participating, you agree to follow the [Code of Conduct](CODE_OF_CONDUCT.md).
