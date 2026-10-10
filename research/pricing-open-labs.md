# Pricing research: DeepSeek, Qwen, Z.ai (GLM) and Kimi (Moonshot)

PlanMode #4794 (parent #4790). Research for `resources/pricing/provider-skus.php`, based on `main` at `1c8f4f0` (snapshot version 11). This file proposes changes; it does not edit the snapshot. A separate writer thread integrates all four families.

- Checked: 2026-10-10. All sources were read between 2026-10-10T00:50 and 01:30 UTC.
- Method: free public reading only. I made no API calls with keys, no paid or generative requests, and used no credentials. OpenRouter data came from the public `GET /api/v1/models` and `GET /api/v1/models/{id}/endpoints`.
- Rates are USD per 1,000,000 tokens (`'per' => '1000000'`), written as decimal strings. A missing, console-only or conflicting rate stays unpriced and is never set to zero.
- Entry count: **62 proposed entries**, plus 2 optional DeepSeek off-peak entries (64 in total):
  - 37 direct entries: DeepSeek 1, Z.ai 7, Kimi 4, Qwen 25.
  - 25 OpenRouter bound bases.
  - 4 OpenRouter identities are "live catalog sufficient" and need no entry.
- Existing entries `zai:glm-5.3`, `zai:glm-5.3-flash` and `deepseek:deepseek-v4-pro-peak` were rechecked. Their rates have not drifted.

## Provider prefixes

| Lab | Prefix | Status | Why |
| --- | --- | --- | --- |
| DeepSeek | `deepseek` | Existing | It is `Laravel\Ai\Enums\Lab::DeepSeek` (laravel/ai `43ad192`), it is already used in the snapshot, and it is in `LaravelAiObservationAdapter::INCLUSIVE_DRIVERS`. |
| Z.ai | `zai` | Existing (package-owned) | laravel/ai has no Z.ai lab. The snapshot already uses `zai:`. `PortkeyPricingSource` does not map `zai` to Portkey's `z-ai`, so unlisted `zai:` models resolve to unavailable rather than to Portkey. |
| Kimi | `moonshot` (proposed) | New | laravel/ai has no Moonshot lab, so callers use the `openai-compatible` driver. Moonshot is the company and the API host (`api.moonshot.ai`, still the host in every docs example after the site moved to platform.kimi.ai). It is also Portkey's provider key (`moonshot.json`) and LiteLLM's prefix. The prefix means the **international** platform (USD). The mainland platform (`api.moonshot.cn`, CNY) is not covered; callers there should use a different provider name. |
| Qwen | `dashscope` (proposed) | New | DashScope is the Model Studio API product and host (`dashscope-intl.aliyuncs.com`), and Portkey (`dashscope.json`) and LiteLLM use the same name. I chose a platform prefix over the `qwen` model family because the platform also serves Kimi, GLM and DeepSeek at its own prices. `dashscope:kimi-k3` reads correctly; `qwen:kimi-k3` would not. **Consequence:** Portkey has a `dashscope` catalog with flat Singapore short-tier headline prices and no tiers, so every in-scope generic and dated Qwen ID must be fallback-blocked (section 3). The alternative is `alibaba`, which Portkey does not serve, so unlisted Qwen models would resolve to unavailable instead of to Portkey's short-tier price. |
| OpenRouter | `openrouter` | Existing | It is `Lab::OpenRouter`. Its IDs are vendor slugs: `deepseek/`, `qwen/`, `z-ai/`, `moonshotai/`. |

Usage reporting: the laravel/ai `OpenAiCompatible` driver maps `prompt_tokens` (inclusive of cache), `prompt_tokens_details.cached_tokens` and `completion_tokens_details.reasoning_tokens`. **It drops cache-write counts** (Kimi `cache_write_tokens`, Qwen `cache_creation_input_tokens`). See uncertainty U1.

## 1. Summary table

### Direct lab APIs

