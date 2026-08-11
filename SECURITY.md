# Security Policy

## Supported versions

Security fixes are provided for the latest tagged release. Before `1.0.0`, fixes may require upgrading to the newest minor release because the public API is still stabilizing.

## Reporting a vulnerability

Please do not open a public issue for a suspected vulnerability.

Report vulnerabilities through [GitHub private vulnerability reporting](https://github.com/jkudish/laravel-ai-pricing/security/advisories/new) or email [joey@jkudish.com](mailto:joey@jkudish.com). Include the affected version, impact, reproduction steps, and any suggested mitigation.

You can expect an acknowledgement within five business days. Confirmed issues will be coordinated privately until a fix and disclosure plan are ready.

## Scope

This package retrieves public pricing catalogs and stores them through Laravel Cache. Reports involving endpoint credential leakage, unsafe cache isolation, malformed remote catalog handling, or incorrect attribution of provider-reported cost are especially welcome.
