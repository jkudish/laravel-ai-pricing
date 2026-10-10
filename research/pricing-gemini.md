# Gemini pricing research (PlanMode #4791)

Research for the snapshot writer. Nothing here edits `resources/pricing/provider-skus.php`.

- Native provider prefix: `gemini`. laravel/ai names its Gemini driver `gemini`, the package already uses `gemini:deep-research`, and `PortkeyPricingSource` maps `gemini` to Portkey's `google` file.
- Native source: <https://ai.google.dev/gemini-api/docs/pricing>, page footer "Last updated 2026-10-09 UTC", fetched 2026-10-10 about 01:16 UTC. Supporting pages, same footer date: [models](https://ai.google.dev/gemini-api/docs/models), [deprecations](https://ai.google.dev/gemini-api/docs/deprecations), [explicit caching](https://ai.google.dev/gemini-api/docs/generate-content/caching), [Google Search grounding](https://ai.google.dev/gemini-api/docs/google-search), [Maps grounding](https://ai.google.dev/gemini-api/docs/maps-grounding), [flex](https://ai.google.dev/gemini-api/docs/flex-inference) and [priority](https://ai.google.dev/gemini-api/docs/priority-inference).
- OpenRouter source: `https://openrouter.ai/api/v1/models` and `/models/google/{id}/endpoints`, queried 2026-10-10T01:19Z (canonical-slug URLs re-checked 01:23Z), plus the [prompt-caching](https://openrouter.ai/docs/guides/best-practices/prompt-caching), [web-search](https://openrouter.ai/docs/guides/features/server-tools/web-search) and [models API](https://openrouter.ai/docs/guides/overview/models) guides.
- Every proposed entry note starts `Checked 2026-10-10.`
- Only free public pages and unauthenticated catalog reads were used. I made no API-key, paid or generative calls.

## 1. Summary

Rates are USD per 1M tokens unless noted. "Storage" is explicit-cache storage per 1M token-hours. Grounding for Gemini 3 and newer costs USD 14 per 1,000 queries, priced at 0.014 per unit. Gemini 2.5 grounding bills per grounded prompt: USD 35 per 1,000 for Search and USD 25 per 1,000 for Maps.

### Native Gemini Developer API (`gemini:`), standard tier

| Proposed identity | Applies when | Input | Output | Cache read | Storage | Grounding units |
| --- | --- | --- | --- | --- | --- | --- |
| `gemini-3.8-flash-standard-2026` | requests through 2026-12-31 | 0.75 | 3.75 | 0.075 | 0.50 | `web_searches`, `maps_queries` 0.014 |
| `gemini-3.8-flash-standard-2027` | requests from 2027-01-01 | 1.50 | 7.50 | 0.15 | 1.00 | same |
| `gemini-3.6-flash-standard-2026` | requests through 2026-12-31 | 0.75 | 3.75 | 0.075 | 0.50 | same |
| `gemini-3.6-flash-standard-2027` | requests from 2027-01-01 | 1.50 | 7.50 | 0.15 | 1.00 | same |
| `gemini-3.5-flash-lite-standard` | all modalities | 0.30 | 2.50 | 0.03 | 1.00 | same |
| `gemini-3.1-flash-lite-standard` | no audio input (audio 0.50) | 0.25 | 1.50 | 0.025 | 1.00 | same |
| `gemini-3.1-pro-preview-standard-short` | prompt ≤ 200k | 2 | 12 | 0.20 | 4.50 | same |
| `gemini-3.1-pro-preview-standard-long` | prompt > 200k | 4 | 18 | 0.40 | 4.50 | same |
| `gemini-3.1-pro-preview-customtools-standard-short` | prompt ≤ 200k | 2 | 12 | 0.20 | 4.50 | same |
| `gemini-3.1-pro-preview-customtools-standard-long` | prompt > 200k | 4 | 18 | 0.40 | 4.50 | same |
| `gemini-3-flash-preview-standard` | no audio input (audio 1.00) | 0.50 | 3 | 0.05 | 1.00 | same |
| `gemini-2.5-pro-standard-short` | prompt ≤ 200k | 1.25 | 10 | 0.125 | 4.50 | `search_grounded_prompts` 0.035, `maps_grounded_prompts` 0.025 |
| `gemini-2.5-pro-standard-long` | prompt > 200k | 2.50 | 15 | 0.25 | 4.50 | same |
| `gemini-2.5-flash-standard` | no audio input (audio 1.00) | 0.30 | 2.50 | 0.03 | 1.00 | same |
| `gemini-2.5-flash-lite-standard` | no audio input (audio 0.30) | 0.10 | 0.40 | 0.01 | 1.00 | same |
| `gemini-robotics-er-2-preview-standard-2026` | requests through 2026-12-31 | 1.00 | 5.00 | 0.10 | 0.50 | `web_searches` 0.014 (no Maps) |
| `gemini-robotics-er-2-preview-standard-2027` | requests from 2027-01-01 | 2.00 | 10.00 | 0.20 | 1.00 | `web_searches` 0.014 |

All native entries are standard tier only. They exclude batch, flex, priority and Vertex AI, and leave cache writes unpriced. Output includes thinking tokens.

### OpenRouter (`openrouter:google/gemini-*`)

Default routing means the standard-tier endpoints. `/flex` and `/priority` endpoints are opt-in service tiers.

| OpenRouter ID | Default-tier endpoints and rates | Decision |
| --- | --- | --- |
| `google/gemini-3.8-flash` | ai-studio and vertex/global at 0.75/3.75/0.075, matching Google | Live catalog sufficient. See caveat C1 on cache writes. |
| `google/gemini-3.7-flash` | ai-studio 0.75/3.75 (vertex/global status −2) | Live catalog sufficient. Google routes `gemini-3.7-flash` to 3.8 Flash at the same price. |
| `google/gemini-3.6-flash` | ai-studio and vertex/global at 0.75/3.75; vertex/us at 0.825/4.125 (1.1x) | Block generic. Add `google/gemini-3.6-flash-standard-max-2026` (0.825/4.125/0.0825, search 0.014). |
| `google/gemini-3.5-flash-lite` | ai-studio and vertex/global at 0.30/2.50; vertex/us and vertex/eu at 0.33/2.75 | Block generic. Add `google/gemini-3.5-flash-lite-standard-max` (0.33/2.75/0.033, search 0.014). |
| `google/gemini-3.5-flash` | ai-studio and vertex/global at 1.50/9.00; vertex/us at 1.65/9.90 | Block generic with no basis. The rate conflicts with Google, which now routes `gemini-3.5-flash` to 3.6 Flash at 0.75/3.75. |
| `google/gemini-3.1-flash-lite` | Unchanged since 2026-10-09 | Existing block and `-standard-max` basis remain correct. See §4. |
| `google/gemini-3.1-pro-preview` | ai-studio and vertex/global at 2/12/0.20; override above 200k: 4/18/0.40 | Block generic, because the package ignores `overrides`. Add `-standard-short` and `-standard-long`. |
| `google/gemini-3.1-pro-preview-customtools` | ai-studio only, same rates and override | Block generic. Add `-standard-short` and `-standard-long`. |
| `google/gemini-3-flash-preview` | ai-studio and vertex/global at 0.50/3.00/0.05; audio 1.00 | Live catalog sufficient. See C1 and C3. |
| `google/gemini-2.5-pro` | ai-studio and vertex global/us/eu at 1.25/10; override above 200k | Block generic with no basis. The model expires on OpenRouter on 2026-10-20 and `overrides` are ignored. |
| `google/gemini-2.5-pro-preview` | Same endpoints as 2.5 Pro; Google shut this ID down 2025-12-02 | Block generic with no basis. |
| `google/gemini-2.5-flash`, `google/gemini-2.5-flash-lite` | Uniform rates matching Google; OpenRouter expiration 2026-10-20 | No entry. Search price conflicts (C4); the model leaves the catalog in 10 days. |

Entry count: 23 new `prices` entries, 17 native and 6 OpenRouter.

## 2. Ready-to-paste `prices` entries

These lint with `php -l`. I spliced them into a scratch copy of the v11 snapshot and ran `vendor/bin/pest`: 370 tests passed, and the only failure was the expected `retains a per-SKU source review date` regex, which needs `2026-10-10`. Hand-checked quotes through `AiPricing::quote` produced:

- `gemini:gemini-3.1-pro-preview-standard-long` with 300,000 input tokens, 1,000 output tokens, 10,000 cached tokens and 2 `web_searches`: 1.25, partial only because I also passed a `cache_write_input_tokens` count.
- `openrouter:google/gemini-3.6-flash-standard-max-2026` with 1M input and 1M output tokens: 4.95.
- `gemini:gemini-2.5-flash-lite-standard` with 1M input tokens, 1,000 `search_grounded_prompts` and 1M `cache_storage_token_hours`: 36.1.

```php
        // --- Native Gemini Developer API (provider "gemini"), standard tier ---
        'gemini:gemini-3.8-flash-standard-2026' => [
            'source' => 'https://ai.google.dev/gemini-api/docs/pricing',
            'notes' => 'Checked 2026-10-10. Billing basis for gemini-3.8-flash on the Gemini Developer API (generateContent or Interactions), paid tier, standard service tier (service_tier omitted or standard), for requests made through 2026-12-31; not a provider alias. Google publishes these introductory rates as valid through December 31, 2026 and doubles every rate from January 1, 2027: use gemini-3.8-flash-standard-2027 from then on. One input rate covers text, image, video and audio; documents bill at the image rate. No long-context tier. Output includes thinking tokens; no separate reasoning rate. cached_input_tokens covers implicit and explicit cache hits (input partitions are exclusive). Google publishes no per-token cache-write fee, so cache_write_input_tokens stays unpriced. cache_storage_token_hours prices explicit-cache storage at USD 0.50 per million token-hours; it is caller-supplied, not reported by responses. web_searches and maps_queries count individual Google Search and Google Maps grounding queries the model executes (USD 14 per 1,000); the shared monthly free allowance of 5,000 per tool is not subtracted. Excludes batch, flex, priority (a downgraded priority request bills at standard; see the x-gemini-service-tier response header), Live API and Vertex AI / Gemini Enterprise Agent Platform pricing.',
            'rates' => [
                'input_tokens' => ['amount' => '0.75', 'per' => '1000000'],
                'output_tokens' => ['amount' => '3.75', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.075', 'per' => '1000000'],
                'cache_storage_token_hours' => ['amount' => '0.50', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.014', 'per' => '1'],
                'maps_queries' => ['amount' => '0.014', 'per' => '1'],
            ],
        ],
        'gemini:gemini-3.8-flash-standard-2027' => [
            'source' => 'https://ai.google.dev/gemini-api/docs/pricing',
            'notes' => 'Checked 2026-10-10. Billing basis for gemini-3.8-flash on the Gemini Developer API, paid tier, standard service tier, for requests made on or after 2027-01-01, when Google publishes the post-introductory rates; not a provider alias. Use gemini-3.8-flash-standard-2026 before then. One input rate covers text, image, video and audio. No long-context tier. Output includes thinking tokens. Exclusive input/cache-hit partitions; cache writes unpriced (no published per-token fee); cache_storage_token_hours at USD 1.00 per million token-hours is caller-supplied. web_searches and maps_queries are per grounding query at USD 14 per 1,000, free monthly allowance not subtracted. Excludes batch, flex, priority, Live API and Vertex AI pricing. Re-review before 2027-01-01 in case Google changes the announced rates.',
            'rates' => [
                'input_tokens' => ['amount' => '1.50', 'per' => '1000000'],
                'output_tokens' => ['amount' => '7.50', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.15', 'per' => '1000000'],
                'cache_storage_token_hours' => ['amount' => '1.00', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.014', 'per' => '1'],
                'maps_queries' => ['amount' => '0.014', 'per' => '1'],
            ],
        ],
        'gemini:gemini-3.6-flash-standard-2026' => [
            'source' => 'https://ai.google.dev/gemini-api/docs/pricing',
            'notes' => 'Checked 2026-10-10. Billing basis for gemini-3.6-flash on the Gemini Developer API, paid tier, standard service tier, for requests made through 2026-12-31; not a provider alias. Requests to the retired gemini-3.5-flash ID are routed to gemini-3.6-flash per https://ai.google.dev/gemini-api/docs/deprecations, but that ID is fallback-blocked, not aliased. Introductory rates valid through December 31, 2026; every rate doubles from January 1, 2027 (use gemini-3.6-flash-standard-2027). One input rate covers text, image, video and audio. No long-context tier. Output includes thinking tokens. Exclusive input/cache-hit partitions; cache writes unpriced; cache_storage_token_hours (USD 0.50 per million token-hours) is caller-supplied. web_searches and maps_queries are per grounding query at USD 14 per 1,000, free monthly allowance not subtracted. Excludes batch, flex, priority, Live API and Vertex AI pricing.',
            'rates' => [
                'input_tokens' => ['amount' => '0.75', 'per' => '1000000'],
                'output_tokens' => ['amount' => '3.75', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.075', 'per' => '1000000'],
                'cache_storage_token_hours' => ['amount' => '0.50', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.014', 'per' => '1'],
                'maps_queries' => ['amount' => '0.014', 'per' => '1'],
            ],
        ],
        'gemini:gemini-3.6-flash-standard-2027' => [
            'source' => 'https://ai.google.dev/gemini-api/docs/pricing',
            'notes' => 'Checked 2026-10-10. Billing basis for gemini-3.6-flash on the Gemini Developer API, paid tier, standard service tier, for requests made on or after 2027-01-01; not a provider alias. Use gemini-3.6-flash-standard-2026 before then. One input rate covers text, image, video and audio. No long-context tier. Output includes thinking tokens. Exclusive input/cache-hit partitions; cache writes unpriced; cache_storage_token_hours (USD 1.00 per million token-hours) is caller-supplied. web_searches and maps_queries are per grounding query at USD 14 per 1,000, free monthly allowance not subtracted. Excludes batch, flex, priority, Live API and Vertex AI pricing. Re-review before 2027-01-01.',
            'rates' => [
                'input_tokens' => ['amount' => '1.50', 'per' => '1000000'],
                'output_tokens' => ['amount' => '7.50', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.15', 'per' => '1000000'],
                'cache_storage_token_hours' => ['amount' => '1.00', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.014', 'per' => '1'],
                'maps_queries' => ['amount' => '0.014', 'per' => '1'],
            ],
        ],
        'gemini:gemini-3.5-flash-lite-standard' => [
            'source' => 'https://ai.google.dev/gemini-api/docs/pricing',
            'notes' => 'Checked 2026-10-10. Billing basis for gemini-3.5-flash-lite on the Gemini Developer API, paid tier, standard service tier; not a provider alias. One input rate covers text, image, video and audio. No long-context tier. Output includes thinking tokens. Exclusive input/cache-hit partitions; cache writes unpriced (no published per-token fee); cache_storage_token_hours (USD 1.00 per million token-hours) is caller-supplied. web_searches and maps_queries are per grounding query at USD 14 per 1,000, free monthly allowance not subtracted. Excludes batch, flex, priority, Live API and Vertex AI pricing.',
            'rates' => [
                'input_tokens' => ['amount' => '0.30', 'per' => '1000000'],
                'output_tokens' => ['amount' => '2.50', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.03', 'per' => '1000000'],
                'cache_storage_token_hours' => ['amount' => '1.00', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.014', 'per' => '1'],
                'maps_queries' => ['amount' => '0.014', 'per' => '1'],
            ],
        ],
        'gemini:gemini-3.1-flash-lite-standard' => [
            'source' => 'https://ai.google.dev/gemini-api/docs/pricing',
            'notes' => 'Checked 2026-10-10. Billing basis for gemini-3.1-flash-lite on the Gemini Developer API, paid tier, standard service tier, text, image and video input only; not a provider alias. Audio input bills at USD 0.50/M (cache hits 0.05/M) and is excluded: the package has no audio input-token unit, so do not use this basis for requests with audio. Documents bill at the image rate. No long-context tier. Output includes thinking tokens. Exclusive input/cache-hit partitions; cache writes unpriced; cache_storage_token_hours (USD 1.00 per million token-hours) is caller-supplied. web_searches and maps_queries are per grounding query at USD 14 per 1,000, free monthly allowance not subtracted. Google lists a shutdown date of 2027-05-07 (replacement gemini-3.5-flash-lite). Excludes batch, flex, priority and Vertex AI pricing.',
            'rates' => [
                'input_tokens' => ['amount' => '0.25', 'per' => '1000000'],
                'output_tokens' => ['amount' => '1.50', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.025', 'per' => '1000000'],
                'cache_storage_token_hours' => ['amount' => '1.00', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.014', 'per' => '1'],
                'maps_queries' => ['amount' => '0.014', 'per' => '1'],
            ],
        ],
        'gemini:gemini-3.1-pro-preview-standard-short' => [
            'source' => 'https://ai.google.dev/gemini-api/docs/pricing',
            'notes' => 'Checked 2026-10-10. Billing basis for gemini-3.1-pro-preview on the Gemini Developer API, paid tier, standard service tier, prompts of at most 200,000 tokens (counting cached tokens); not a provider alias. Above 200,000 prompt tokens use gemini-3.1-pro-preview-standard-long. One input rate for all modalities. Output includes thinking tokens. Exclusive input/cache-hit partitions; cache writes unpriced; cache_storage_token_hours (USD 4.50 per million token-hours, not context-tiered) is caller-supplied. web_searches and maps_queries are per grounding query at USD 14 per 1,000, free monthly allowance not subtracted. Preview model with no announced shutdown date. Excludes batch, flex, priority and Vertex AI pricing.',
            'rates' => [
                'input_tokens' => ['amount' => '2', 'per' => '1000000'],
                'output_tokens' => ['amount' => '12', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.20', 'per' => '1000000'],
                'cache_storage_token_hours' => ['amount' => '4.50', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.014', 'per' => '1'],
                'maps_queries' => ['amount' => '0.014', 'per' => '1'],
            ],
        ],
        'gemini:gemini-3.1-pro-preview-standard-long' => [
            'source' => 'https://ai.google.dev/gemini-api/docs/pricing',
            'notes' => 'Checked 2026-10-10. Billing basis for gemini-3.1-pro-preview on the Gemini Developer API, paid tier, standard service tier, prompts of more than 200,000 tokens (counting cached tokens); the higher tier applies to the whole request. Not a provider alias. One input rate for all modalities. Output includes thinking tokens. Exclusive input/cache-hit partitions; cache writes unpriced; cache_storage_token_hours (USD 4.50 per million token-hours) is caller-supplied. web_searches and maps_queries are per grounding query at USD 14 per 1,000, free monthly allowance not subtracted. Excludes batch, flex, priority and Vertex AI pricing.',
            'rates' => [
                'input_tokens' => ['amount' => '4', 'per' => '1000000'],
                'output_tokens' => ['amount' => '18', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.40', 'per' => '1000000'],
                'cache_storage_token_hours' => ['amount' => '4.50', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.014', 'per' => '1'],
                'maps_queries' => ['amount' => '0.014', 'per' => '1'],
            ],
        ],
        'gemini:gemini-3.1-pro-preview-customtools-standard-short' => [
            'source' => 'https://ai.google.dev/gemini-api/docs/pricing',
            'notes' => 'Checked 2026-10-10. Billing basis for the gemini-3.1-pro-preview-customtools endpoint, which Google prices the same as gemini-3.1-pro-preview: paid tier, standard service tier, prompts of at most 200,000 tokens (counting cached tokens). Not a provider alias. One input rate for all modalities. Output includes thinking tokens. Exclusive input/cache-hit partitions; cache writes unpriced; cache_storage_token_hours (USD 4.50 per million token-hours) is caller-supplied. web_searches and maps_queries are per grounding query at USD 14 per 1,000, free monthly allowance not subtracted. Excludes batch, flex, priority and Vertex AI pricing.',
            'rates' => [
                'input_tokens' => ['amount' => '2', 'per' => '1000000'],
                'output_tokens' => ['amount' => '12', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.20', 'per' => '1000000'],
                'cache_storage_token_hours' => ['amount' => '4.50', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.014', 'per' => '1'],
                'maps_queries' => ['amount' => '0.014', 'per' => '1'],
            ],
        ],
        'gemini:gemini-3.1-pro-preview-customtools-standard-long' => [
            'source' => 'https://ai.google.dev/gemini-api/docs/pricing',
            'notes' => 'Checked 2026-10-10. Billing basis for the gemini-3.1-pro-preview-customtools endpoint, priced the same as gemini-3.1-pro-preview: paid tier, standard service tier, prompts of more than 200,000 tokens (counting cached tokens); the higher tier applies to the whole request. Not a provider alias. Output includes thinking tokens. Exclusive input/cache-hit partitions; cache writes unpriced; cache_storage_token_hours (USD 4.50 per million token-hours) is caller-supplied. web_searches and maps_queries are per grounding query at USD 14 per 1,000, free monthly allowance not subtracted. Excludes batch, flex, priority and Vertex AI pricing.',
            'rates' => [
                'input_tokens' => ['amount' => '4', 'per' => '1000000'],
                'output_tokens' => ['amount' => '18', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.40', 'per' => '1000000'],
                'cache_storage_token_hours' => ['amount' => '4.50', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.014', 'per' => '1'],
                'maps_queries' => ['amount' => '0.014', 'per' => '1'],
            ],
        ],
        'gemini:gemini-3-flash-preview-standard' => [
            'source' => 'https://ai.google.dev/gemini-api/docs/pricing',
            'notes' => 'Checked 2026-10-10. Billing basis for gemini-3-flash-preview (legacy Flash preview, no announced shutdown; replacement gemini-3.6-flash) on the Gemini Developer API, paid tier, standard service tier, text, image and video input only; not a provider alias. Audio input bills at USD 1.00/M (cache hits 0.10/M) and is excluded. No long-context tier. Output includes thinking tokens. Exclusive input/cache-hit partitions; cache writes unpriced; cache_storage_token_hours (USD 1.00 per million token-hours) is caller-supplied. web_searches and maps_queries are per grounding query at USD 14 per 1,000, free monthly allowance not subtracted. Excludes batch, flex, priority and Vertex AI pricing.',
            'rates' => [
                'input_tokens' => ['amount' => '0.50', 'per' => '1000000'],
                'output_tokens' => ['amount' => '3', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.05', 'per' => '1000000'],
                'cache_storage_token_hours' => ['amount' => '1.00', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.014', 'per' => '1'],
                'maps_queries' => ['amount' => '0.014', 'per' => '1'],
            ],
        ],
        'gemini:gemini-2.5-pro-standard-short' => [
            'source' => 'https://ai.google.dev/gemini-api/docs/pricing',
            'notes' => 'Checked 2026-10-10. Billing basis for gemini-2.5-pro on the Gemini Developer API, paid tier, standard service tier, prompts of at most 200,000 tokens (counting cached tokens); not a provider alias. Google limits 2.5 access to existing users; no shutdown date announced. One input rate for all modalities. Output includes thinking tokens. Exclusive input/cache-hit partitions; cache writes unpriced; cache_storage_token_hours (USD 4.50 per million token-hours) is caller-supplied. Gemini 2.5 grounding bills per grounded prompt, not per query: search_grounded_prompts at USD 35 per 1,000 and maps_grounded_prompts at USD 25 per 1,000 (a Maps prompt bills only when it returns at least one Maps result); daily free allowances (1,500 Search RPD, 10,000 Maps RPD) are not subtracted. Do not map per-query web_searches onto these units. Excludes batch, flex, priority and Vertex AI pricing.',
            'rates' => [
                'input_tokens' => ['amount' => '1.25', 'per' => '1000000'],
                'output_tokens' => ['amount' => '10', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.125', 'per' => '1000000'],
                'cache_storage_token_hours' => ['amount' => '4.50', 'per' => '1000000'],
                'search_grounded_prompts' => ['amount' => '0.035', 'per' => '1'],
                'maps_grounded_prompts' => ['amount' => '0.025', 'per' => '1'],
            ],
        ],
        'gemini:gemini-2.5-pro-standard-long' => [
            'source' => 'https://ai.google.dev/gemini-api/docs/pricing',
            'notes' => 'Checked 2026-10-10. Billing basis for gemini-2.5-pro on the Gemini Developer API, paid tier, standard service tier, prompts of more than 200,000 tokens (counting cached tokens); the higher tier applies to the whole request. Not a provider alias. Output includes thinking tokens. Exclusive input/cache-hit partitions; cache writes unpriced; cache_storage_token_hours (USD 4.50 per million token-hours) is caller-supplied. search_grounded_prompts (USD 35 per 1,000) and maps_grounded_prompts (USD 25 per 1,000) are per grounded prompt; daily free allowances are not subtracted. Excludes batch, flex, priority and Vertex AI pricing.',
            'rates' => [
                'input_tokens' => ['amount' => '2.50', 'per' => '1000000'],
                'output_tokens' => ['amount' => '15', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.25', 'per' => '1000000'],
                'cache_storage_token_hours' => ['amount' => '4.50', 'per' => '1000000'],
                'search_grounded_prompts' => ['amount' => '0.035', 'per' => '1'],
                'maps_grounded_prompts' => ['amount' => '0.025', 'per' => '1'],
            ],
        ],
        'gemini:gemini-2.5-flash-standard' => [
            'source' => 'https://ai.google.dev/gemini-api/docs/pricing',
            'notes' => 'Checked 2026-10-10. Billing basis for gemini-2.5-flash on the Gemini Developer API, paid tier, standard service tier, text, image and video input only; not a provider alias. Google limits 2.5 access to existing users; no shutdown date announced. Audio input bills at USD 1.00/M (cache hits 0.10/M) and is excluded. No long-context tier. Output includes thinking tokens. Exclusive input/cache-hit partitions; cache writes unpriced; cache_storage_token_hours (USD 1.00 per million token-hours) is caller-supplied. search_grounded_prompts (USD 35 per 1,000) and maps_grounded_prompts (USD 25 per 1,000) are per grounded prompt; the 1,500 RPD free allowance shared with Flash-Lite is not subtracted. Excludes batch, flex, priority and Vertex AI pricing.',
            'rates' => [
                'input_tokens' => ['amount' => '0.30', 'per' => '1000000'],
                'output_tokens' => ['amount' => '2.50', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.03', 'per' => '1000000'],
                'cache_storage_token_hours' => ['amount' => '1.00', 'per' => '1000000'],
                'search_grounded_prompts' => ['amount' => '0.035', 'per' => '1'],
                'maps_grounded_prompts' => ['amount' => '0.025', 'per' => '1'],
            ],
        ],
        'gemini:gemini-2.5-flash-lite-standard' => [
            'source' => 'https://ai.google.dev/gemini-api/docs/pricing',
            'notes' => 'Checked 2026-10-10. Billing basis for gemini-2.5-flash-lite on the Gemini Developer API, paid tier, standard service tier, text, image and video input only; not a provider alias. Google limits 2.5 access to existing users; no shutdown date announced. Audio input bills at USD 0.30/M (cache hits 0.03/M) and is excluded. No long-context tier. Output includes thinking tokens. Exclusive input/cache-hit partitions; cache writes unpriced; cache_storage_token_hours (USD 1.00 per million token-hours) is caller-supplied. search_grounded_prompts (USD 35 per 1,000) and maps_grounded_prompts (USD 25 per 1,000) are per grounded prompt; the 1,500 RPD free allowance shared with Flash is not subtracted. Excludes batch, flex, priority and Vertex AI pricing.',
            'rates' => [
                'input_tokens' => ['amount' => '0.10', 'per' => '1000000'],
                'output_tokens' => ['amount' => '0.40', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.01', 'per' => '1000000'],
                'cache_storage_token_hours' => ['amount' => '1.00', 'per' => '1000000'],
                'search_grounded_prompts' => ['amount' => '0.035', 'per' => '1'],
                'maps_grounded_prompts' => ['amount' => '0.025', 'per' => '1'],
            ],
        ],
        'gemini:gemini-robotics-er-2-preview-standard-2026' => [
            'source' => 'https://ai.google.dev/gemini-api/docs/pricing',
            'notes' => 'Checked 2026-10-10. Billing basis for gemini-robotics-er-2-preview (generateContent, not the Live API streaming variant) on the Gemini Developer API, paid tier, standard tier, for requests made through 2026-12-31; not a provider alias. Every rate doubles from January 1, 2027 (use gemini-robotics-er-2-preview-standard-2027). One input rate covers text, image, video and audio. Output includes thinking tokens. Exclusive input/cache-hit partitions; cache writes unpriced; cache_storage_token_hours (USD 0.50 per million token-hours) is caller-supplied. web_searches is per Google Search grounding query at USD 14 per 1,000, free monthly allowance not subtracted; Maps grounding is not listed for this model. Excludes batch and Vertex AI pricing.',
            'rates' => [
                'input_tokens' => ['amount' => '1.00', 'per' => '1000000'],
                'output_tokens' => ['amount' => '5.00', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.10', 'per' => '1000000'],
                'cache_storage_token_hours' => ['amount' => '0.50', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.014', 'per' => '1'],
            ],
        ],
        'gemini:gemini-robotics-er-2-preview-standard-2027' => [
            'source' => 'https://ai.google.dev/gemini-api/docs/pricing',
            'notes' => 'Checked 2026-10-10. Billing basis for gemini-robotics-er-2-preview (generateContent) on the Gemini Developer API, paid tier, standard tier, for requests made on or after 2027-01-01; not a provider alias. Use gemini-robotics-er-2-preview-standard-2026 before then. One input rate covers text, image, video and audio. Output includes thinking tokens. Exclusive input/cache-hit partitions; cache writes unpriced; cache_storage_token_hours (USD 1.00 per million token-hours) is caller-supplied. web_searches is per Google Search grounding query at USD 14 per 1,000, free monthly allowance not subtracted. Excludes batch and Vertex AI pricing. Re-review before 2027-01-01.',
            'rates' => [
                'input_tokens' => ['amount' => '2.00', 'per' => '1000000'],
                'output_tokens' => ['amount' => '10.00', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.20', 'per' => '1000000'],
                'cache_storage_token_hours' => ['amount' => '1.00', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.014', 'per' => '1'],
            ],
        ],

        // --- OpenRouter google/gemini-* bound bases ---
        'openrouter:google/gemini-3.6-flash-standard-max-2026' => [
            'source' => 'https://openrouter.ai/api/v1/models/google/gemini-3.6-flash-20260721/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for google/gemini-3.6-flash (canonical google/gemini-3.6-flash-20260721) under OpenRouter default routing at the standard service tier, for requests made through 2026-12-31; not the :batch variant. Default routing spans google-ai-studio and google-vertex/global (USD 0.75/M input, 3.75/M output, 0.075/M cache read) and the regional google-vertex/us endpoint (0.825, 4.125 and 0.0825, a 1.1x uplift), so every request is priced at the regional rates; this can overstate a global request by 10%. Flex and priority endpoints are opt-in service tiers and are excluded. Upstream standard rates confirmed at https://ai.google.dev/gemini-api/docs/pricing as introductory through December 31, 2026; Google doubles them from January 1, 2027 and OpenRouter has published no 2027 rate, so this basis is invalid from 2027-01-01 and no 2027 basis is proposed. Reasoning is output usage. Cache writes are deliberately unpriced: the catalog lists input_cache_write at 0.0417/M (five minutes of storage only) while https://openrouter.ai/docs/guides/best-practices/prompt-caching bills a Gemini cache write at the input price plus five minutes of storage. Cache reads use the 0.1x catalog rate, not the guide generic 0.25x constant. web_searches counts native Google Search grounding queries only, passed through at USD 14 per 1,000 per https://openrouter.ai/docs/guides/features/server-tools/web-search; Exa and other engines stay unpriced. The generic openrouter:google/gemini-3.6-flash identity is fallback-blocked. Snapshot invalidation: a change to the canonical revision, default-tier endpoint set, token rates or search pricing requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '0.825', 'per' => '1000000'],
                'output_tokens' => ['amount' => '4.125', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.0825', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.014', 'per' => '1'],
            ],
        ],
        'openrouter:google/gemini-3.5-flash-lite-standard-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/google/gemini-3.5-flash-lite-20260721/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for google/gemini-3.5-flash-lite (canonical google/gemini-3.5-flash-lite-20260721) under OpenRouter default routing at the standard service tier; not the :batch variant. Default routing spans google-ai-studio and google-vertex/global (USD 0.30/M input, 2.50/M output, 0.03/M cache read) and the regional google-vertex/us and google-vertex/eu endpoints (0.33, 2.75 and 0.033, a 1.1x uplift), so every request is priced at the regional rates; this can overstate a global request by 10%. Flex and priority endpoints are opt-in service tiers and are excluded. Upstream standard rates confirmed at https://ai.google.dev/gemini-api/docs/pricing: one input rate for all modalities, no long-context tier. Reasoning is output usage. Cache writes are deliberately unpriced (catalog storage-only 0.0833/M conflicts with the OpenRouter prompt-caching guide). Cache reads use the 0.1x catalog rate. web_searches counts native Google Search grounding queries only at USD 14 per 1,000; the OpenRouter web-search guide does not list 3.5 Flash-Lite among native-search models, so map the count only when the response shows the native engine ran. The generic openrouter:google/gemini-3.5-flash-lite identity is fallback-blocked. Snapshot invalidation: a change to the canonical revision, default-tier endpoint set, token rates or search pricing requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '0.33', 'per' => '1000000'],
                'output_tokens' => ['amount' => '2.75', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.033', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.014', 'per' => '1'],
            ],
        ],
        'openrouter:google/gemini-3.1-pro-preview-standard-short' => [
            'source' => 'https://openrouter.ai/api/v1/models/google/gemini-3.1-pro-preview-20260219/endpoints',
            'notes' => 'Checked 2026-10-10. Billing basis for google/gemini-3.1-pro-preview (canonical google/gemini-3.1-pro-preview-20260219) under OpenRouter default routing at the standard service tier, prompts of at most 200,000 tokens; not the :batch or customtools variants. Both default-tier endpoints (google-ai-studio, google-vertex/global) charge USD 2/M input, 12/M output and 0.20/M cache read, matching https://ai.google.dev/gemini-api/docs/pricing. The catalog applies its override (4/M, 18/M, 0.40/M) when total prompt tokens are strictly greater than 200,000; the package OpenRouter source ignores pricing overrides, so the generic identity is fallback-blocked and callers choose -standard-short or -standard-long from the actual prompt length. Flex and priority endpoints are excluded. Reasoning is output usage. Cache writes are deliberately unpriced (catalog storage-only 0.375/M conflicts with the OpenRouter prompt-caching guide). web_searches counts native Google Search grounding queries only at USD 14 per 1,000. Snapshot invalidation: a change to the canonical revision, endpoint set, override threshold, token rates or search pricing requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '2', 'per' => '1000000'],
                'output_tokens' => ['amount' => '12', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.20', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.014', 'per' => '1'],
            ],
        ],
        'openrouter:google/gemini-3.1-pro-preview-standard-long' => [
            'source' => 'https://openrouter.ai/api/v1/models/google/gemini-3.1-pro-preview-20260219/endpoints',
            'notes' => 'Checked 2026-10-10. Billing basis for google/gemini-3.1-pro-preview (canonical google/gemini-3.1-pro-preview-20260219) under OpenRouter default routing at the standard service tier, prompts of more than 200,000 tokens: the catalog override rates USD 4/M input, 18/M output and 0.40/M cache read on both default-tier endpoints, matching Google long-context pricing. Not the :batch or customtools variants. Flex and priority endpoints are excluded. Reasoning is output usage. Cache writes are deliberately unpriced (catalog storage-only rate conflicts with the OpenRouter prompt-caching guide; the override does not restate it). web_searches counts native Google Search grounding queries only at USD 14 per 1,000. The generic identity is fallback-blocked. Snapshot invalidation: a change to the canonical revision, endpoint set, override threshold, token rates or search pricing requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '4', 'per' => '1000000'],
                'output_tokens' => ['amount' => '18', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.40', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.014', 'per' => '1'],
            ],
        ],
        'openrouter:google/gemini-3.1-pro-preview-customtools-standard-short' => [
            'source' => 'https://openrouter.ai/api/v1/models/google/gemini-3.1-pro-preview-customtools-20260219/endpoints',
            'notes' => 'Checked 2026-10-10. Billing basis for google/gemini-3.1-pro-preview-customtools (canonical google/gemini-3.1-pro-preview-customtools-20260219), whose only endpoint is google-ai-studio at the standard tier, prompts of at most 200,000 tokens: USD 2/M input, 12/M output, 0.20/M cache read, matching https://ai.google.dev/gemini-api/docs/pricing. The catalog override (4/M, 18/M, 0.40/M above 200,000 prompt tokens) is ignored by the package OpenRouter source, so the generic identity is fallback-blocked. Reasoning is output usage. Cache writes are deliberately unpriced. web_searches counts native Google Search grounding queries only at USD 14 per 1,000. Snapshot invalidation: a change to the canonical revision, endpoint set, override threshold, token rates or search pricing requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '2', 'per' => '1000000'],
                'output_tokens' => ['amount' => '12', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.20', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.014', 'per' => '1'],
            ],
        ],
        'openrouter:google/gemini-3.1-pro-preview-customtools-standard-long' => [
            'source' => 'https://openrouter.ai/api/v1/models/google/gemini-3.1-pro-preview-customtools-20260219/endpoints',
            'notes' => 'Checked 2026-10-10. Billing basis for google/gemini-3.1-pro-preview-customtools (canonical google/gemini-3.1-pro-preview-customtools-20260219) on its google-ai-studio standard endpoint, prompts of more than 200,000 tokens: catalog override rates USD 4/M input, 18/M output, 0.40/M cache read. Reasoning is output usage. Cache writes are deliberately unpriced. web_searches counts native Google Search grounding queries only at USD 14 per 1,000. The generic identity is fallback-blocked. Snapshot invalidation: a change to the canonical revision, endpoint set, override threshold, token rates or search pricing requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '4', 'per' => '1000000'],
                'output_tokens' => ['amount' => '18', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.40', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.014', 'per' => '1'],
            ],
        ],
```

## 3. Proposed `fallback_blocked` and alias changes

```php
        // Checked 2026-10-10 against https://ai.google.dev/gemini-api/docs/pricing,
        // https://ai.google.dev/gemini-api/docs/deprecations and the flex/priority guides.
        // service_tier (standard, flex, priority) is a request field on the same model ID,
        // the Pro models are prompt-length tiered, and 3.8/3.6 Flash and Robotics-ER 2 double
        // their rates on 2027-01-01, so generic native IDs cannot select a rate. Use the
        // -standard[-short|-long|-2026|-2027] bases. gemini-3.7-flash and gemini-3.5-flash
        // are retired IDs that Google routes to gemini-3.8-flash and gemini-3.6-flash; the
        // -latest IDs are moving aliases whose current targets Google does not document.
        'gemini:gemini-3.8-flash',
        'gemini:gemini-3.7-flash',
        'gemini:gemini-3.6-flash',
        'gemini:gemini-3.5-flash',
        'gemini:gemini-3.5-flash-lite',
        'gemini:gemini-3.1-flash-lite',
        'gemini:gemini-3.1-pro-preview',
        'gemini:gemini-3.1-pro-preview-customtools',
        'gemini:gemini-3-flash-preview',
        'gemini:gemini-2.5-pro',
        'gemini:gemini-2.5-flash',
        'gemini:gemini-2.5-flash-lite',
        'gemini:gemini-robotics-er-2-preview',
        'gemini:gemini-flash-latest',
        'gemini:gemini-flash-lite-latest',
        'gemini:gemini-pro-latest',
        // Bound OpenRouter bases priced only by the snapshot (add to the invariant-test allowlist).
        'openrouter:google/gemini-3.6-flash-standard-max-2026',
        'openrouter:google/gemini-3.5-flash-lite-standard-max',
        'openrouter:google/gemini-3.1-pro-preview-standard-short',
        'openrouter:google/gemini-3.1-pro-preview-standard-long',
        'openrouter:google/gemini-3.1-pro-preview-customtools-standard-short',
        'openrouter:google/gemini-3.1-pro-preview-customtools-standard-long',
        // Checked 2026-10-10 against https://openrouter.ai/api/v1/models/google/{id}/endpoints.
        // 3.6 Flash and 3.5 Flash-Lite default routing spans global and 1.1x regional Vertex
        // endpoints; 3.5 Flash is catalogued at 1.50/9.00 while Google routes gemini-3.5-flash
        // to 3.6 Flash at 0.75/3.75; the Pro catalogs carry >200k overrides that the package
        // OpenRouter source ignores. 2.5 Pro (and its retired preview ID) also leave
        // OpenRouter on 2026-10-20, so no basis is proposed for them.
        'openrouter:google/gemini-3.6-flash',
        'openrouter:google/gemini-3.5-flash-lite',
        'openrouter:google/gemini-3.5-flash',
        'openrouter:google/gemini-3.1-pro-preview',
        'openrouter:google/gemini-3.1-pro-preview-customtools',
        'openrouter:google/gemini-2.5-pro',
        'openrouter:google/gemini-2.5-pro-preview',
```

Native `-standard*` bases do not need to be in `fallback_blocked`. This matches the Anthropic and OpenAI bases, and no remote catalog uses those keys. The six OpenRouter bases follow the `-272k` and `-standard-max` precedent: they are blocked and priced, so add them to the overlap allowlist in `tests/Unit/PackagePricingSourceTest.php`.

Aliases: none proposed.

- `gemini-3.7-flash` → 3.8 Flash and `gemini-3.5-flash` → 3.6 Flash are documented routing redirects, but their targets are tier-ambiguous generic IDs. The alias rule forbids targeting a fallback-blocked identity, and an alias to a qualified basis would invent a provider alias. Callers should pass the target's basis.
- `gemini-flash-latest`, `gemini-flash-lite-latest` and `gemini-pro-latest` are documented as moving aliases on the [models page](https://ai.google.dev/gemini-api/docs/models), but Google does not publish their current targets. Portkey guesses 3 Flash Preview, 3.1 Flash-Lite and 3.1 Pro rates under `-lte-128k` keys. An unknown target cannot be aliased, so I propose blocking them.

## 4. Drift in existing Gemini entries

| Entry | Finding |
| --- | --- |
| `openrouter:google/gemini-3.1-flash-lite-standard-max` | No rate drift. Canonical slug is still `-20260507`. The default-tier endpoint set (ai-studio, vertex/global, vertex/us, vertex/eu) and every rate match: 0.25/1.50/0.025 and 0.275/1.65/0.0275, cache write 0.0833, `web_search` 0.014. Google rates are unchanged. New fact for the notes: the deprecations page now lists a shutdown date of 2027-05-07 for `gemini-3.1-flash-lite`, with `gemini-3.5-flash-lite` as the replacement. OpenRouter's guide still uses a 0.25x Gemini read constant and "input + 5 min storage" cache writes, so the existing cache reasoning still holds. |
| `openrouter:google/gemini-3.1-flash-lite` (blocked) | Still justified for the same reasons. |
| `openrouter:google/gemini-3.1-flash-lite-preview` (unblocked; tests assert this) | Google shut down this ID on 2026-05-25, but OpenRouter still lists it with ai-studio endpoints at the same 0.25/1.50 rates. Leaving it unblocked is harmless today. Consider blocking it at the next review. |
| `gemini:gemini-3.1-flash-lite` and other native IDs | No `gemini:` prices exist today. Portkey keys native Gemini only as `-lte-128k`/`-gt-128k` plus a few plain legacy IDs. For example, plain `gemini-2.5-flash-lite` is priced at 0.10/0.40 with no tier distinction, so it is the one generic native ID that currently gets a fallback price. Portkey's `gemini-3.5-flash-*` keys are also stale at 1.50/9.00. |
| `gemini:deep-research` (blocked) | Still unpriceable. The pricing page bills agents at underlying model and tool rates. The models page now lists versioned agent IDs `deep-research-preview-04-2026`, `deep-research-max-preview-04-2026` and `antigravity-preview-09-2026`. Optionally block those too, although no catalog prices them today. |
| `src/Sources/OpenRouterPricingSource.php` (package code, not a snapshot entry) | 1. `pricing.overrides` is ignored. The live catalog prices >200k-token prompts for `google/gemini-3.1-pro-preview*`, `google/gemini-2.5-pro*` and any future override at short-context rates. This drives the Pro blocks above. 2. `image` → `images` and `audio` → `audio_seconds` look wrong for Gemini. OpenRouter documents `image` as "cost per image input" and does not document `audio`, but every Gemini value equals the per-token prompt rate or the per-token audio rate, such as 3.1 Flash-Lite `audio` 0.0000005 = 0.50/M. An `images` or `audio_seconds` count would therefore be priced at a per-token amount. I did not fix this; flagging it only. |
| laravel/ai Gemini usage | Verified on `laravel/ai` 1.x `ParsesTextResponses::extractUsage`. Input is `total_input_tokens`, which includes cached tokens, and the package adapter subtracts `cacheReadInputTokens` for the 1.x dialect. Output already folds in thought tokens. The native bases therefore do not double-bill cache hits or reasoning. |

## 5. Excluded models

| Model(s) | Reason |
| --- | --- |
| `gemini-3.8-live`, `gemini-3.8-live-extended-thinking`, `gemini-3.1-flash-live-preview`, `gemini-3.5-live-translate-preview`, `gemini-2.5-flash-native-audio-preview-12-2025`, `gemini-robotics-er-2-streaming-preview` | Live API, real-time audio, or native audio. Out of scope. |
| `gemini-3.5-transcribe`, `gemini-3.5-transcribe-live` | Speech-to-text audio models priced per audio token or minute. Out of scope as audio and Live. |
| `gemini-3.8-flash-tts`, `gemini-3.8-flash-lite-tts`, `gemini-3.1-flash-tts-preview`, `gemini-2.5-flash-preview-tts`, `gemini-2.5-pro-preview-tts` | TTS. Out of scope. |
| `gemini-nano-banana-2.1`, `gemini-3.1-flash-image`, `gemini-3.1-flash-lite-image`, `gemini-3-pro-image`, `gemini-2.5-flash-image` | Image generation, priced on image output tokens. `gemini-2.5-flash-image` is also deprecated. The pricing page says it shuts down on October 2, 2026, while the deprecations table says March 15, 2027. |
| `gemini-omni-1.1-flash`, `gemini-omni-flash-preview`, `veo-3.1-generate-preview`, `veo-3.1-fast-generate-preview`, `veo-3.1-lite-generate-preview` | Video generation. |
| `lyria-3.5`, `lyria-3-clip-preview`, `lyria-3-pro-preview` | Music generation. |
| `gemini-embedding-2`, `gemini-embedding-001` | Embeddings. |
| Gemma 4 | Free tier only, with no paid price. Gemma is out of scope. |
| `gemini-robotics-er-1.6-preview` | Listed on the models page but has no price on the pricing page. |
| `deep-research-preview-04-2026`, `deep-research-max-preview-04-2026`, `antigravity-preview-09-2026` | Agents billed at underlying model and tool rates, so they have no fixed rate. They stay covered by the `gemini:deep-research` precedent. |
| Shut down: `gemini-3-pro-preview` (2026-03-09), `gemini-3.1-flash-lite-preview` (2026-05-25), `gemini-2.0-flash`, `-001`, `gemini-2.0-flash-lite`, `-001` (2026-06-01), `gemini-2.5-computer-use-preview-10-2025` (2026-07-28), all `gemini-2.5-*-preview-*` IDs, `gemini-2.0-flash-live-001` | Deprecated or shut down per the deprecations page. |
| Auto-routed: `gemini-3.7-flash`, `gemini-3.5-flash` | Not on the pricing page, and Google serves them with newer models. Blocked rather than priced; see §3. |
| Batch, Flex, Priority tiers (every model), OpenRouter `:batch` IDs and `/flex` and `/priority` endpoints | Out of scope. The live catalog prices `:batch` IDs at batch rates. |
| Fine-tuning | The Gemini API pricing page lists no Gemini tuning price. Gemma tuning is listed as "Not available". |
| Vertex AI / Gemini Enterprise Agent Platform | The package has no Vertex provider, so I created no identities. See C7. |

## 6. Conflicts and uncertainties

- **C1. Family-wide OpenRouter cache-write conflict. The writer must decide.** Every Gemini catalog lists `input_cache_write` as five minutes of storage only. Examples: 3.8 Flash 0.0417/M is 0.50 × 5/60, and 3.1 Pro 0.375/M is 4.50 × 5/60. OpenRouter's prompt-caching guide still bills a Gemini write at "input price + 5 min storage", and still uses a 0.25x read constant where the catalog and Google use 0.1x. The precedent blocked `google/gemini-3.1-flash-lite` partly for this reason.
  - I did not block uniformly priced models (3.8 Flash, 3.7 Flash, 3 Flash Preview) for this alone. Blocking them would freeze rates that the live catalog will update on 2027-01-01 for 3.8 Flash.
  - For strict precedent parity, add `-standard-max` bases for those three. 3.8 Flash would need `-2026` dating and has no 2027 OpenRouter rate.
  - A cleaner systemic fix is code: drop `input_cache_write` for `google/*` in `OpenRouterPricingSource`, so a Gemini cache-write count stays missing everywhere.
- **C2. Dated price change.** Google publishes 3.8 Flash, 3.6 Flash and Robotics-ER 2 rates "through December 31, 2026" and doubled rates "starting January 1, 2027".
  - The snapshot has only a global `effective_at`, so I encoded the condition in the basis name (`-2026` and `-2027`). The caller chooses by request date.
  - Google does not state a timezone for the boundary.
  - The writer could instead publish only `-2026` and schedule a refresh before 2027-01-01.
- **C3. Audio input.** Several models charge more for audio input: 3.1 Flash-Lite, 3 Flash Preview, 2.5 Flash and 2.5 Flash-Lite. The package has no audio input-token unit; OpenRouter's `audio` maps to `audio_seconds`, see §4. I followed the 3.1 Flash-Lite precedent: these bases cover text, image and video only, and their notes forbid audio requests. 3.8 Flash, 3.6 Flash, 3.5 Flash-Lite, 3.1 Pro, 2.5 Pro and Robotics-ER 2 publish one rate for all modalities.
- **C4. OpenRouter grounding.**
  - The catalog lists `web_search` 0.014 for every Gemini ID, including 2.5. Google bills 2.5 grounding at USD 35 per 1,000 grounded prompts, not per query.
  - OpenRouter's web-search guide lists native Google search only for "Gemini 3 Flash, Gemini 3 Pro, Gemini 3.1 Flash/Lite, Gemini 3.5 Flash". That list omits 3.5 Flash-Lite, 3.6, 3.7 and 3.8 Flash and all of 2.5, so `auto` may fall back to Exa at 0.007 for those models. The list may just be stale.
  - The proposed bases keep `web_searches` for native queries only, following the precedent. The 2.5 OpenRouter search rate is a conflict and stays unpriced, because I proposed no 2.5 bases.
- **C5. Long-context counting.** Google says "prompts <= 200k tokens". I assumed this counts cached tokens, because `promptTokenCount` includes `cachedContentTokenCount`. OpenRouter's override applies when total prompt tokens are strictly greater than 200,000, which matches.
- **C6. New Usage unit names. The writer must decide.** These units are new to the package, and no adapter maps them:
  - `cache_storage_token_hours`: storage rate per 1M token-hours.
  - `maps_queries`: Gemini 3+ Maps grounding queries.
  - `search_grounded_prompts` and `maps_grounded_prompts`: Gemini 2.5 per-prompt grounding.

  Callers must supply them. A missing count does not affect other units. The writer may rename them, or drop storage. Dropping storage leaves it unpriced, as with Z.ai, which is a safe fallback. Free allowances (5,000 per month for Gemini 3+, and 1,500 or 10,000 RPD for 2.5) are never subtracted, as in the precedent.
- **C7. Vertex and regional pricing.** Google states that Gemini Enterprise Agent Platform (Vertex) prices may differ. OpenRouter's endpoints show regional Vertex `us` and `eu` at 1.1x for 3.6 Flash, 3.5 Flash-Lite, 3.5 Flash and 3.1 Flash-Lite, while global Vertex matches AI Studio. 2.5 Pro regional Vertex shows no uplift. The package has no Vertex provider, so none of this creates identities.
- **C8. Native cache writes.** Google's explicit-caching guide lists billing as cached-token reads, storage duration and ordinary tokens. It gives no per-token creation fee, so `cache_write_input_tokens` stays unpriced, and a reported count keeps the quote partial. laravel/ai 1.x does not report Gemini cache writes.
- **C9. Inclusion judgement for Robotics-ER 2.** `gemini-robotics-er-2-preview` is a niche embodied-reasoning VLM, but it is a standard generateContent text and thinking model on the pricing page, so I included it. Drop its two entries and its block if the writer wants mainstream models only.
- **C10. 2.5 availability.** Google restricts 2.5 models to prior users. OpenRouter shows `expiration_date` 2026-10-20 for `google/gemini-2.5-pro`, `-flash` and `-flash-lite`. The native 2.5 bases remain valid because Google announced no shutdown.
- **C11. Priority downgrade.** A priority request that Google downgrades bills at standard, and the response header `x-gemini-service-tier` shows the tier actually used. Callers can use the header to pick the standard basis.

## Writer checklist (from CONTRIBUTING)

- Bump `version` to 12 and set `retrieved_at`.
- Add `2026-10-10` to the review-date regex test.
- Allowlist the six OpenRouter bases in the overlap invariant test.
- Add exact-decimal tests for the new entries.
- Add README "Built-in model billing bases" rows for the `gemini` bases, the new OpenRouter bases, and the new unit names.
- Update the CHANGELOG.