| Identity | Applies when | Input | Cached input | Cache write | Output | Source |
| --- | --- | --- | --- | --- | --- | --- |
| `deepseek:deepseek-flash-peak` | Direct API, peak price as reservation | 0.3 | 0.006 | — | 1.2 | [DeepSeek](https://api-docs.deepseek.com/quick_start/pricing) |
| `deepseek:deepseek-flash-off-peak` | Direct API, request known to run off-peak | 0.15 | 0.003 | — | 0.6 | [DeepSeek](https://api-docs.deepseek.com/quick_start/pricing) |
| `deepseek:deepseek-v4-pro-off-peak` | Direct API, request known to run off-peak | 0.66 | 0.022 | — | 1.98 | [DeepSeek](https://api-docs.deepseek.com/quick_start/pricing) |
| `zai:glm-5.3-flashx` | Direct Z.AI pay-as-you-go API | 0.37 | 0.075 | — | 1.25 | [Z.ai](https://docs.z.ai/guides/overview/pricing) |
| `zai:glm-5.2` | Direct Z.AI pay-as-you-go API | 1.4 | 0.26 | — | 4.4 | [Z.ai](https://docs.z.ai/guides/overview/pricing) |
| `zai:glm-5.1` | Direct Z.AI pay-as-you-go API | 1.4 | 0.26 | — | 4.4 | [Z.ai](https://docs.z.ai/guides/overview/pricing) |
| `zai:glm-5` | Direct Z.AI pay-as-you-go API | 1 | 0.2 | — | 3.2 | [Z.ai](https://docs.z.ai/guides/overview/pricing) |
| `zai:glm-4.7` | Direct Z.AI pay-as-you-go API | 0.6 | 0.11 | — | 2.2 | [Z.ai](https://docs.z.ai/guides/overview/pricing) |
| `zai:glm-4.6` | Direct Z.AI pay-as-you-go API | 0.6 | 0.11 | — | 2.2 | [Z.ai](https://docs.z.ai/guides/overview/pricing) |
| `zai:glm-4.5-air` | Direct Z.AI pay-as-you-go API | 0.2 | 0.03 | — | 1.1 | [Z.ai](https://docs.z.ai/guides/overview/pricing) |
| `moonshot:kimi-k3` | Kimi international API | 3 | 0.3 | 5m 3 / 1h 6 | 15 | [Kimi](https://platform.kimi.ai/docs/pricing/chat) |
| `moonshot:kimi-k2.7-code` | Kimi international API | 0.95 | 0.19 | — | 4 | [Kimi](https://platform.kimi.ai/docs/pricing/chat) |
| `moonshot:kimi-k2.7-code-highspeed` | Kimi international API | 1.9 | 0.38 | — | 8 | [Kimi](https://platform.kimi.ai/docs/pricing/chat) |
| `moonshot:kimi-k2.6` | Kimi international API | 0.95 | 0.16 | — | 4 | [Kimi](https://platform.kimi.ai/docs/pricing/chat) |
| `dashscope:qwen3.8-max-intl` | Singapore/International, ≤1,000,000 input | 2 | — | 2.5 | 6 | [Model Studio](https://www.alibabacloud.com/help/en/model-studio/model-pricing) |
| `dashscope:qwen3.8-flash-intl` | Singapore/International, ≤1,000,000 input | 0.15 | — | 0.1875 | 0.47 | [Model Studio](https://www.alibabacloud.com/help/en/model-studio/model-pricing) |
| `dashscope:qwen3.7-max-intl` | Singapore/International, ≤1,000,000 input | 2.5 | 0.5 | 3.125 | 7.5 | [Model Studio](https://www.alibabacloud.com/help/en/model-studio/model-pricing) |
| `dashscope:qwen3.7-plus-intl-256k` | Singapore/International, ≤256,000 input | 0.4 | 0.08 | 0.5 | 1.6 | [Model Studio](https://www.alibabacloud.com/help/en/model-studio/model-pricing) |
| `dashscope:qwen3.7-plus-intl-1m` | Singapore/International, >256,000 and ≤1,000,000 input | 1.2 | 0.24 | 1.5 | 4.8 | [Model Studio](https://www.alibabacloud.com/help/en/model-studio/model-pricing) |
| `dashscope:qwen3.7-flash-intl-32k` | Singapore/International, ≤32,000 input | 0.03 | 0.006 | 0.0375 | 0.13 | [Model Studio](https://www.alibabacloud.com/help/en/model-studio/model-pricing) |
| `dashscope:qwen3.7-flash-intl-256k` | Singapore/International, >32,000 and ≤256,000 input | 0.1 | 0.02 | 0.125 | 0.4 | [Model Studio](https://www.alibabacloud.com/help/en/model-studio/model-pricing) |
| `dashscope:qwen3.7-flash-intl-1m` | Singapore/International, >256,000 and ≤1,000,000 input | 0.2 | 0.04 | 0.25 | 0.8 | [Model Studio](https://www.alibabacloud.com/help/en/model-studio/model-pricing) |
| `dashscope:qwen3-max-intl-32k` | Singapore/International, ≤32,000 input | 1.2 | 0.24 | 1.5 | 6 | [Model Studio](https://www.alibabacloud.com/help/en/model-studio/model-pricing) |
| `dashscope:qwen3-max-intl-128k` | Singapore/International, >32,000 and ≤128,000 input | 2.4 | 0.48 | 3 | 12 | [Model Studio](https://www.alibabacloud.com/help/en/model-studio/model-pricing) |
| `dashscope:qwen3-max-intl-256k` | Singapore/International, >128,000 and ≤256,000 input | 3 | 0.6 | 3.75 | 15 | [Model Studio](https://www.alibabacloud.com/help/en/model-studio/model-pricing) |
| `dashscope:qwen-plus-intl-nonthinking-256k` | Singapore/International, ≤256,000 input | 0.4 | 0.08 | 0.5 | 1.2 | [Model Studio](https://www.alibabacloud.com/help/en/model-studio/model-pricing) |
| `dashscope:qwen-plus-intl-thinking-256k` | Singapore/International, ≤256,000 input | 0.4 | 0.08 | 0.5 | 4 | [Model Studio](https://www.alibabacloud.com/help/en/model-studio/model-pricing) |
| `dashscope:qwen-plus-intl-nonthinking-1m` | Singapore/International, >256,000 and ≤1,000,000 input | 1.2 | 0.24 | 1.5 | 3.6 | [Model Studio](https://www.alibabacloud.com/help/en/model-studio/model-pricing) |
| `dashscope:qwen-plus-intl-thinking-1m` | Singapore/International, >256,000 and ≤1,000,000 input | 1.2 | 0.24 | 1.5 | 12 | [Model Studio](https://www.alibabacloud.com/help/en/model-studio/model-pricing) |
| `dashscope:qwen-flash-intl-256k` | Singapore/International, ≤256,000 input | 0.05 | 0.01 | 0.0625 | 0.4 | [Model Studio](https://www.alibabacloud.com/help/en/model-studio/model-pricing) |
| `dashscope:qwen-flash-intl-1m` | Singapore/International, >256,000 and ≤1,000,000 input | 0.25 | 0.05 | 0.3125 | 2 | [Model Studio](https://www.alibabacloud.com/help/en/model-studio/model-pricing) |
| `dashscope:qwen3-coder-plus-intl-32k` | Singapore/International, ≤32,000 input | 1 | 0.2 | 1.25 | 5 | [Model Studio](https://www.alibabacloud.com/help/en/model-studio/model-pricing) |
| `dashscope:qwen3-coder-plus-intl-128k` | Singapore/International, >32,000 and ≤128,000 input | 1.8 | 0.36 | 2.25 | 9 | [Model Studio](https://www.alibabacloud.com/help/en/model-studio/model-pricing) |
| `dashscope:qwen3-coder-plus-intl-256k` | Singapore/International, >128,000 and ≤256,000 input | 3 | 0.6 | 3.75 | 15 | [Model Studio](https://www.alibabacloud.com/help/en/model-studio/model-pricing) |
| `dashscope:qwen3-coder-plus-intl-1m` | Singapore/International, >256,000 and ≤1,000,000 input | 6 | 1.2 | 7.5 | 60 | [Model Studio](https://www.alibabacloud.com/help/en/model-studio/model-pricing) |
| `dashscope:qwen3-coder-flash-intl-32k` | Singapore/International, ≤32,000 input | 0.3 | 0.06 | 0.375 | 1.5 | [Model Studio](https://www.alibabacloud.com/help/en/model-studio/model-pricing) |
| `dashscope:qwen3-coder-flash-intl-128k` | Singapore/International, >32,000 and ≤128,000 input | 0.5 | 0.1 | 0.625 | 2.5 | [Model Studio](https://www.alibabacloud.com/help/en/model-studio/model-pricing) |
| `dashscope:qwen3-coder-flash-intl-256k` | Singapore/International, >128,000 and ≤256,000 input | 0.8 | 0.16 | 1 | 4 | [Model Studio](https://www.alibabacloud.com/help/en/model-studio/model-pricing) |
| `dashscope:qwen3-coder-flash-intl-1m` | Singapore/International, >256,000 and ≤1,000,000 input | 1.6 | 0.32 | 2 | 9.6 | [Model Studio](https://www.alibabacloud.com/help/en/model-studio/model-pricing) |

Kimi K3's cache write uses the TTL units `cache_write_input_tokens_5m` and `cache_write_input_tokens_1h`. Qwen cache write is explicit-cache creation only. For `qwen3.8-*`, cached input is deliberately unpriced (console-only rate). Tier names use the upper bound of the input-token range, where K means 1,000 (Alibaba's definition): `-32k` is at most 32,000 input tokens, and `-1m` is more than the previous bound and at most 1,000,000.

### OpenRouter identities

The package prices `openrouter:` identities from the `/models` headline only (`OpenRouterPricingSource` matches `id` exactly and ignores endpoint `overrides`). The live catalog is therefore safe only where every default-routed endpoint has the same price, quantization and context, with no override. Endpoints with the opt-in service-tier suffixes `/fast`, `/priority`, `/flex` and `/ultrafast` are excluded from "default-routed", following https://openrouter.ai/docs/guides/features/service-tiers: "Requests that don't use any of these are never routed to a non-default service tier". Other suffixes, such as `turbo`, regions and quantizations, count as default-routed.

| OpenRouter ID | Endpoints (default/all) | Price sets | Quantizations | Context | Live headline in/out | Verdict | Worst case in / cached / write / out |
| --- | --- | --- | --- | --- | --- | --- | --- |
| `deepseek/deepseek-v4.1-flash` | 31/31 | 24 | fp4, fp8, unknown | 1,000,000–1,048,576 | 0.3 / 1.2 | block; bound `deepseek/deepseek-v4.1-flash-standard-max` | 0.45 / 0.049 / — / 1.8 |
| `deepseek/deepseek-v4-pro-0813` | 21/21 | 15 | fp4, fp8, unknown | 1,000,000–1,048,576 | 0.66 / 1.98 | block; bound `deepseek/deepseek-v4-pro-0813-standard-max` | 1.65 / 0.219 / — / 5 |
| `deepseek/deepseek-v4-pro` | 16/16 | 16 | fp4, fp8, unknown | 1,000,000–1,048,576 | 0.2088 / 0.4176 | block; bound `deepseek/deepseek-v4-pro-standard-max` | 1.91 / 0.33 / — / 10.5 |
| `deepseek/deepseek-v4-flash` | 16/16 | 15 | fp4, fp8, unknown | 384,000–1,048,576 | 0.0228 / 1.28 | block; bound `deepseek/deepseek-v4-flash-standard-max` | 0.44 / 0.07 / — / 1.536 |
| `deepseek/deepseek-v4-flash-0731` | 26/27 | 22 | fp4, fp8, unknown | 262,144–1,048,576 | 0.018 / 1.28 | block; bound `deepseek/deepseek-v4-flash-0731-standard-max` | 0.44 / 0.07 / — / 1.536 |
| `deepseek/deepseek-v3.2` | 12/12 | 10 | fp4, fp8, unknown | 32,768–163,840 | 0.259 / 0.42 | block; bound `deepseek/deepseek-v3.2-standard-max` | 3 / 0.5 / — / 4.5 |
| `qwen/qwen3.8-max-0902` | 2/2 | 2 | unknown | 1,000,000 | 2 / 6 | live catalog sufficient | 2 / 0.25 / 2.5 / 6 |
| `qwen/qwen3.8-flash` | 2/2 | 2 | unknown | 1,000,000 | 0.15 / 0.47 | live catalog sufficient | 0.15 / 0.016 / 0.2 / 0.47 |
| `qwen/qwen3.7-plus` | 2/2 | 1 | unknown | 1,000,000 | 0.32 / 1.28 | block; bound `qwen/qwen3.7-plus-standard-max` | 0.96 / 0.192 / 1.2 / 3.84 |
| `qwen/qwen3.7-max` | 2/2 | 2 | unknown | 1,000,000 | 1.475 / 4.425 | block; bound `qwen/qwen3.7-max-standard-max` | 2 / 0.4 / 1.84375 / 6 |
| `qwen/qwen3.7-flash` | 2/2 | 2 | unknown | 1,000,000 | 0.03 / 0.13 | block; bound `qwen/qwen3.7-flash-standard-max` | 0.23 / 0.046 / 0.25 / 0.92 |
| `qwen/qwen3.8-2.4t-a95b` | 7/7 | 3 | fp4, fp8, unknown | 262,144–1,048,576 | 2 / 6 | block; bound `qwen/qwen3.8-2.4t-a95b-standard-max` | 2 / 0.25 / 2.5 / 6 |
| `qwen/qwen3.8-27b` | 19/19 | 18 | bf16, fp16, fp4, fp8, unknown | 65,536–1,000,000 | 0.425 / 2.55 | block; bound `qwen/qwen3.8-27b-standard-max` | 0.99 / 0.99 / 0.53125 / 4.7 |
| `qwen/qwen3-coder-flash` | 1/1 | 1 | unknown | 1,000,000 | 0.195 / 0.975 | block; bound `qwen/qwen3-coder-flash-standard-max` | 0.52 / 0.104 / 0.65 / 2.6 |
| `qwen/qwen3-coder-next` | 1/1 | 1 | bf16 | 262,144 | 0.12 / 0.8 | live catalog sufficient | 0.12 / 0.07 / — / 0.8 |
| `qwen/qwen3-coder` | 3/3 | 3 | fp4, fp8, unknown | 256,000–262,144 | 0.3 / 1 | block; bound `qwen/qwen3-coder-standard-max` | 0.35 / 0.1 / — / 1.8 |
| `qwen/qwen-plus` | 1/1 | 1 | unknown | 1,000,000 | 0.26 / 0.78 | block; bound `qwen/qwen-plus-standard-max` | 0.78 / 0.156 / 0.975 / 2.34 |
| `moonshotai/kimi-k3` | 22/25 | 17 | fp4, fp8, mxfp4, unknown | 1,048,576 | 0.8 / 13.5 | block; bound `moonshotai/kimi-k3-standard-max` | 4.5 / 1.2 / 3.75 / 22.5 |
| `moonshotai/kimi-k2.7-code` | 12/12 | 9 | fp4, fp8, int4, unknown | 256,000–262,144 | 0.6712 / 3.35 | block; bound `moonshotai/kimi-k2.7-code-standard-max` | 1.9 / 0.38 / — / 8 |
| `moonshotai/kimi-k2.6` | 17/17 | 13 | bf16, fp4, fp8, int4, unknown | 256,000–262,144 | 0.465 / 2.45 | block; bound `moonshotai/kimi-k2.6-standard-max` | 1.09 / 0.37 / — / 4.6 |
| `z-ai/glm-5.3` | 37/41 | 23 | fp4, fp8, nvfp4, unknown | 262,124–1,048,576 | 0.039 / 6 | block; bound `z-ai/glm-5.3-standard-max` | 1.54 / 0.26 / — / 6 |
| `z-ai/glm-5.3-flash` | 32/33 | 19 | fp4, fp8, nvfp4, unknown | 262,144–1,048,576 | 0.15 / 0.5 | block; bound `z-ai/glm-5.3-flash-standard-max` | 0.225 / 0.099 / — / 1.6 |
| `z-ai/glm-5.3-flashx` | 1/1 | 1 | fp8 | 1,048,576 | 0.37 / 1.25 | live catalog sufficient | 0.37 / 0.09 / — / 1.25 |
| `z-ai/glm-5.2` | 29/34 | 21 | fp4, fp8, mxfp4, nvfp4, unknown | 262,144–1,048,576 | 0.06 / 7 | block; bound `z-ai/glm-5.2-standard-max` | 1.54 / 0.26 / — / 10 |
| `z-ai/glm-5.1` | 13/13 | 10 | fp8, unknown | 200,000–204,800 | 0.966 / 3.036 | block; bound `z-ai/glm-5.1-standard-max` | 1.4014 / 0.6 / — / 4.4044 |
| `z-ai/glm-5` | 8/8 | 5 | fp8, unknown | 198,000–204,800 | 0.6 / 1.92 | block; bound `z-ai/glm-5-standard-max` | 1 / 0.2 / — / 3.2 |
| `z-ai/glm-4.7` | 6/6 | 6 | fp4, fp8, unknown | 131,072–204,800 | 0.6 / 2.2 | block; bound `z-ai/glm-4.7-standard-max` | 0.7 / 0.11 / — / 2.5 |
| `z-ai/glm-4.6` | 4/4 | 4 | bf16, fp4 | 198,000–204,800 | 0.5 / 2 | block; bound `z-ai/glm-4.6-standard-max` | 0.6 / 0.11 / — / 2.2 |
| `z-ai/glm-4.5-air` | 3/3 | 3 | bf16, fp8 | 131,072 | 0.13 / 0.85 | block; bound `z-ai/glm-4.5-air-standard-max` | 0.2 / 0.03 / — / 1.1 |

How to read this table:

- "Price sets" counts distinct base rate cards and does not count `overrides`. `qwen/qwen3.7-plus`, `qwen3-coder-flash` and `qwen-plus` each have one base card, but `min_prompt_tokens` overrides raise long prompts to the "worst case" column, which the package cannot see.
- The two `qwen3.8` identities are "sufficient" because both Alibaba endpoints bill identical input, cached and output rates at one quantization and context. They differ only in whether a cache-write rate is published. The headline has no cache-write rate, so a `cache_write_input_tokens` count stays missing (partial), never underpriced.
- `qwen/qwen3-coder-next` and `z-ai/glm-5.3-flashx` have a single endpoint each, so the verdict holds until another endpoint appears.

These four OpenRouter identities need **no entry and no block**.

## 2. Ready-to-paste `prices` entries

Each entry's `notes` starts with `Checked 2026-10-10.` The writer must add `2026-10-10` to the review-date regex in `tests/Unit/PackagePricingSourceTest.php`, bump `version` to 12, set `retrieved_at`, add exact-decimal tests, and update the README billing-basis table and the changelog, following the CONTRIBUTING checklist.

### 2a. DeepSeek (direct)

Only `deepseek-flash` is new. The existing `deepseek-v4-pro-peak` is unchanged (section 4).

```php
        'deepseek:deepseek-flash-peak' => [
            'source' => 'https://api-docs.deepseek.com/quick_start/pricing',
            'notes' => 'Checked 2026-10-10. Direct DeepSeek API model deepseek-flash, which currently runs DeepSeek-V4.1-Flash (released 2026-09-10). Synthetic peak billing basis, not a provider alias: USD 0.30/M cache-miss input, 0.006/M cache-hit input, 1.20/M output. Peak hours are 01:00-04:00 and 06:00-10:00 UTC Monday-Friday except Chinese public holidays; all other hours, weekends and Chinese public holidays are off-peak at half price. May be used as a conservative reservation at any time, not an exact off-peak invoice. Exclusive input/cache-hit partitions; thinking (the default mode) is output usage. The legacy names deepseek-v4-flash and deepseek-v4-flash-vision-exp are temporarily routed to V4.1-Flash and billed at this price, but stay fallback-blocked identities rather than aliases because the price is time-tiered.',
            'rates' => [
                'input_tokens' => ['amount' => '0.3', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.006', 'per' => '1000000'],
                'output_tokens' => ['amount' => '1.2', 'per' => '1000000'],
            ],
        ],
```

Optional off-peak bases. The existing convention publishes only a conservative `-peak` basis. Add these only if callers can prove a request ran entirely off-peak (see uncertainty U5).

```php
        'deepseek:deepseek-flash-off-peak' => [
            'source' => 'https://api-docs.deepseek.com/quick_start/pricing',
            'notes' => 'Checked 2026-10-10. OPTIONAL. Direct DeepSeek API model deepseek-flash (DeepSeek-V4.1-Flash), off-peak billing basis, not a provider alias: USD 0.15/M cache-miss input, 0.003/M cache-hit input, 0.60/M output. Peak hours are 01:00-04:00 and 06:00-10:00 UTC Monday-Friday except Chinese public holidays; all other hours, weekends and Chinese public holidays are off-peak at half price. Use only for a request the caller knows ran entirely off-peak; DeepSeek does not document which timestamp decides a request that crosses a boundary, and a request billed at peak would be understated by half. Exclusive input/cache-hit partitions; thinking is output usage.',
            'rates' => [
                'input_tokens' => ['amount' => '0.15', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.003', 'per' => '1000000'],
                'output_tokens' => ['amount' => '0.6', 'per' => '1000000'],
            ],
        ],
        'deepseek:deepseek-v4-pro-off-peak' => [
            'source' => 'https://api-docs.deepseek.com/quick_start/pricing',
            'notes' => 'Checked 2026-10-10. OPTIONAL. Direct deepseek-v4-pro, currently DeepSeek-V4-Pro-0813, off-peak billing basis, not a provider alias: USD 0.66/M cache-miss input, 0.022/M cache-hit input, 1.98/M output. Peak hours are 01:00-04:00 and 06:00-10:00 UTC Monday-Friday except Chinese public holidays; all other hours, weekends and Chinese public holidays are off-peak at half price. Use only for a request the caller knows ran entirely off-peak; DeepSeek does not document which timestamp decides a request that crosses a boundary. Exclusive input/cache-hit partitions; thinking is output usage.',
            'rates' => [
                'input_tokens' => ['amount' => '0.66', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.022', 'per' => '1000000'],
                'output_tokens' => ['amount' => '1.98', 'per' => '1000000'],
            ],
        ],
```

### 2b. Z.ai (direct)

```php
        'zai:glm-5.3-flashx' => [
            'source' => 'https://docs.z.ai/guides/overview/pricing',
            'notes' => 'Checked 2026-10-10. Direct Z.AI pay-as-you-go API glm-5.3-flashx, not Coding Plan or OpenRouter. High-speed sibling of GLM-5.3-Flash with its own model ID and rates. Exclusive input/cache-hit partitions; output includes reasoning. Cache storage is only limited-time free and is deliberately unpriced; storage or tool usage must not be silently omitted.',
            'rates' => [
                'input_tokens' => ['amount' => '0.37', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.075', 'per' => '1000000'],
                'output_tokens' => ['amount' => '1.25', 'per' => '1000000'],
            ],
        ],
        'zai:glm-5.2' => [
            'source' => 'https://docs.z.ai/guides/overview/pricing',
            'notes' => 'Checked 2026-10-10. Direct Z.AI pay-as-you-go API glm-5.2, not Coding Plan or OpenRouter. Exclusive input/cache-hit partitions; output includes reasoning. Cache storage is only limited-time free and is deliberately unpriced; storage or tool usage must not be silently omitted.',
            'rates' => [
                'input_tokens' => ['amount' => '1.4', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.26', 'per' => '1000000'],
                'output_tokens' => ['amount' => '4.4', 'per' => '1000000'],
            ],
        ],
        'zai:glm-5.1' => [
            'source' => 'https://docs.z.ai/guides/overview/pricing',
            'notes' => 'Checked 2026-10-10. Direct Z.AI pay-as-you-go API glm-5.1, not Coding Plan or OpenRouter. 200K context. Exclusive input/cache-hit partitions; output includes reasoning. Cache storage is only limited-time free and is deliberately unpriced; storage or tool usage must not be silently omitted.',
            'rates' => [
                'input_tokens' => ['amount' => '1.4', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.26', 'per' => '1000000'],
                'output_tokens' => ['amount' => '4.4', 'per' => '1000000'],
            ],
        ],
        'zai:glm-5' => [
            'source' => 'https://docs.z.ai/guides/overview/pricing',
            'notes' => 'Checked 2026-10-10. Direct Z.AI pay-as-you-go API glm-5, not Coding Plan or OpenRouter. 200K context. Exclusive input/cache-hit partitions; output includes reasoning. Cache storage is only limited-time free and is deliberately unpriced; storage or tool usage must not be silently omitted.',
            'rates' => [
                'input_tokens' => ['amount' => '1', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.2', 'per' => '1000000'],
                'output_tokens' => ['amount' => '3.2', 'per' => '1000000'],
            ],
        ],
        'zai:glm-4.7' => [
            'source' => 'https://docs.z.ai/guides/overview/pricing',
            'notes' => 'Checked 2026-10-10. Direct Z.AI pay-as-you-go API glm-4.7, not Coding Plan or OpenRouter. Older generation, still listed; not GLM-4.7-Flash (free) or GLM-4.7-FlashX. Exclusive input/cache-hit partitions; output includes reasoning. Cache storage is only limited-time free and is deliberately unpriced; storage or tool usage must not be silently omitted.',
            'rates' => [
                'input_tokens' => ['amount' => '0.6', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.11', 'per' => '1000000'],
                'output_tokens' => ['amount' => '2.2', 'per' => '1000000'],
            ],
        ],
        'zai:glm-4.6' => [
            'source' => 'https://docs.z.ai/guides/overview/pricing',
            'notes' => 'Checked 2026-10-10. Direct Z.AI pay-as-you-go API glm-4.6, not Coding Plan or OpenRouter. Older generation, still listed. Exclusive input/cache-hit partitions; output includes reasoning. Cache storage is only limited-time free and is deliberately unpriced; storage or tool usage must not be silently omitted.',
            'rates' => [
                'input_tokens' => ['amount' => '0.6', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.11', 'per' => '1000000'],
                'output_tokens' => ['amount' => '2.2', 'per' => '1000000'],
            ],
        ],
        'zai:glm-4.5-air' => [
            'source' => 'https://docs.z.ai/guides/overview/pricing',
            'notes' => 'Checked 2026-10-10. Direct Z.AI pay-as-you-go API glm-4.5-air, not Coding Plan or OpenRouter. Older generation, still listed; not GLM-4.5-AirX. Exclusive input/cache-hit partitions; output includes reasoning. Cache storage is only limited-time free and is deliberately unpriced; storage or tool usage must not be silently omitted.',
            'rates' => [
                'input_tokens' => ['amount' => '0.2', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.03', 'per' => '1000000'],
                'output_tokens' => ['amount' => '1.1', 'per' => '1000000'],
            ],
        ],
```

### 2c. Kimi / Moonshot (direct, international)

```php
        'moonshot:kimi-k3' => [
            'source' => 'https://platform.kimi.ai/docs/pricing/chat',
            'notes' => 'Checked 2026-10-10. Direct Kimi API Platform (international, USD; platform.moonshot.ai now redirects to platform.kimi.ai, API host api.moonshot.ai), pay-as-you-go, not Kimi Membership, Kimi Code or batch. USD 3.00/M cache-miss input, 0.30/M cached input, 15.00/M output, cache writes 3.00/M (5-minute TTL, the default) and 6.00/M (1-hour TTL) per https://platform.kimi.ai/docs/guide/context-caching. Cache reads, writes and uncached input are exclusive partitions of prompt_tokens; reasoning is output usage. Chat Completions and Responses report only an aggregate cache_write_tokens; only the Messages API reports the TTL split, so an aggregate write without a split stays a missing unit. 1M context, no context tier. Built-in $web_search (legacy, deprecating 2026-10-20) and the /v1/tools REST endpoints are separately billed and not priced here.',
            'rates' => [
                'input_tokens' => ['amount' => '3', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.3', 'per' => '1000000'],
                'cache_write_input_tokens_5m' => ['amount' => '3', 'per' => '1000000'],
                'cache_write_input_tokens_1h' => ['amount' => '6', 'per' => '1000000'],
                'output_tokens' => ['amount' => '15', 'per' => '1000000'],
            ],
        ],
        'moonshot:kimi-k2.7-code' => [
            'source' => 'https://platform.kimi.ai/docs/pricing/chat',
            'notes' => 'Checked 2026-10-10. Direct Kimi API Platform (international, USD), pay-as-you-go, not batch. Dedicated coding model, 256K context. Automatic context caching with no separate cache-write charge for K2-series models per https://platform.kimi.ai/docs/guide/context-caching; exclusive input/cache-hit partitions; reasoning is output usage. Tools are separately billed and not priced here.',
            'rates' => [
                'input_tokens' => ['amount' => '0.95', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.19', 'per' => '1000000'],
                'output_tokens' => ['amount' => '4', 'per' => '1000000'],
            ],
        ],
        'moonshot:kimi-k2.7-code-highspeed' => [
            'source' => 'https://platform.kimi.ai/docs/pricing/chat',
            'notes' => 'Checked 2026-10-10. Direct Kimi API Platform (international, USD), pay-as-you-go, not batch. Same weights as kimi-k2.7-code served at higher output speed under its own model ID and rates. Automatic context caching with no separate cache-write charge for K2-series models per https://platform.kimi.ai/docs/guide/context-caching; exclusive input/cache-hit partitions; reasoning is output usage. Tools are separately billed and not priced here.',
            'rates' => [
                'input_tokens' => ['amount' => '1.9', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.38', 'per' => '1000000'],
                'output_tokens' => ['amount' => '8', 'per' => '1000000'],
            ],
        ],
        'moonshot:kimi-k2.6' => [
            'source' => 'https://platform.kimi.ai/docs/pricing/chat',
            'notes' => 'Checked 2026-10-10. Direct Kimi API Platform (international, USD), pay-as-you-go, not batch. Multimodal model with thinking and non-thinking modes, 256K context. Automatic context caching with no separate cache-write charge for K2-series models per https://platform.kimi.ai/docs/guide/context-caching; exclusive input/cache-hit partitions; reasoning is output usage. Tools are separately billed and not priced here.',
            'rates' => [
                'input_tokens' => ['amount' => '0.95', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.16', 'per' => '1000000'],
                'output_tokens' => ['amount' => '4', 'per' => '1000000'],
            ],
        ],
```

### 2d. Qwen / Alibaba Cloud Model Studio (direct, Singapore region, International deployment scope)

Region: **Singapore** (`dashscope-intl.aliyuncs.com`), deployment scope **International**, from the `#### Singapore` tables on the pricing page. Mainland China (Beijing), Hong Kong, Frankfurt, Virginia and Tokyo rates, and the cheaper "Global" deployment scope rates (for example qwen3.8-max at 1.65/4.951), are excluded. Cache rates are derived from https://www.alibabacloud.com/help/en/model-studio/context-cache:

- implicit-cache hit at 20% of the tier input price;
- explicit cache creation at 125%;
- explicit hit at 10%.

See judgment call J1 for why `cached_input_tokens` uses 20%.

```php
        'dashscope:qwen3.8-max-intl' => [
            'source' => 'https://www.alibabacloud.com/help/en/model-studio/model-pricing',
            'notes' => 'Checked 2026-10-10. qwen3.8-max (flagship; also covers the qwen3.8-max-0902 snapshot at identical rates): Alibaba Cloud Model Studio Singapore region (dashscope-intl), International deployment scope, standard real-time list price; not China (Beijing), Hong Kong, Frankfurt, Virginia or Tokyo, not Global, US or EU deployment scope, not batch, not limited-time discounts, free quota not subtracted. Billing basis, not a provider alias. Applies to requests with at most 1,000,000 input tokens per request. Thinking and non-thinking modes bill the same output rate. Cache-hit tokens are deliberately unpriced: https://www.alibabacloud.com/help/en/model-studio/context-cache says the qwen3.8 cache-hit rate is not the usual 10%/20% and is published only in the console, so a cached_input_tokens count keeps the quote partial. cache_write_input_tokens is explicit cache creation at 125% of the input price (5-minute TTL). Reasoning (chain of thought) is output usage.',
            'rates' => [
                'input_tokens' => ['amount' => '2', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '2.5', 'per' => '1000000'],
                'output_tokens' => ['amount' => '6', 'per' => '1000000'],
            ],
        ],
        'dashscope:qwen3.8-flash-intl' => [
            'source' => 'https://www.alibabacloud.com/help/en/model-studio/model-pricing',
            'notes' => 'Checked 2026-10-10. qwen3.8-flash: Alibaba Cloud Model Studio Singapore region (dashscope-intl), International deployment scope, standard real-time list price; not China (Beijing), Hong Kong, Frankfurt, Virginia or Tokyo, not Global, US or EU deployment scope, not batch, not limited-time discounts, free quota not subtracted. Billing basis, not a provider alias. Applies to requests with at most 1,000,000 input tokens per request. Cache-hit tokens are deliberately unpriced: https://www.alibabacloud.com/help/en/model-studio/context-cache says the qwen3.8 cache-hit rate is not the usual 10%/20% and is published only in the console, so a cached_input_tokens count keeps the quote partial. cache_write_input_tokens is explicit cache creation at 125% of the input price (5-minute TTL). Reasoning (chain of thought) is output usage.',
            'rates' => [
                'input_tokens' => ['amount' => '0.15', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '0.1875', 'per' => '1000000'],
                'output_tokens' => ['amount' => '0.47', 'per' => '1000000'],
            ],
        ],
        'dashscope:qwen3.7-max-intl' => [
            'source' => 'https://www.alibabacloud.com/help/en/model-studio/model-pricing',
            'notes' => 'Checked 2026-10-10. qwen3.7-max (currently qwen3.7-max-2026-05-20; previous-generation flagship): Alibaba Cloud Model Studio Singapore region (dashscope-intl), International deployment scope, standard real-time list price; not China (Beijing), Hong Kong, Frankfurt, Virginia or Tokyo, not Global, US or EU deployment scope, not batch, not limited-time discounts, free quota not subtracted. Billing basis, not a provider alias. Applies to requests with at most 1,000,000 input tokens per request. Thinking and non-thinking modes bill the same output rate. cached_input_tokens is the implicit-cache hit rate (20% of the tier input price per https://www.alibabacloud.com/help/en/model-studio/context-cache); explicit-cache hits bill at 10%, so this overstates them. cache_write_input_tokens is explicit cache creation at 125% of the tier input price (5-minute TTL); implicit caching has no write charge. Reasoning (chain of thought) is output usage.',
            'rates' => [
                'input_tokens' => ['amount' => '2.5', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.5', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '3.125', 'per' => '1000000'],
                'output_tokens' => ['amount' => '7.5', 'per' => '1000000'],
            ],
        ],
        'dashscope:qwen3.7-plus-intl-256k' => [
            'source' => 'https://www.alibabacloud.com/help/en/model-studio/model-pricing',
            'notes' => 'Checked 2026-10-10. qwen3.7-plus (currently qwen3.7-plus-2026-05-26; the generic alias carries a limited-time 20% discount that is excluded): Alibaba Cloud Model Studio Singapore region (dashscope-intl), International deployment scope, standard real-time list price; not China (Beijing), Hong Kong, Frankfurt, Virginia or Tokyo, not Global, US or EU deployment scope, not batch, not limited-time discounts, free quota not subtracted. Billing basis, not a provider alias. Applies to requests with at most 256,000 input tokens per request. The tier is chosen by the total input tokens of the request, including cache hits and cache creation, and every token of the request, including output, bills at that tier. cached_input_tokens is the implicit-cache hit rate (20% of the tier input price per https://www.alibabacloud.com/help/en/model-studio/context-cache); explicit-cache hits bill at 10%, so this overstates them. cache_write_input_tokens is explicit cache creation at 125% of the tier input price (5-minute TTL); implicit caching has no write charge. Reasoning (chain of thought) is output usage.',
            'rates' => [
                'input_tokens' => ['amount' => '0.4', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.08', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '0.5', 'per' => '1000000'],
                'output_tokens' => ['amount' => '1.6', 'per' => '1000000'],
            ],
        ],
        'dashscope:qwen3.7-plus-intl-1m' => [
            'source' => 'https://www.alibabacloud.com/help/en/model-studio/model-pricing',
            'notes' => 'Checked 2026-10-10. qwen3.7-plus (currently qwen3.7-plus-2026-05-26; the generic alias carries a limited-time 20% discount that is excluded): Alibaba Cloud Model Studio Singapore region (dashscope-intl), International deployment scope, standard real-time list price; not China (Beijing), Hong Kong, Frankfurt, Virginia or Tokyo, not Global, US or EU deployment scope, not batch, not limited-time discounts, free quota not subtracted. Billing basis, not a provider alias. Applies to requests with more than 256,000 and at most 1,000,000 input tokens per request. The tier is chosen by the total input tokens of the request, including cache hits and cache creation, and every token of the request, including output, bills at that tier. cached_input_tokens is the implicit-cache hit rate (20% of the tier input price per https://www.alibabacloud.com/help/en/model-studio/context-cache); explicit-cache hits bill at 10%, so this overstates them. cache_write_input_tokens is explicit cache creation at 125% of the tier input price (5-minute TTL); implicit caching has no write charge. Reasoning (chain of thought) is output usage.',
            'rates' => [
                'input_tokens' => ['amount' => '1.2', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.24', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '1.5', 'per' => '1000000'],
                'output_tokens' => ['amount' => '4.8', 'per' => '1000000'],
            ],
        ],
        'dashscope:qwen3.7-flash-intl-32k' => [
            'source' => 'https://www.alibabacloud.com/help/en/model-studio/model-pricing',
            'notes' => 'Checked 2026-10-10. qwen3.7-flash (currently qwen3.7-flash-2026-07-15): Alibaba Cloud Model Studio Singapore region (dashscope-intl), International deployment scope, standard real-time list price; not China (Beijing), Hong Kong, Frankfurt, Virginia or Tokyo, not Global, US or EU deployment scope, not batch, not limited-time discounts, free quota not subtracted. Billing basis, not a provider alias. Applies to requests with at most 32,000 input tokens per request. The tier is chosen by the total input tokens of the request, including cache hits and cache creation, and every token of the request, including output, bills at that tier. cached_input_tokens is the implicit-cache hit rate (20% of the tier input price per https://www.alibabacloud.com/help/en/model-studio/context-cache); explicit-cache hits bill at 10%, so this overstates them. cache_write_input_tokens is explicit cache creation at 125% of the tier input price (5-minute TTL); implicit caching has no write charge. Reasoning (chain of thought) is output usage.',
            'rates' => [
                'input_tokens' => ['amount' => '0.03', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.006', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '0.0375', 'per' => '1000000'],
                'output_tokens' => ['amount' => '0.13', 'per' => '1000000'],
            ],
        ],
        'dashscope:qwen3.7-flash-intl-256k' => [
            'source' => 'https://www.alibabacloud.com/help/en/model-studio/model-pricing',
            'notes' => 'Checked 2026-10-10. qwen3.7-flash (currently qwen3.7-flash-2026-07-15): Alibaba Cloud Model Studio Singapore region (dashscope-intl), International deployment scope, standard real-time list price; not China (Beijing), Hong Kong, Frankfurt, Virginia or Tokyo, not Global, US or EU deployment scope, not batch, not limited-time discounts, free quota not subtracted. Billing basis, not a provider alias. Applies to requests with more than 32,000 and at most 256,000 input tokens per request. The tier is chosen by the total input tokens of the request, including cache hits and cache creation, and every token of the request, including output, bills at that tier. cached_input_tokens is the implicit-cache hit rate (20% of the tier input price per https://www.alibabacloud.com/help/en/model-studio/context-cache); explicit-cache hits bill at 10%, so this overstates them. cache_write_input_tokens is explicit cache creation at 125% of the tier input price (5-minute TTL); implicit caching has no write charge. Reasoning (chain of thought) is output usage.',
            'rates' => [
                'input_tokens' => ['amount' => '0.1', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.02', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '0.125', 'per' => '1000000'],
                'output_tokens' => ['amount' => '0.4', 'per' => '1000000'],
            ],
        ],
        'dashscope:qwen3.7-flash-intl-1m' => [
            'source' => 'https://www.alibabacloud.com/help/en/model-studio/model-pricing',
            'notes' => 'Checked 2026-10-10. qwen3.7-flash (currently qwen3.7-flash-2026-07-15): Alibaba Cloud Model Studio Singapore region (dashscope-intl), International deployment scope, standard real-time list price; not China (Beijing), Hong Kong, Frankfurt, Virginia or Tokyo, not Global, US or EU deployment scope, not batch, not limited-time discounts, free quota not subtracted. Billing basis, not a provider alias. Applies to requests with more than 256,000 and at most 1,000,000 input tokens per request. The tier is chosen by the total input tokens of the request, including cache hits and cache creation, and every token of the request, including output, bills at that tier. cached_input_tokens is the implicit-cache hit rate (20% of the tier input price per https://www.alibabacloud.com/help/en/model-studio/context-cache); explicit-cache hits bill at 10%, so this overstates them. cache_write_input_tokens is explicit cache creation at 125% of the tier input price (5-minute TTL); implicit caching has no write charge. Reasoning (chain of thought) is output usage.',
            'rates' => [
                'input_tokens' => ['amount' => '0.2', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.04', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '0.25', 'per' => '1000000'],
                'output_tokens' => ['amount' => '0.8', 'per' => '1000000'],
            ],
        ],
        'dashscope:qwen3-max-intl-32k' => [
            'source' => 'https://www.alibabacloud.com/help/en/model-studio/model-pricing',
            'notes' => 'Checked 2026-10-10. qwen3-max (currently qwen3-max-2026-01-23; also the 2025-09-23 snapshot at identical rates): Alibaba Cloud Model Studio Singapore region (dashscope-intl), International deployment scope, standard real-time list price; not China (Beijing), Hong Kong, Frankfurt, Virginia or Tokyo, not Global, US or EU deployment scope, not batch, not limited-time discounts, free quota not subtracted. Billing basis, not a provider alias. Applies to requests with at most 32,000 input tokens per request. The tier is chosen by the total input tokens of the request, including cache hits and cache creation, and every token of the request, including output, bills at that tier. cached_input_tokens is the implicit-cache hit rate (20% of the tier input price per https://www.alibabacloud.com/help/en/model-studio/context-cache); explicit-cache hits bill at 10%, so this overstates them. cache_write_input_tokens is explicit cache creation at 125% of the tier input price (5-minute TTL); implicit caching has no write charge. Reasoning (chain of thought) is output usage.',
            'rates' => [
                'input_tokens' => ['amount' => '1.2', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.24', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '1.5', 'per' => '1000000'],
                'output_tokens' => ['amount' => '6', 'per' => '1000000'],
            ],
        ],
        'dashscope:qwen3-max-intl-128k' => [
            'source' => 'https://www.alibabacloud.com/help/en/model-studio/model-pricing',
            'notes' => 'Checked 2026-10-10. qwen3-max (currently qwen3-max-2026-01-23; also the 2025-09-23 snapshot at identical rates): Alibaba Cloud Model Studio Singapore region (dashscope-intl), International deployment scope, standard real-time list price; not China (Beijing), Hong Kong, Frankfurt, Virginia or Tokyo, not Global, US or EU deployment scope, not batch, not limited-time discounts, free quota not subtracted. Billing basis, not a provider alias. Applies to requests with more than 32,000 and at most 128,000 input tokens per request. The tier is chosen by the total input tokens of the request, including cache hits and cache creation, and every token of the request, including output, bills at that tier. cached_input_tokens is the implicit-cache hit rate (20% of the tier input price per https://www.alibabacloud.com/help/en/model-studio/context-cache); explicit-cache hits bill at 10%, so this overstates them. cache_write_input_tokens is explicit cache creation at 125% of the tier input price (5-minute TTL); implicit caching has no write charge. Reasoning (chain of thought) is output usage.',
            'rates' => [
                'input_tokens' => ['amount' => '2.4', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.48', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '3', 'per' => '1000000'],
                'output_tokens' => ['amount' => '12', 'per' => '1000000'],
            ],
        ],
        'dashscope:qwen3-max-intl-256k' => [
            'source' => 'https://www.alibabacloud.com/help/en/model-studio/model-pricing',
            'notes' => 'Checked 2026-10-10. qwen3-max (currently qwen3-max-2026-01-23; also the 2025-09-23 snapshot at identical rates): Alibaba Cloud Model Studio Singapore region (dashscope-intl), International deployment scope, standard real-time list price; not China (Beijing), Hong Kong, Frankfurt, Virginia or Tokyo, not Global, US or EU deployment scope, not batch, not limited-time discounts, free quota not subtracted. Billing basis, not a provider alias. Applies to requests with more than 128,000 and at most 256,000 input tokens per request. The tier is chosen by the total input tokens of the request, including cache hits and cache creation, and every token of the request, including output, bills at that tier. cached_input_tokens is the implicit-cache hit rate (20% of the tier input price per https://www.alibabacloud.com/help/en/model-studio/context-cache); explicit-cache hits bill at 10%, so this overstates them. cache_write_input_tokens is explicit cache creation at 125% of the tier input price (5-minute TTL); implicit caching has no write charge. Reasoning (chain of thought) is output usage.',
            'rates' => [
                'input_tokens' => ['amount' => '3', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.6', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '3.75', 'per' => '1000000'],
                'output_tokens' => ['amount' => '15', 'per' => '1000000'],
            ],
        ],
        'dashscope:qwen-plus-intl-nonthinking-256k' => [
            'source' => 'https://www.alibabacloud.com/help/en/model-studio/model-pricing',
            'notes' => 'Checked 2026-10-10. qwen-plus (currently qwen-plus-2025-12-01; also qwen-plus-latest), non-thinking mode only; the output rate differs by mode: Alibaba Cloud Model Studio Singapore region (dashscope-intl), International deployment scope, standard real-time list price; not China (Beijing), Hong Kong, Frankfurt, Virginia or Tokyo, not Global, US or EU deployment scope, not batch, not limited-time discounts, free quota not subtracted. Billing basis, not a provider alias. Applies to requests with at most 256,000 input tokens per request. The tier is chosen by the total input tokens of the request, including cache hits and cache creation, and every token of the request, including output, bills at that tier. cached_input_tokens is the implicit-cache hit rate (20% of the tier input price per https://www.alibabacloud.com/help/en/model-studio/context-cache); explicit-cache hits bill at 10%, so this overstates them. cache_write_input_tokens is explicit cache creation at 125% of the tier input price (5-minute TTL); implicit caching has no write charge. Reasoning (chain of thought) is output usage.',
            'rates' => [
                'input_tokens' => ['amount' => '0.4', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.08', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '0.5', 'per' => '1000000'],
                'output_tokens' => ['amount' => '1.2', 'per' => '1000000'],
            ],
        ],
        'dashscope:qwen-plus-intl-thinking-256k' => [
            'source' => 'https://www.alibabacloud.com/help/en/model-studio/model-pricing',
            'notes' => 'Checked 2026-10-10. qwen-plus (currently qwen-plus-2025-12-01; also qwen-plus-latest), thinking mode only; the output rate differs by mode: Alibaba Cloud Model Studio Singapore region (dashscope-intl), International deployment scope, standard real-time list price; not China (Beijing), Hong Kong, Frankfurt, Virginia or Tokyo, not Global, US or EU deployment scope, not batch, not limited-time discounts, free quota not subtracted. Billing basis, not a provider alias. Applies to requests with at most 256,000 input tokens per request. The tier is chosen by the total input tokens of the request, including cache hits and cache creation, and every token of the request, including output, bills at that tier. cached_input_tokens is the implicit-cache hit rate (20% of the tier input price per https://www.alibabacloud.com/help/en/model-studio/context-cache); explicit-cache hits bill at 10%, so this overstates them. cache_write_input_tokens is explicit cache creation at 125% of the tier input price (5-minute TTL); implicit caching has no write charge. Reasoning (chain of thought) is output usage.',
            'rates' => [
                'input_tokens' => ['amount' => '0.4', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.08', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '0.5', 'per' => '1000000'],
                'output_tokens' => ['amount' => '4', 'per' => '1000000'],
            ],
        ],
        'dashscope:qwen-plus-intl-nonthinking-1m' => [
            'source' => 'https://www.alibabacloud.com/help/en/model-studio/model-pricing',
            'notes' => 'Checked 2026-10-10. qwen-plus (currently qwen-plus-2025-12-01; also qwen-plus-latest), non-thinking mode only; the output rate differs by mode: Alibaba Cloud Model Studio Singapore region (dashscope-intl), International deployment scope, standard real-time list price; not China (Beijing), Hong Kong, Frankfurt, Virginia or Tokyo, not Global, US or EU deployment scope, not batch, not limited-time discounts, free quota not subtracted. Billing basis, not a provider alias. Applies to requests with more than 256,000 and at most 1,000,000 input tokens per request. The tier is chosen by the total input tokens of the request, including cache hits and cache creation, and every token of the request, including output, bills at that tier. cached_input_tokens is the implicit-cache hit rate (20% of the tier input price per https://www.alibabacloud.com/help/en/model-studio/context-cache); explicit-cache hits bill at 10%, so this overstates them. cache_write_input_tokens is explicit cache creation at 125% of the tier input price (5-minute TTL); implicit caching has no write charge. Reasoning (chain of thought) is output usage.',
            'rates' => [
                'input_tokens' => ['amount' => '1.2', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.24', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '1.5', 'per' => '1000000'],
                'output_tokens' => ['amount' => '3.6', 'per' => '1000000'],
            ],
        ],
        'dashscope:qwen-plus-intl-thinking-1m' => [
            'source' => 'https://www.alibabacloud.com/help/en/model-studio/model-pricing',
            'notes' => 'Checked 2026-10-10. qwen-plus (currently qwen-plus-2025-12-01; also qwen-plus-latest), thinking mode only; the output rate differs by mode: Alibaba Cloud Model Studio Singapore region (dashscope-intl), International deployment scope, standard real-time list price; not China (Beijing), Hong Kong, Frankfurt, Virginia or Tokyo, not Global, US or EU deployment scope, not batch, not limited-time discounts, free quota not subtracted. Billing basis, not a provider alias. Applies to requests with more than 256,000 and at most 1,000,000 input tokens per request. The tier is chosen by the total input tokens of the request, including cache hits and cache creation, and every token of the request, including output, bills at that tier. cached_input_tokens is the implicit-cache hit rate (20% of the tier input price per https://www.alibabacloud.com/help/en/model-studio/context-cache); explicit-cache hits bill at 10%, so this overstates them. cache_write_input_tokens is explicit cache creation at 125% of the tier input price (5-minute TTL); implicit caching has no write charge. Reasoning (chain of thought) is output usage.',
            'rates' => [
                'input_tokens' => ['amount' => '1.2', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.24', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '1.5', 'per' => '1000000'],
                'output_tokens' => ['amount' => '12', 'per' => '1000000'],
            ],
        ],
        'dashscope:qwen-flash-intl-256k' => [
            'source' => 'https://www.alibabacloud.com/help/en/model-studio/model-pricing',
            'notes' => 'Checked 2026-10-10. qwen-flash (currently qwen-flash-2025-07-28): Alibaba Cloud Model Studio Singapore region (dashscope-intl), International deployment scope, standard real-time list price; not China (Beijing), Hong Kong, Frankfurt, Virginia or Tokyo, not Global, US or EU deployment scope, not batch, not limited-time discounts, free quota not subtracted. Billing basis, not a provider alias. Applies to requests with at most 256,000 input tokens per request. The tier is chosen by the total input tokens of the request, including cache hits and cache creation, and every token of the request, including output, bills at that tier. cached_input_tokens is the implicit-cache hit rate (20% of the tier input price per https://www.alibabacloud.com/help/en/model-studio/context-cache); explicit-cache hits bill at 10%, so this overstates them. cache_write_input_tokens is explicit cache creation at 125% of the tier input price (5-minute TTL); implicit caching has no write charge. Reasoning (chain of thought) is output usage.',
            'rates' => [
                'input_tokens' => ['amount' => '0.05', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.01', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '0.0625', 'per' => '1000000'],
                'output_tokens' => ['amount' => '0.4', 'per' => '1000000'],
            ],
        ],
        'dashscope:qwen-flash-intl-1m' => [
            'source' => 'https://www.alibabacloud.com/help/en/model-studio/model-pricing',
            'notes' => 'Checked 2026-10-10. qwen-flash (currently qwen-flash-2025-07-28): Alibaba Cloud Model Studio Singapore region (dashscope-intl), International deployment scope, standard real-time list price; not China (Beijing), Hong Kong, Frankfurt, Virginia or Tokyo, not Global, US or EU deployment scope, not batch, not limited-time discounts, free quota not subtracted. Billing basis, not a provider alias. Applies to requests with more than 256,000 and at most 1,000,000 input tokens per request. The tier is chosen by the total input tokens of the request, including cache hits and cache creation, and every token of the request, including output, bills at that tier. cached_input_tokens is the implicit-cache hit rate (20% of the tier input price per https://www.alibabacloud.com/help/en/model-studio/context-cache); explicit-cache hits bill at 10%, so this overstates them. cache_write_input_tokens is explicit cache creation at 125% of the tier input price (5-minute TTL); implicit caching has no write charge. Reasoning (chain of thought) is output usage.',
            'rates' => [
                'input_tokens' => ['amount' => '0.25', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.05', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '0.3125', 'per' => '1000000'],
                'output_tokens' => ['amount' => '2', 'per' => '1000000'],
            ],
        ],
        'dashscope:qwen3-coder-plus-intl-32k' => [
            'source' => 'https://www.alibabacloud.com/help/en/model-studio/model-pricing',
            'notes' => 'Checked 2026-10-10. qwen3-coder-plus (currently qwen3-coder-plus-2025-09-23): Alibaba Cloud Model Studio Singapore region (dashscope-intl), International deployment scope, standard real-time list price; not China (Beijing), Hong Kong, Frankfurt, Virginia or Tokyo, not Global, US or EU deployment scope, not batch, not limited-time discounts, free quota not subtracted. Billing basis, not a provider alias. Applies to requests with at most 32,000 input tokens per request. The tier is chosen by the total input tokens of the request, including cache hits and cache creation, and every token of the request, including output, bills at that tier. cached_input_tokens is the implicit-cache hit rate (20% of the tier input price per https://www.alibabacloud.com/help/en/model-studio/context-cache); explicit-cache hits bill at 10%, so this overstates them. cache_write_input_tokens is explicit cache creation at 125% of the tier input price (5-minute TTL); implicit caching has no write charge. Reasoning (chain of thought) is output usage.',
            'rates' => [
                'input_tokens' => ['amount' => '1', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.2', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '1.25', 'per' => '1000000'],
                'output_tokens' => ['amount' => '5', 'per' => '1000000'],
            ],
        ],
        'dashscope:qwen3-coder-plus-intl-128k' => [
            'source' => 'https://www.alibabacloud.com/help/en/model-studio/model-pricing',
            'notes' => 'Checked 2026-10-10. qwen3-coder-plus (currently qwen3-coder-plus-2025-09-23): Alibaba Cloud Model Studio Singapore region (dashscope-intl), International deployment scope, standard real-time list price; not China (Beijing), Hong Kong, Frankfurt, Virginia or Tokyo, not Global, US or EU deployment scope, not batch, not limited-time discounts, free quota not subtracted. Billing basis, not a provider alias. Applies to requests with more than 32,000 and at most 128,000 input tokens per request. The tier is chosen by the total input tokens of the request, including cache hits and cache creation, and every token of the request, including output, bills at that tier. cached_input_tokens is the implicit-cache hit rate (20% of the tier input price per https://www.alibabacloud.com/help/en/model-studio/context-cache); explicit-cache hits bill at 10%, so this overstates them. cache_write_input_tokens is explicit cache creation at 125% of the tier input price (5-minute TTL); implicit caching has no write charge. Reasoning (chain of thought) is output usage.',
            'rates' => [
                'input_tokens' => ['amount' => '1.8', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.36', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '2.25', 'per' => '1000000'],
                'output_tokens' => ['amount' => '9', 'per' => '1000000'],
            ],
        ],
        'dashscope:qwen3-coder-plus-intl-256k' => [
            'source' => 'https://www.alibabacloud.com/help/en/model-studio/model-pricing',
            'notes' => 'Checked 2026-10-10. qwen3-coder-plus (currently qwen3-coder-plus-2025-09-23): Alibaba Cloud Model Studio Singapore region (dashscope-intl), International deployment scope, standard real-time list price; not China (Beijing), Hong Kong, Frankfurt, Virginia or Tokyo, not Global, US or EU deployment scope, not batch, not limited-time discounts, free quota not subtracted. Billing basis, not a provider alias. Applies to requests with more than 128,000 and at most 256,000 input tokens per request. The tier is chosen by the total input tokens of the request, including cache hits and cache creation, and every token of the request, including output, bills at that tier. cached_input_tokens is the implicit-cache hit rate (20% of the tier input price per https://www.alibabacloud.com/help/en/model-studio/context-cache); explicit-cache hits bill at 10%, so this overstates them. cache_write_input_tokens is explicit cache creation at 125% of the tier input price (5-minute TTL); implicit caching has no write charge. Reasoning (chain of thought) is output usage.',
            'rates' => [
                'input_tokens' => ['amount' => '3', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.6', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '3.75', 'per' => '1000000'],
                'output_tokens' => ['amount' => '15', 'per' => '1000000'],
            ],
        ],
        'dashscope:qwen3-coder-plus-intl-1m' => [
            'source' => 'https://www.alibabacloud.com/help/en/model-studio/model-pricing',
            'notes' => 'Checked 2026-10-10. qwen3-coder-plus (currently qwen3-coder-plus-2025-09-23): Alibaba Cloud Model Studio Singapore region (dashscope-intl), International deployment scope, standard real-time list price; not China (Beijing), Hong Kong, Frankfurt, Virginia or Tokyo, not Global, US or EU deployment scope, not batch, not limited-time discounts, free quota not subtracted. Billing basis, not a provider alias. Applies to requests with more than 256,000 and at most 1,000,000 input tokens per request. The tier is chosen by the total input tokens of the request, including cache hits and cache creation, and every token of the request, including output, bills at that tier. cached_input_tokens is the implicit-cache hit rate (20% of the tier input price per https://www.alibabacloud.com/help/en/model-studio/context-cache); explicit-cache hits bill at 10%, so this overstates them. cache_write_input_tokens is explicit cache creation at 125% of the tier input price (5-minute TTL); implicit caching has no write charge. Reasoning (chain of thought) is output usage.',
            'rates' => [
                'input_tokens' => ['amount' => '6', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '1.2', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '7.5', 'per' => '1000000'],
                'output_tokens' => ['amount' => '60', 'per' => '1000000'],
            ],
        ],
        'dashscope:qwen3-coder-flash-intl-32k' => [
            'source' => 'https://www.alibabacloud.com/help/en/model-studio/model-pricing',
            'notes' => 'Checked 2026-10-10. qwen3-coder-flash (currently qwen3-coder-flash-2025-07-28): Alibaba Cloud Model Studio Singapore region (dashscope-intl), International deployment scope, standard real-time list price; not China (Beijing), Hong Kong, Frankfurt, Virginia or Tokyo, not Global, US or EU deployment scope, not batch, not limited-time discounts, free quota not subtracted. Billing basis, not a provider alias. Applies to requests with at most 32,000 input tokens per request. The tier is chosen by the total input tokens of the request, including cache hits and cache creation, and every token of the request, including output, bills at that tier. cached_input_tokens is the implicit-cache hit rate (20% of the tier input price per https://www.alibabacloud.com/help/en/model-studio/context-cache); explicit-cache hits bill at 10%, so this overstates them. cache_write_input_tokens is explicit cache creation at 125% of the tier input price (5-minute TTL); implicit caching has no write charge. Reasoning (chain of thought) is output usage.',
            'rates' => [
                'input_tokens' => ['amount' => '0.3', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.06', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '0.375', 'per' => '1000000'],
                'output_tokens' => ['amount' => '1.5', 'per' => '1000000'],
            ],
        ],
        'dashscope:qwen3-coder-flash-intl-128k' => [
            'source' => 'https://www.alibabacloud.com/help/en/model-studio/model-pricing',
            'notes' => 'Checked 2026-10-10. qwen3-coder-flash (currently qwen3-coder-flash-2025-07-28): Alibaba Cloud Model Studio Singapore region (dashscope-intl), International deployment scope, standard real-time list price; not China (Beijing), Hong Kong, Frankfurt, Virginia or Tokyo, not Global, US or EU deployment scope, not batch, not limited-time discounts, free quota not subtracted. Billing basis, not a provider alias. Applies to requests with more than 32,000 and at most 128,000 input tokens per request. The tier is chosen by the total input tokens of the request, including cache hits and cache creation, and every token of the request, including output, bills at that tier. cached_input_tokens is the implicit-cache hit rate (20% of the tier input price per https://www.alibabacloud.com/help/en/model-studio/context-cache); explicit-cache hits bill at 10%, so this overstates them. cache_write_input_tokens is explicit cache creation at 125% of the tier input price (5-minute TTL); implicit caching has no write charge. Reasoning (chain of thought) is output usage.',
            'rates' => [
                'input_tokens' => ['amount' => '0.5', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.1', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '0.625', 'per' => '1000000'],
                'output_tokens' => ['amount' => '2.5', 'per' => '1000000'],
            ],
        ],
        'dashscope:qwen3-coder-flash-intl-256k' => [
            'source' => 'https://www.alibabacloud.com/help/en/model-studio/model-pricing',
            'notes' => 'Checked 2026-10-10. qwen3-coder-flash (currently qwen3-coder-flash-2025-07-28): Alibaba Cloud Model Studio Singapore region (dashscope-intl), International deployment scope, standard real-time list price; not China (Beijing), Hong Kong, Frankfurt, Virginia or Tokyo, not Global, US or EU deployment scope, not batch, not limited-time discounts, free quota not subtracted. Billing basis, not a provider alias. Applies to requests with more than 128,000 and at most 256,000 input tokens per request. The tier is chosen by the total input tokens of the request, including cache hits and cache creation, and every token of the request, including output, bills at that tier. cached_input_tokens is the implicit-cache hit rate (20% of the tier input price per https://www.alibabacloud.com/help/en/model-studio/context-cache); explicit-cache hits bill at 10%, so this overstates them. cache_write_input_tokens is explicit cache creation at 125% of the tier input price (5-minute TTL); implicit caching has no write charge. Reasoning (chain of thought) is output usage.',
            'rates' => [
                'input_tokens' => ['amount' => '0.8', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.16', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '1', 'per' => '1000000'],
                'output_tokens' => ['amount' => '4', 'per' => '1000000'],
            ],
        ],
        'dashscope:qwen3-coder-flash-intl-1m' => [
            'source' => 'https://www.alibabacloud.com/help/en/model-studio/model-pricing',
            'notes' => 'Checked 2026-10-10. qwen3-coder-flash (currently qwen3-coder-flash-2025-07-28): Alibaba Cloud Model Studio Singapore region (dashscope-intl), International deployment scope, standard real-time list price; not China (Beijing), Hong Kong, Frankfurt, Virginia or Tokyo, not Global, US or EU deployment scope, not batch, not limited-time discounts, free quota not subtracted. Billing basis, not a provider alias. Applies to requests with more than 256,000 and at most 1,000,000 input tokens per request. The tier is chosen by the total input tokens of the request, including cache hits and cache creation, and every token of the request, including output, bills at that tier. cached_input_tokens is the implicit-cache hit rate (20% of the tier input price per https://www.alibabacloud.com/help/en/model-studio/context-cache); explicit-cache hits bill at 10%, so this overstates them. cache_write_input_tokens is explicit cache creation at 125% of the tier input price (5-minute TTL); implicit caching has no write charge. Reasoning (chain of thought) is output usage.',
            'rates' => [
                'input_tokens' => ['amount' => '1.6', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.32', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '2', 'per' => '1000000'],
                'output_tokens' => ['amount' => '9.6', 'per' => '1000000'],
            ],
        ],
```

### 2e. OpenRouter bound bases (worst case under default routing)

Each basis is the per-unit maximum over all default-routed endpoints at the standard tier, including endpoints currently reporting a non-zero status, every quantization, and time-of-day and prompt-length overrides. They follow the existing `openrouter:google/gemini-3.1-flash-lite-standard-max` convention. Like the existing bound bases, each one goes in **both** `fallback_blocked` and `prices`, and in the invariant-test allowlist. See J4 for whether to ship them at all.

```php
        'openrouter:deepseek/deepseek-v4.1-flash-standard-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/deepseek/deepseek-v4.1-flash/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for deepseek/deepseek-v4.1-flash (canonical deepseek/deepseek-v4.1-flash-20260910) under OpenRouter default routing at the standard service tier; not :batch. Default routing spans 24 distinct price sets across 31 default-routed endpoints, quantizations fp4/fp8/unknown, context 1,000,000-1,048,576, time-of-day overrides, so the live catalog headline (input 0.3, output 1.2 per M) can understate a request. Each rate is the maximum over all default-routed endpoints, including every quantization and override: input_tokens 0.45 from fireworks/us; cached_input_tokens 0.049 from wafer; output_tokens 1.8 from fireworks/us. Maxima can come from different endpoints, so the basis can exceed any single endpoint. Callers must not opt into service tiers (service_tier, :nitro, :floor or tier slugs). Includes endpoints currently reporting a non-zero status (open-inference/fp4, fireworks). Reasoning is output usage; OpenRouter web search and other server tools are distinct, unpriced units. Prefer provider-reported usage.cost for completed requests. Snapshot invalidation: any change to the endpoint set or rates requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '0.45', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.049', 'per' => '1000000'],
                'output_tokens' => ['amount' => '1.8', 'per' => '1000000'],
            ],
        ],
        'openrouter:deepseek/deepseek-v4-pro-0813-standard-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/deepseek/deepseek-v4-pro-0813/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for deepseek/deepseek-v4-pro-0813 (canonical deepseek/deepseek-v4-pro-20260813) under OpenRouter default routing at the standard service tier; not :batch. Default routing spans 15 distinct price sets across 21 default-routed endpoints, quantizations fp4/fp8/unknown, context 1,000,000-1,048,576, time-of-day overrides, so the live catalog headline (input 0.66, output 1.98 per M) can understate a request. Each rate is the maximum over all default-routed endpoints, including every quantization and override: input_tokens 1.65 from venice; cached_input_tokens 0.219 from wafer; output_tokens 5 from wafer. Maxima can come from different endpoints, so the basis can exceed any single endpoint. Callers must not opt into service tiers (service_tier, :nitro, :floor or tier slugs). Reasoning is output usage; OpenRouter web search and other server tools are distinct, unpriced units. Prefer provider-reported usage.cost for completed requests. Snapshot invalidation: any change to the endpoint set or rates requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '1.65', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.219', 'per' => '1000000'],
                'output_tokens' => ['amount' => '5', 'per' => '1000000'],
            ],
        ],
        'openrouter:deepseek/deepseek-v4-pro-standard-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/deepseek/deepseek-v4-pro/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for deepseek/deepseek-v4-pro (canonical deepseek/deepseek-v4-pro-20260423) under OpenRouter default routing at the standard service tier; not :batch. Default routing spans 16 distinct price sets across 16 default-routed endpoints, quantizations fp4/fp8/unknown, context 1,000,000-1,048,576, so the live catalog headline (input 0.2088, output 0.4176 per M) can understate a request. Each rate is the maximum over all default-routed endpoints, including every quantization and override: input_tokens 1.91 from azure/us; cached_input_tokens 0.33 from venice; output_tokens 10.5 from reka. Maxima can come from different endpoints, so the basis can exceed any single endpoint. Callers must not opt into service tiers (service_tier, :nitro, :floor or tier slugs). Reasoning is output usage; OpenRouter web search and other server tools are distinct, unpriced units. Prefer provider-reported usage.cost for completed requests. Snapshot invalidation: any change to the endpoint set or rates requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '1.91', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.33', 'per' => '1000000'],
                'output_tokens' => ['amount' => '10.5', 'per' => '1000000'],
            ],
        ],
        'openrouter:deepseek/deepseek-v4-flash-standard-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/deepseek/deepseek-v4-flash/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for deepseek/deepseek-v4-flash (canonical deepseek/deepseek-v4-flash-20260423) under OpenRouter default routing at the standard service tier; not :batch. Default routing spans 15 distinct price sets across 16 default-routed endpoints, quantizations fp4/fp8/unknown, context 384,000-1,048,576, so the live catalog headline (input 0.0228, output 1.28 per M) can understate a request. Each rate is the maximum over all default-routed endpoints, including every quantization and override: input_tokens 0.44 from cloudflare; cached_input_tokens 0.07 from parasail/fp8; output_tokens 1.536 from open-inference/fp4. Maxima can come from different endpoints, so the basis can exceed any single endpoint. Callers must not opt into service tiers (service_tier, :nitro, :floor or tier slugs). Includes endpoints currently reporting a non-zero status (venice). Reasoning is output usage; OpenRouter web search and other server tools are distinct, unpriced units. Prefer provider-reported usage.cost for completed requests. Snapshot invalidation: any change to the endpoint set or rates requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '0.44', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.07', 'per' => '1000000'],
                'output_tokens' => ['amount' => '1.536', 'per' => '1000000'],
            ],
        ],
        'openrouter:deepseek/deepseek-v4-flash-0731-standard-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/deepseek/deepseek-v4-flash-0731/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for deepseek/deepseek-v4-flash-0731 (canonical deepseek/deepseek-v4-flash-20260731) under OpenRouter default routing at the standard service tier; not :batch. Default routing spans 22 distinct price sets across 26 default-routed endpoints, quantizations fp4/fp8/unknown, context 262,144-1,048,576, time-of-day overrides, so the live catalog headline (input 0.018, output 1.28 per M) can understate a request. Each rate is the maximum over all default-routed endpoints, including every quantization and override: input_tokens 0.44 from phala; cached_input_tokens 0.07 from coreweave/fp8; output_tokens 1.536 from open-inference/fp4. Maxima can come from different endpoints, so the basis can exceed any single endpoint. Opt-in service-tier endpoints (wafer/fast) are excluded; callers must not use service_tier, :nitro, :floor or tier slugs. Reasoning is output usage; OpenRouter web search and other server tools are distinct, unpriced units. Prefer provider-reported usage.cost for completed requests. Snapshot invalidation: any change to the endpoint set or rates requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '0.44', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.07', 'per' => '1000000'],
                'output_tokens' => ['amount' => '1.536', 'per' => '1000000'],
            ],
        ],
        'openrouter:deepseek/deepseek-v3.2-standard-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/deepseek/deepseek-v3.2/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for deepseek/deepseek-v3.2 (canonical deepseek/deepseek-v3.2-20251201) under OpenRouter default routing at the standard service tier; not :batch. Default routing spans 10 distinct price sets across 12 default-routed endpoints, quantizations fp4/fp8/unknown, context 32,768-163,840, so the live catalog headline (input 0.259, output 0.42 per M) can understate a request. Each rate is the maximum over all default-routed endpoints, including every quantization and override: input_tokens 3 from mara; cached_input_tokens 0.5 from phala; output_tokens 4.5 from mara. Maxima can come from different endpoints, so the basis can exceed any single endpoint. Callers must not opt into service tiers (service_tier, :nitro, :floor or tier slugs). Includes endpoints currently reporting a non-zero status (gmicloud/fp8, atlas-cloud/fp8). Reasoning is output usage; OpenRouter web search and other server tools are distinct, unpriced units. Prefer provider-reported usage.cost for completed requests. Snapshot invalidation: any change to the endpoint set or rates requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '3', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.5', 'per' => '1000000'],
                'output_tokens' => ['amount' => '4.5', 'per' => '1000000'],
            ],
        ],
        'openrouter:qwen/qwen3.7-plus-standard-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/qwen/qwen3.7-plus/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for qwen/qwen3.7-plus (canonical qwen/qwen3.7-plus-20260602) under OpenRouter default routing at the standard service tier; not :batch. Default routing spans prompt-length overrides, so the live catalog headline (input 0.32, output 1.28 per M) can understate a request. Each rate is the maximum over all default-routed endpoints, including every quantization and override: input_tokens 0.96 from alibaba/us-east-1; cached_input_tokens 0.192 from alibaba/us-east-1; cache_write_input_tokens 1.2 from alibaba/us-east-1; output_tokens 3.84 from alibaba/us-east-1. Maxima can come from different endpoints, so the basis can exceed any single endpoint. Callers must not opt into service tiers (service_tier, :nitro, :floor or tier slugs). Reasoning is output usage; OpenRouter web search and other server tools are distinct, unpriced units. Prefer provider-reported usage.cost for completed requests. Snapshot invalidation: any change to the endpoint set or rates requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '0.96', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.192', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '1.2', 'per' => '1000000'],
                'output_tokens' => ['amount' => '3.84', 'per' => '1000000'],
            ],
        ],
        'openrouter:qwen/qwen3.7-max-standard-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/qwen/qwen3.7-max/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for qwen/qwen3.7-max (canonical qwen/qwen3.7-max-20260520) under OpenRouter default routing at the standard service tier; not :batch. Default routing spans 2 distinct price sets across 2 default-routed endpoints, so the live catalog headline (input 1.475, output 4.425 per M) can understate a request. Each rate is the maximum over all default-routed endpoints, including every quantization and override: input_tokens 2 from alibaba/us-east-1; cached_input_tokens 0.4 from alibaba/us-east-1; cache_write_input_tokens 1.84375 from alibaba; output_tokens 6 from alibaba/us-east-1. Maxima can come from different endpoints, so the basis can exceed any single endpoint. Callers must not opt into service tiers (service_tier, :nitro, :floor or tier slugs). Reasoning is output usage; OpenRouter web search and other server tools are distinct, unpriced units. Prefer provider-reported usage.cost for completed requests. Snapshot invalidation: any change to the endpoint set or rates requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '2', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.4', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '1.84375', 'per' => '1000000'],
                'output_tokens' => ['amount' => '6', 'per' => '1000000'],
            ],
        ],
        'openrouter:qwen/qwen3.7-flash-standard-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/qwen/qwen3.7-flash/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for qwen/qwen3.7-flash (canonical qwen/qwen3.7-flash-20260727) under OpenRouter default routing at the standard service tier; not :batch. Default routing spans 2 distinct price sets across 2 default-routed endpoints, prompt-length overrides, so the live catalog headline (input 0.03, output 0.13 per M) can understate a request. Each rate is the maximum over all default-routed endpoints, including every quantization and override: input_tokens 0.23 from alibaba/us-east-1; cached_input_tokens 0.046 from alibaba/us-east-1; cache_write_input_tokens 0.25 from alibaba; output_tokens 0.92 from alibaba/us-east-1. Maxima can come from different endpoints, so the basis can exceed any single endpoint. Callers must not opt into service tiers (service_tier, :nitro, :floor or tier slugs). Reasoning is output usage; OpenRouter web search and other server tools are distinct, unpriced units. Prefer provider-reported usage.cost for completed requests. Snapshot invalidation: any change to the endpoint set or rates requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '0.23', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.046', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '0.25', 'per' => '1000000'],
                'output_tokens' => ['amount' => '0.92', 'per' => '1000000'],
            ],
        ],
        'openrouter:qwen/qwen3.8-2.4t-a95b-standard-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/qwen/qwen3.8-2.4t-a95b/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for qwen/qwen3.8-2.4t-a95b (canonical qwen/qwen3.8-2.4t-a95b-20260812) under OpenRouter default routing at the standard service tier; not :batch. Default routing spans 3 distinct price sets across 7 default-routed endpoints, quantizations fp4/fp8/unknown, context 262,144-1,048,576, so the live catalog headline (input 2, output 6 per M) can understate a request. Each rate is the maximum over all default-routed endpoints, including every quantization and override: input_tokens 2 from novita; cached_input_tokens 0.25 from novita; cache_write_input_tokens 2.5 from alibaba; output_tokens 6 from novita. Maxima can come from different endpoints, so the basis can exceed any single endpoint. Callers must not opt into service tiers (service_tier, :nitro, :floor or tier slugs). Reasoning is output usage; OpenRouter web search and other server tools are distinct, unpriced units. Prefer provider-reported usage.cost for completed requests. Snapshot invalidation: any change to the endpoint set or rates requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '2', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.25', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '2.5', 'per' => '1000000'],
                'output_tokens' => ['amount' => '6', 'per' => '1000000'],
            ],
        ],
        'openrouter:qwen/qwen3.8-27b-standard-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/qwen/qwen3.8-27b/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for qwen/qwen3.8-27b (canonical qwen/qwen3.8-27b-20260814) under OpenRouter default routing at the standard service tier; not :batch. Default routing spans 18 distinct price sets across 19 default-routed endpoints, quantizations bf16/fp16/fp4/fp8/unknown, context 65,536-1,000,000, so the live catalog headline (input 0.425, output 2.55 per M) can understate a request. Each rate is the maximum over all default-routed endpoints, including every quantization and override: input_tokens 0.99 from cerebras/fp16; cached_input_tokens 0.99 from cerebras/fp16; cache_write_input_tokens 0.53125 from alibaba; output_tokens 4.7 from modelrun/fp4. Maxima can come from different endpoints, so the basis can exceed any single endpoint. Callers must not opt into service tiers (service_tier, :nitro, :floor or tier slugs). Includes endpoints currently reporting a non-zero status (alibaba, cloudflare). Reasoning is output usage; OpenRouter web search and other server tools are distinct, unpriced units. Prefer provider-reported usage.cost for completed requests. Snapshot invalidation: any change to the endpoint set or rates requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '0.99', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.99', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '0.53125', 'per' => '1000000'],
                'output_tokens' => ['amount' => '4.7', 'per' => '1000000'],
            ],
        ],
        'openrouter:qwen/qwen3-coder-flash-standard-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/qwen/qwen3-coder-flash/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for qwen/qwen3-coder-flash (canonical qwen/qwen3-coder-flash) under OpenRouter default routing at the standard service tier; not :batch. Default routing spans prompt-length overrides, so the live catalog headline (input 0.195, output 0.975 per M) can understate a request. Each rate is the maximum over all default-routed endpoints, including every quantization and override: input_tokens 0.52 from alibaba; cached_input_tokens 0.104 from alibaba; cache_write_input_tokens 0.65 from alibaba; output_tokens 2.6 from alibaba. Maxima can come from different endpoints, so the basis can exceed any single endpoint. Callers must not opt into service tiers (service_tier, :nitro, :floor or tier slugs). Reasoning is output usage; OpenRouter web search and other server tools are distinct, unpriced units. Prefer provider-reported usage.cost for completed requests. Snapshot invalidation: any change to the endpoint set or rates requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '0.52', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.104', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '0.65', 'per' => '1000000'],
                'output_tokens' => ['amount' => '2.6', 'per' => '1000000'],
            ],
        ],
        'openrouter:qwen/qwen3-coder-standard-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/qwen/qwen3-coder/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for qwen/qwen3-coder (canonical qwen/qwen3-coder-480b-a35b-07-25) under OpenRouter default routing at the standard service tier; not :batch. Default routing spans 3 distinct price sets across 3 default-routed endpoints, quantizations fp4/fp8/unknown, context 256,000-262,144, so the live catalog headline (input 0.3, output 1 per M) can understate a request. Each rate is the maximum over all default-routed endpoints, including every quantization and override: input_tokens 0.35 from venice/fp8; cached_input_tokens 0.1 from deepinfra/turbo; output_tokens 1.8 from google-vertex/us-south1. Maxima can come from different endpoints, so the basis can exceed any single endpoint. Callers must not opt into service tiers (service_tier, :nitro, :floor or tier slugs). Includes endpoints currently reporting a non-zero status (deepinfra/turbo, venice/fp8). Reasoning is output usage; OpenRouter web search and other server tools are distinct, unpriced units. Prefer provider-reported usage.cost for completed requests. Snapshot invalidation: any change to the endpoint set or rates requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '0.35', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.1', 'per' => '1000000'],
                'output_tokens' => ['amount' => '1.8', 'per' => '1000000'],
            ],
        ],
        'openrouter:qwen/qwen-plus-standard-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/qwen/qwen-plus/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for qwen/qwen-plus (canonical qwen/qwen-plus-2025-01-25) under OpenRouter default routing at the standard service tier; not :batch. Default routing spans prompt-length overrides, so the live catalog headline (input 0.26, output 0.78 per M) can understate a request. Each rate is the maximum over all default-routed endpoints, including every quantization and override: input_tokens 0.78 from alibaba; cached_input_tokens 0.156 from alibaba; cache_write_input_tokens 0.975 from alibaba; output_tokens 2.34 from alibaba. Maxima can come from different endpoints, so the basis can exceed any single endpoint. Callers must not opt into service tiers (service_tier, :nitro, :floor or tier slugs). Reasoning is output usage; OpenRouter web search and other server tools are distinct, unpriced units. Prefer provider-reported usage.cost for completed requests. Snapshot invalidation: any change to the endpoint set or rates requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '0.78', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.156', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '0.975', 'per' => '1000000'],
                'output_tokens' => ['amount' => '2.34', 'per' => '1000000'],
            ],
        ],
        'openrouter:moonshotai/kimi-k3-standard-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/moonshotai/kimi-k3/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for moonshotai/kimi-k3 (canonical moonshotai/kimi-k3-20260715) under OpenRouter default routing at the standard service tier; not :batch. Default routing spans 17 distinct price sets across 22 default-routed endpoints, quantizations fp4/fp8/mxfp4/unknown, so the live catalog headline (input 0.8, output 13.5 per M) can understate a request. Each rate is the maximum over all default-routed endpoints, including every quantization and override: input_tokens 4.5 from fireworks/us; cached_input_tokens 1.2 from akashml/fp4; cache_write_input_tokens 3.75 from amazon-bedrock/us-east-2; output_tokens 22.5 from fireworks/us. Maxima can come from different endpoints, so the basis can exceed any single endpoint. Opt-in service-tier endpoints (fireworks/fast, inference-net/fast, parasail/fast) are excluded; callers must not use service_tier, :nitro, :floor or tier slugs. Reasoning is output usage; OpenRouter web search and other server tools are distinct, unpriced units. Prefer provider-reported usage.cost for completed requests. Snapshot invalidation: any change to the endpoint set or rates requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '4.5', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '1.2', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '3.75', 'per' => '1000000'],
                'output_tokens' => ['amount' => '22.5', 'per' => '1000000'],
            ],
        ],
        'openrouter:moonshotai/kimi-k2.7-code-standard-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/moonshotai/kimi-k2.7-code/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for moonshotai/kimi-k2.7-code (canonical moonshotai/kimi-k2.7-code-20260612) under OpenRouter default routing at the standard service tier; not :batch. Default routing spans 9 distinct price sets across 12 default-routed endpoints, quantizations fp4/fp8/int4/unknown, context 256,000-262,144, so the live catalog headline (input 0.6712, output 3.35 per M) can understate a request. Each rate is the maximum over all default-routed endpoints, including every quantization and override: input_tokens 1.9 from moonshotai/highspeed; cached_input_tokens 0.38 from moonshotai/highspeed; output_tokens 8 from moonshotai/highspeed. Maxima can come from different endpoints, so the basis can exceed any single endpoint. Callers must not opt into service tiers (service_tier, :nitro, :floor or tier slugs). Reasoning is output usage; OpenRouter web search and other server tools are distinct, unpriced units. Prefer provider-reported usage.cost for completed requests. Snapshot invalidation: any change to the endpoint set or rates requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '1.9', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.38', 'per' => '1000000'],
                'output_tokens' => ['amount' => '8', 'per' => '1000000'],
            ],
        ],
        'openrouter:moonshotai/kimi-k2.6-standard-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/moonshotai/kimi-k2.6/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for moonshotai/kimi-k2.6 (canonical moonshotai/kimi-k2.6-20260420) under OpenRouter default routing at the standard service tier; not :batch. Default routing spans 13 distinct price sets across 17 default-routed endpoints, quantizations bf16/fp4/fp8/int4/unknown, context 256,000-262,144, so the live catalog headline (input 0.465, output 2.45 per M) can understate a request. Each rate is the maximum over all default-routed endpoints, including every quantization and override: input_tokens 1.09 from phala; cached_input_tokens 0.37 from phala; output_tokens 4.6 from phala. Maxima can come from different endpoints, so the basis can exceed any single endpoint. Callers must not opt into service tiers (service_tier, :nitro, :floor or tier slugs). Reasoning is output usage; OpenRouter web search and other server tools are distinct, unpriced units. Prefer provider-reported usage.cost for completed requests. Snapshot invalidation: any change to the endpoint set or rates requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '1.09', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.37', 'per' => '1000000'],
                'output_tokens' => ['amount' => '4.6', 'per' => '1000000'],
            ],
        ],
        'openrouter:z-ai/glm-5.3-standard-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/z-ai/glm-5.3/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for z-ai/glm-5.3 (canonical z-ai/glm-5.3-20260816) under OpenRouter default routing at the standard service tier; not :batch. Default routing spans 23 distinct price sets across 37 default-routed endpoints, quantizations fp4/fp8/nvfp4/unknown, context 262,124-1,048,576, so the live catalog headline (input 0.039, output 6 per M) can understate a request. Each rate is the maximum over all default-routed endpoints, including every quantization and override: input_tokens 1.54 from mistral/nvfp4; cached_input_tokens 0.26 from io-net/fp8; output_tokens 6 from wafer/us. Maxima can come from different endpoints, so the basis can exceed any single endpoint. Opt-in service-tier endpoints (alibaba/fast, baseten/fast, fireworks/fast) are excluded; callers must not use service_tier, :nitro, :floor or tier slugs. Reasoning is output usage; OpenRouter web search and other server tools are distinct, unpriced units. Prefer provider-reported usage.cost for completed requests. Snapshot invalidation: any change to the endpoint set or rates requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '1.54', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.26', 'per' => '1000000'],
                'output_tokens' => ['amount' => '6', 'per' => '1000000'],
            ],
        ],
        'openrouter:z-ai/glm-5.3-flash-standard-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/z-ai/glm-5.3-flash/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for z-ai/glm-5.3-flash (canonical z-ai/glm-5.3-flash-20260826) under OpenRouter default routing at the standard service tier; not :batch. Default routing spans 19 distinct price sets across 32 default-routed endpoints, quantizations fp4/fp8/nvfp4/unknown, context 262,144-1,048,576, so the live catalog headline (input 0.15, output 0.5 per M) can understate a request. Each rate is the maximum over all default-routed endpoints, including every quantization and override: input_tokens 0.225 from fireworks/us; cached_input_tokens 0.099 from inceptron/fp8; output_tokens 1.6 from reka. Maxima can come from different endpoints, so the basis can exceed any single endpoint. Opt-in service-tier endpoints (parasail/fast) are excluded; callers must not use service_tier, :nitro, :floor or tier slugs. Reasoning is output usage; OpenRouter web search and other server tools are distinct, unpriced units. Prefer provider-reported usage.cost for completed requests. Snapshot invalidation: any change to the endpoint set or rates requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '0.225', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.099', 'per' => '1000000'],
                'output_tokens' => ['amount' => '1.6', 'per' => '1000000'],
            ],
        ],
        'openrouter:z-ai/glm-5.2-standard-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/z-ai/glm-5.2/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for z-ai/glm-5.2 (canonical z-ai/glm-5.2-20260616) under OpenRouter default routing at the standard service tier; not :batch. Default routing spans 21 distinct price sets across 29 default-routed endpoints, quantizations fp4/fp8/mxfp4/nvfp4/unknown, context 262,144-1,048,576, so the live catalog headline (input 0.06, output 7 per M) can understate a request. Each rate is the maximum over all default-routed endpoints, including every quantization and override: input_tokens 1.54 from mistral/eu; cached_input_tokens 0.26 from cloudflare; output_tokens 10 from wafer. Maxima can come from different endpoints, so the basis can exceed any single endpoint. Opt-in service-tier endpoints (alibaba/fast, baidu/fast, baseten/fast, decart/fast) are excluded; callers must not use service_tier, :nitro, :floor or tier slugs. Includes endpoints currently reporting a non-zero status (siliconflow/fp8). Reasoning is output usage; OpenRouter web search and other server tools are distinct, unpriced units. Prefer provider-reported usage.cost for completed requests. Snapshot invalidation: any change to the endpoint set or rates requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '1.54', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.26', 'per' => '1000000'],
                'output_tokens' => ['amount' => '10', 'per' => '1000000'],
            ],
        ],
        'openrouter:z-ai/glm-5.1-standard-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/z-ai/glm-5.1/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for z-ai/glm-5.1 (canonical z-ai/glm-5.1-20260406) under OpenRouter default routing at the standard service tier; not :batch. Default routing spans 10 distinct price sets across 13 default-routed endpoints, quantizations fp8/unknown, context 200,000-204,800, so the live catalog headline (input 0.966, output 3.036 per M) can understate a request. Each rate is the maximum over all default-routed endpoints, including every quantization and override: input_tokens 1.4014 from venice/fp8; cached_input_tokens 0.6 from siliconflow/fp8; output_tokens 4.4044 from venice/fp8. Maxima can come from different endpoints, so the basis can exceed any single endpoint. Callers must not opt into service tiers (service_tier, :nitro, :floor or tier slugs). Reasoning is output usage; OpenRouter web search and other server tools are distinct, unpriced units. Prefer provider-reported usage.cost for completed requests. Snapshot invalidation: any change to the endpoint set or rates requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '1.4014', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.6', 'per' => '1000000'],
                'output_tokens' => ['amount' => '4.4044', 'per' => '1000000'],
            ],
        ],
        'openrouter:z-ai/glm-5-standard-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/z-ai/glm-5/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for z-ai/glm-5 (canonical z-ai/glm-5-20260211) under OpenRouter default routing at the standard service tier; not :batch. Default routing spans 5 distinct price sets across 8 default-routed endpoints, quantizations fp8/unknown, context 198,000-204,800, so the live catalog headline (input 0.6, output 1.92 per M) can understate a request. Each rate is the maximum over all default-routed endpoints, including every quantization and override: input_tokens 1 from amazon-bedrock; cached_input_tokens 0.2 from siliconflow/fp8; output_tokens 3.2 from amazon-bedrock. Maxima can come from different endpoints, so the basis can exceed any single endpoint. Callers must not opt into service tiers (service_tier, :nitro, :floor or tier slugs). Reasoning is output usage; OpenRouter web search and other server tools are distinct, unpriced units. Prefer provider-reported usage.cost for completed requests. Snapshot invalidation: any change to the endpoint set or rates requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '1', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.2', 'per' => '1000000'],
                'output_tokens' => ['amount' => '3.2', 'per' => '1000000'],
            ],
        ],
        'openrouter:z-ai/glm-4.7-standard-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/z-ai/glm-4.7/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for z-ai/glm-4.7 (canonical z-ai/glm-4.7-20251222) under OpenRouter default routing at the standard service tier; not :batch. Default routing spans 6 distinct price sets across 6 default-routed endpoints, quantizations fp4/fp8/unknown, context 131,072-204,800, so the live catalog headline (input 0.6, output 2.2 per M) can understate a request. Each rate is the maximum over all default-routed endpoints, including every quantization and override: input_tokens 0.7 from mancer/fp4; cached_input_tokens 0.11 from z-ai/fp4; output_tokens 2.5 from mancer/fp4. Maxima can come from different endpoints, so the basis can exceed any single endpoint. Callers must not opt into service tiers (service_tier, :nitro, :floor or tier slugs). Includes endpoints currently reporting a non-zero status (deepinfra/fp4, venice/fp4). Reasoning is output usage; OpenRouter web search and other server tools are distinct, unpriced units. Prefer provider-reported usage.cost for completed requests. Snapshot invalidation: any change to the endpoint set or rates requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '0.7', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.11', 'per' => '1000000'],
                'output_tokens' => ['amount' => '2.5', 'per' => '1000000'],
            ],
        ],
        'openrouter:z-ai/glm-4.6-standard-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/z-ai/glm-4.6/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for z-ai/glm-4.6 (canonical z-ai/glm-4.6) under OpenRouter default routing at the standard service tier; not :batch. Default routing spans 4 distinct price sets across 4 default-routed endpoints, quantizations bf16/fp4, context 198,000-204,800, so the live catalog headline (input 0.5, output 2 per M) can understate a request. Each rate is the maximum over all default-routed endpoints, including every quantization and override: input_tokens 0.6 from z-ai/fp4; cached_input_tokens 0.11 from novita/bf16; output_tokens 2.2 from novita/bf16. Maxima can come from different endpoints, so the basis can exceed any single endpoint. Callers must not opt into service tiers (service_tier, :nitro, :floor or tier slugs). Reasoning is output usage; OpenRouter web search and other server tools are distinct, unpriced units. Prefer provider-reported usage.cost for completed requests. Snapshot invalidation: any change to the endpoint set or rates requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '0.6', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.11', 'per' => '1000000'],
                'output_tokens' => ['amount' => '2.2', 'per' => '1000000'],
            ],
        ],
        'openrouter:z-ai/glm-4.5-air-standard-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/z-ai/glm-4.5-air/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for z-ai/glm-4.5-air (canonical z-ai/glm-4.5-air) under OpenRouter default routing at the standard service tier; not :batch. Default routing spans 3 distinct price sets across 3 default-routed endpoints, quantizations bf16/fp8, so the live catalog headline (input 0.13, output 0.85 per M) can understate a request. Each rate is the maximum over all default-routed endpoints, including every quantization and override: input_tokens 0.2 from z-ai/fp8; cached_input_tokens 0.03 from z-ai/fp8; output_tokens 1.1 from z-ai/fp8. Maxima can come from different endpoints, so the basis can exceed any single endpoint. Callers must not opt into service tiers (service_tier, :nitro, :floor or tier slugs). Reasoning is output usage; OpenRouter web search and other server tools are distinct, unpriced units. Prefer provider-reported usage.cost for completed requests. Snapshot invalidation: any change to the endpoint set or rates requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '0.2', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.03', 'per' => '1000000'],
                'output_tokens' => ['amount' => '1.1', 'per' => '1000000'],
            ],
        ],
```

## 3. Proposed `fallback_blocked` and `aliases` changes

```php
    'fallback_blocked' => [
        // ... existing entries ...
        // Checked 2026-10-10 against https://api-docs.deepseek.com/quick_start/pricing
        // and https://api-docs.deepseek.com/updates. deepseek-flash runs V4.1-Flash
        // and is time-tiered like Pro; price it only through a -peak or -off-peak basis.
        'deepseek:deepseek-flash',
        // Checked 2026-10-10 against https://www.alibabacloud.com/help/en/model-studio/model-pricing.
        // Model Studio prices the same model ID differently by region and deployment
        // scope (Singapore International, Global, US, EU, mainland China), most Qwen
        // models are tiered by input tokens per request, and qwen-plus also prices
        // output by thinking mode. Portkey's dashscope catalog carries a flat short-tier
        // price, so generic and dated IDs must stay unavailable; use the -intl bases.
        'dashscope:qwen3.8-max',
        'dashscope:qwen3.8-max-0902',
        'dashscope:qwen3.8-flash',
        'dashscope:qwen3.7-max',
        'dashscope:qwen3.7-max-2026-05-20',
        'dashscope:qwen3.7-max-2026-06-08',
        'dashscope:qwen3.7-plus',
        'dashscope:qwen3.7-plus-2026-05-26',
        'dashscope:qwen3.7-flash',
        'dashscope:qwen3.7-flash-2026-07-15',
        'dashscope:qwen3-max',
        'dashscope:qwen3-max-2026-01-23',
        'dashscope:qwen3-max-2025-09-23',
        'dashscope:qwen-plus',
        'dashscope:qwen-plus-latest',
        'dashscope:qwen-plus-2025-12-01',
        'dashscope:qwen-plus-2025-09-11',
        'dashscope:qwen-plus-2025-07-28',
        'dashscope:qwen-flash',
        'dashscope:qwen-flash-2025-07-28',
        'dashscope:qwen3-coder-plus',
        'dashscope:qwen3-coder-plus-2025-09-23',
        'dashscope:qwen3-coder-plus-2025-07-22',
        'dashscope:qwen3-coder-flash',
        'dashscope:qwen3-coder-flash-2025-07-28',
        // Checked 2026-10-10 against https://openrouter.ai/api/v1/models/{id}/endpoints.
        // Default routing for these IDs spans endpoints with different prices,
        // quantizations, context lengths or time-of-day/prompt-length overrides, and
        // the live catalog prices only the /models headline. Use the -standard-max bases.
        'openrouter:deepseek/deepseek-v4.1-flash',
        'openrouter:deepseek/deepseek-v4-pro-0813',
        'openrouter:deepseek/deepseek-v4-pro',
        'openrouter:deepseek/deepseek-v4-flash',
        'openrouter:deepseek/deepseek-v4-flash-0731',
        'openrouter:deepseek/deepseek-v3.2',
        'openrouter:qwen/qwen3.7-plus',
        'openrouter:qwen/qwen3.7-max',
        'openrouter:qwen/qwen3.7-flash',
        'openrouter:qwen/qwen3.8-2.4t-a95b',
        'openrouter:qwen/qwen3.8-27b',
        'openrouter:qwen/qwen3-coder-flash',
        'openrouter:qwen/qwen3-coder',
        'openrouter:qwen/qwen-plus',
        'openrouter:moonshotai/kimi-k3',
        'openrouter:moonshotai/kimi-k2.7-code',
        'openrouter:moonshotai/kimi-k2.6',
        'openrouter:z-ai/glm-5.3',
        'openrouter:z-ai/glm-5.3-flash',
        'openrouter:z-ai/glm-5.2',
        'openrouter:z-ai/glm-5.1',
        'openrouter:z-ai/glm-5',
        'openrouter:z-ai/glm-4.7',
        'openrouter:z-ai/glm-4.6',
        'openrouter:z-ai/glm-4.5-air',
        // Bound bases: deliberately also listed in prices (add to the invariant allowlist).
        'openrouter:deepseek/deepseek-v4.1-flash-standard-max',
        'openrouter:deepseek/deepseek-v4-pro-0813-standard-max',
        'openrouter:deepseek/deepseek-v4-pro-standard-max',
        'openrouter:deepseek/deepseek-v4-flash-standard-max',
        'openrouter:deepseek/deepseek-v4-flash-0731-standard-max',
        'openrouter:deepseek/deepseek-v3.2-standard-max',
        'openrouter:qwen/qwen3.7-plus-standard-max',
        'openrouter:qwen/qwen3.7-max-standard-max',
        'openrouter:qwen/qwen3.7-flash-standard-max',
        'openrouter:qwen/qwen3.8-2.4t-a95b-standard-max',
        'openrouter:qwen/qwen3.8-27b-standard-max',
        'openrouter:qwen/qwen3-coder-flash-standard-max',
        'openrouter:qwen/qwen3-coder-standard-max',
        'openrouter:qwen/qwen-plus-standard-max',
        'openrouter:moonshotai/kimi-k3-standard-max',
        'openrouter:moonshotai/kimi-k2.7-code-standard-max',
        'openrouter:moonshotai/kimi-k2.6-standard-max',
        'openrouter:z-ai/glm-5.3-standard-max',
        'openrouter:z-ai/glm-5.3-flash-standard-max',
        'openrouter:z-ai/glm-5.2-standard-max',
        'openrouter:z-ai/glm-5.1-standard-max',
        'openrouter:z-ai/glm-5-standard-max',
        'openrouter:z-ai/glm-4.7-standard-max',
        'openrouter:z-ai/glm-4.6-standard-max',
        'openrouter:z-ai/glm-4.5-air-standard-max',
    ],
```

The bound bases share the `-standard-max` suffix. Every dated Qwen snapshot listed above bills at the same rates as its family's `-intl` bases, so callers can use those bases for them.

Optional blocks for the writer to decide:

- `deepseek:deepseek-chat` and `deepseek:deepseek-reasoner` were discontinued on 2026-07-24 (DeepSeek changelog, 2026-04-24 entry), so requests to them fail. Portkey's `deepseek.json` still lists them at legacy prices. The risk is low; block them only for hygiene.
- No blocks are needed for `moonshot:` (one international price per model, no tiers) or for the new `zai:` entries (flat international prices).

**Aliases: none proposed.** I considered and rejected:

- the Qwen "Currently equivalent to …" mappings, such as `qwen3.7-plus` → `qwen3.7-plus-2026-05-26`, because the targets are tiered and region-dependent and an alias must resolve to one price and must not target a blocked identity;
- `deepseek:deepseek-v4-flash` → `deepseek-flash`, because the route is "temporary" per DeepSeek and the price is time-tiered;
- the `kimi-k2.7-code-highspeed` family, because it is a separate model ID with its own price.

## 4. Drift in existing DeepSeek and Z.ai entries

| Entry | Snapshot v11 | Source 2026-10-10 | Drift |
| --- | --- | --- | --- |
| `deepseek:deepseek-v4-pro-peak` | 1.32 / 0.044 / 3.96, peak 01–04 and 06–10 UTC weekdays, runs V4-Pro-0813 | Same rates and schedule; model version DeepSeek-V4-Pro-0813 | **None.** OpenRouter's `deepseek` endpoint overrides independently encode the same schedule. The changelog adds that V4 Pro service continues "after September 14, 2026, with the billing method remaining unchanged". |
| DeepSeek `fallback_blocked` comment | "No generic V4 SKU … Pro is time-tiered; retired Flash names now run V4.1-Flash." | Same facts. The current Flash model name is now **`deepseek-flash`** (DeepSeek-V4.1-Flash). Legacy `deepseek-v4-flash` and `deepseek-v4-flash-vision-exp` are "temporarily routed to V4.1 Flash" and "billed at the Flash price". | **Incomplete, not wrong.** The new canonical `deepseek-flash` is neither priced nor blocked; add `deepseek-flash-peak` and block `deepseek:deepseek-flash`. Update the README's blocked-identity list and billing-basis table to match. |
| `zai:glm-5.3` | 1.4 / 0.26 / 4.4; storage limited-time free | 1.4 / 0.26 / 4.4; "Limited-time Free" | **None.** |
| `zai:glm-5.3-flash` | 0.15 / 0.03 / 0.50 | 0.15 / 0.03 / 0.50 | **None.** |
| Portkey `deepseek.json` (context only) | n/a | Lists `deepseek-v4-pro` at 0.435/0.87, which is stale | No snapshot change. This confirms the existing blocks are needed. |

## 5. Excluded models and products

**DeepSeek**

- `deepseek-chat` and `deepseek-reasoner`: discontinued 2026-07-24.
- `deepseek-v4-flash` and `deepseek-v4-flash-vision-exp`: retired models whose names are temporarily routed to V4.1-Flash; kept blocked.
- V3.x, R1 and V3.2-Speciale: no longer served by the DeepSeek API.
- OpenRouter `deepseek/deepseek-chat`, `-chat-v3-0324`, `-chat-v3.1`, `-r1`, `-r1-0528`, `-v3.1-terminus`, `-v3.2-exp` and `-v4-flash-vision-exp`: older or experimental third-party-hosted generations. `deepseek/deepseek-v3.2` stays in scope as a widely used older model.
- `deepseek/deepseek-v4.1-flash:batch`: batch.
- FIM and chat prefix completion are beta features of the same models at the same rates, so no separate SKU is needed.

**Qwen (Model Studio)**

- All non-Singapore regions and non-International deployment scopes: region out of scope.
- The limited-time 20% discount on `qwen3.7-plus`: promotional.
- The 90-day free quota: promotional allowance, not subtracted.
- Batch (50%): batch.
- `qwen3.8-max-prime`: fast mode, Beijing only.
- `qwen3.7-max-preview`, `qwen3.7-max-2026-05-17` and `qwen3.6-max-preview`: preview.
- `qwen3.6-plus`, `qwen3.5-plus`, `qwen3.6-flash` and `qwen3.5-flash`: superseded generations, still listed.
- `qwen-max`: legacy, non-thinking only.
- `qwen-turbo`: "will no longer be updated".
- `qwq-plus`: legacy reasoning model.
- Older flat `qwen-plus-2025-07-14`, `-04-28` and `-01-25` snapshots.
- `qwen-long` and Qwen Math, Translation, Data Mining and Deep Research: Beijing-only or specialized.
- Open-weight models served on Model Studio (`qwen3.8-2.4t-a95b` at the same 2/6 as qwen3.8-max, `qwen3.8-27b`, and the Qwen3.6, 3.5 and 3 open-weight sizes, `qwen3-coder-next`, `-480b-a35b-instruct` and `-30b-a3b-instruct`): mostly consumed through third-party hosts and covered on OpenRouter. They can be added later from the same table.
- Omni, Omni-Realtime, VL, OCR and QVQ: multimodal, vision or audio.
- LiveTranslate, ASR, Fun-ASR, Paraformer and Voice Chat: audio.
- `text-embedding-v4/v3`, multimodal embeddings and rerank: embeddings.
- `qwen-plus-character` and similar, `decision-model-preview`: niche or free preview.
- Image and video generation.
- Third-party models on Model Studio (DeepSeek, Kimi, GLM, MiniMax): Alibaba's prices differ from the labs' own APIs.
- Context-cache storage: not billed.

**Z.ai**

- GLM-4.5, GLM-4.5-X, GLM-4.5-AirX, GLM-4.7-FlashX and GLM-4-32B-0414-128K: older or niche variants.
- GLM-4.7-Flash and GLM-4.5-Flash: free tier. A zero rate is a published but changeable policy, so I did not add it.
- GLM-4.6V, -4.6V-FlashX, -4.6V-Flash, GLM-4.5V, GLM-OCR and AutoGLM-Phone: vision.
- GLM-Image, CogView-4, CogVideoX-3 and GLM-ASR-2512: image, video and audio.
- Agents (Slide/Poster, Translation, Effects).
- The GLM Coding Plan: subscription, not pay-as-you-go.
- Cached-input storage: limited-time free and deliberately unpriced.
- The Web Search built-in tool at USD 0.01/use: see U7.
- OpenRouter `z-ai/glm-5.3-prime`: premium fast variant, not on Z.ai's pay-as-you-go page.
- OpenRouter `z-ai/glm-5-turbo` and `z-ai/glm-5v-turbo`: not on Z.ai's international pricing page.
- OpenRouter `z-ai/glm-4.5`, `z-ai/glm-4.5v`, `z-ai/glm-4.6v` and `z-ai/glm-4.7-flash`: older, vision or free.
- OpenRouter `:batch` variants.

**Kimi**

- `kimi-k2.5`, the `moonshot-v1` series, the `kimi-k2` series including `kimi-k2-thinking(-turbo)`, `kimi-latest` and `kimi-thinking-preview`: discontinued between 2025-11-11 and 2026-08-31.
- The same IDs on OpenRouter (`moonshotai/kimi-k2`, `-k2-0905`, `-k2-thinking`, `-k2.5`).
- Batch (`moonshotai/kimi-k3:batch`, `/docs/pricing/batch`).
- Web Search Basic, Web Search Pro and URL Fetch: standalone REST tools at USD 0.002, 0.003 and 0.002 per successful call, not model SKUs.
- Legacy `$web_search` at USD 0.005/call: deprecating 2026-10-20.
- File APIs: "temporarily free".

**OpenRouter, general**

- Opt-in service-tier endpoints (`/fast` and others) and the `:nitro` and `:floor` variants.
- Web search plugins and other server tools: none of these models has a native search rate in the catalog.

## 6. Judgment calls, conflicts and uncertainties

### Judgment calls the writer must accept or reverse

- **J1. Qwen cache-hit rate = 20% (implicit), applied to all cache hits.** Implicit hits bill at 20% and explicit hits at 10%, but both arrive as the same `cached_tokens` count, and the package has one `cached_input_tokens` unit. 20% is the documented upper bound, so explicit hits are overstated by 2×. This follows the conservative-reservation precedent of `deepseek-v4-pro-peak`. Alternatives:
  - leave `cached_input_tokens` unpriced (quotes go partial whenever implicit caching hits, which is common);
  - add `-explicit` bases at 10%.
- **J2. Qwen generic identities are fallback-blocked even when flat** (qwen3.8-max, qwen3.8-flash, qwen3.7-max). The same model ID costs 2/6 under Singapore International but 1.65/4.951 under Global scope. CONTRIBUTING requires geography-dependent prices to be qualified bases.
- **J3. Qwen tier naming uses upper bounds** (`-32k`, `-128k`, `-256k`, `-1m`) instead of `-short`/`-long`, because three families have 3–4 tiers. `qwen-plus` also needs `-thinking`/`-nonthinking`, because its output rate differs by mode (1.2 vs 4 at ≤256K).
- **J4. OpenRouter bound bases are worst-case composites.** Maxima can come from different endpoints, and some outliers are large:
  - `deepseek/deepseek-v3.2`: 3/4.5 from 32K-context `mara`/`sambanova`, against a 0.259/0.42 headline;
  - `deepseek/deepseek-v4-pro` (April weights): 1.91/10.5;
  - `z-ai/glm-5.2`: output 10 from `wafer`.

  Endpoint sets change often (V4.1-Flash has 31 endpoints), so these bases go stale fastest. Because OpenRouter returns `usage.cost` for completed requests, bases matter only for pre-flight quotes. **If maintenance cost is a concern, ship the 25 fallback blocks and drop the bound bases.** The blocks alone are the safety-critical part.
- **J5. `moonshotai/highspeed` is treated as default-routed.** It is not a documented service-tier suffix, so `openrouter:moonshotai/kimi-k2.7-code-standard-max` is 1.9/0.38/8 (the HighSpeed price). If OpenRouter treats it as opt-in, the bound would be 0.95/0.19/4.
- **J6. Prefixes `moonshot` and `dashscope`** (see the prefix table). With `dashscope`, Portkey fallback is active for any Qwen ID that is not blocked.
- **J7. Model selection for Qwen.** I included:
  - the current Max/Plus/Flash (3.8-max, 3.7-plus, 3.8-flash);
  - the previous Max/Flash (3.7-max, 3.7-flash), which are still listed and on OpenRouter;
  - the coders (qwen3-coder-plus, qwen3-coder-flash);
  - the widely used moving IDs `qwen3-max`, `qwen-plus` and `qwen-flash`.

  Model Studio lists no `qwen3.8-plus`.

### Conflicts and uncertainties

- **U1. Cache writes are lost through laravel/ai `openai-compatible`.** `OpenAiCompatible\Concerns\ParsesTextResponses::extractUsage` reads only `prompt_tokens`, `cached_tokens` and `reasoning_tokens`. Because `openai-compatible` is an inclusive driver, the package bills cache-write tokens as uncached input:
  - Kimi K3 5-minute writes: 3.00 = 3.00, coincidentally correct;
  - Kimi K3 1-hour writes: understated by 3.00/M;
  - Qwen explicit writes: understated by 25%.

  Callers who pass raw usage, or the Kimi Messages API, which has an Anthropic-shaped TTL split, are unaffected. The writer should document this or extend the adapter. The entries are correct either way.
- **U2. Qwen 3.8 cache-hit rates are unpublished.** context-cache says the rate for `qwen3.8-max`, `-max-0902`, `qwen3.8-flash` and `qwen3.8-2.4t-a95b` "is not 20%… see the Model Studio console", so they are left unpriced. Not used: OpenRouter's Alibaba endpoint shows 0.25/M for qwen3.8-max (12.5%) and 0.016/M for qwen3.8-flash, but that is not a primary source.
- **U3. Qwen "Global" scope is cheaper than "International".** Global costs 1.65/4.951 for qwen3.8-max; it is listed under Hong Kong, Frankfurt, Virginia and Tokyo, but not Singapore. Only the Singapore International table was used. A caller in another region needs different, unreviewed bases.
- **U4. Qwen promotional pricing.** The `qwen3.7-plus` generic alias shows "List price $0.4 (Limited-time 20% off)", while the dated snapshot row shows 0.4 with no promotion. The list price was used. OpenRouter's Alibaba endpoint bills the discounted 0.32/1.28.
- **U5. DeepSeek off-peak bases.** DeepSeek does not say which timestamp (request start or completion) decides peak versus off-peak, and it does not publish the Chinese public-holiday calendar on the pricing page. That is why off-peak is optional and peak remains the conservative reservation.
- **U6. DeepSeek legacy Flash routing is "temporary".** When DeepSeek stops routing `deepseek-v4-flash` names, nothing in the snapshot changes, because those names are blocked, not aliased. V4.1-Flash has native vision. The pricing page lists only token rates, and I did not verify how image input is counted.
- **U7. Z.ai Web Search, USD 0.01 per "use".** It is unclear whether one "use" equals one search query (`web_searches`) or one tool invocation that may run several queries. It is not priced; the existing note already says tool usage must not be silently omitted.
- **U8. Z.ai GLM-5.3-FlashX cache-hit price conflicts.** The Z.ai page says 0.075; OpenRouter's single `z-ai/fp8` endpoint bills 0.09. The direct entry uses Z.ai's 0.075. The OpenRouter identity uses its own live catalog, which is what OpenRouter bills.
- **U9. Kimi docs moved.** `platform.moonshot.ai/docs/pricing/chat` returns 301 to `platform.kimi.ai/docs/pricing/chat`, which is used as the source. The cache FAQ calls the models "kimi-k2.7" and "kimi-k2.7-highspeed" instead of the model IDs `kimi-k2.7-code` and `kimi-k2.7-code-highspeed`. That is a docs inconsistency with no price impact.
- **U10. OpenRouter headlines are misleading for GLM.** `z-ai/glm-5.3` has a headline of 0.039 input / 6 output (the Wafer endpoint), and `z-ai/glm-5.2` has 0.06 / 7. The `/models` price is not one endpoint's coherent rate card, which is further evidence the generics must be blocked.
- **U11. OpenRouter `qwen/qwen-plus` is a different model** from DashScope `qwen-plus`: its canonical slug is `qwen/qwen-plus-2025-01-25`, an old snapshot. The direct `qwen-plus` alias currently points to `qwen-plus-2025-12-01`.
- **U12. OpenRouter endpoint `discount` fields** are non-zero for a few endpoints, for example `venice/fp8` on GLM-5.1. I assumed listed prices are pre-discount or equal, so the maxima remain upper bounds.
- **U13. Kimi K3 cache writes on OpenRouter** reach 3.75/M (`amazon-bedrock/us-east-2`) under the single `cache_write_input_tokens` unit. OpenRouter does not distinguish TTLs.
