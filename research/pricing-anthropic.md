# Anthropic Claude pricing research

PlanMode #4793 (parent #4790). Research for `resources/pricing/provider-skus.php`. This file does not edit the snapshot; the writer thread integrates all four families.

- Checked: 2026-10-10 (UTC). All rates are USD.
- Primary source for native rates: <https://platform.claude.com/docs/en/about-claude/pricing>. Cross-checked against [model deprecations](https://platform.claude.com/docs/en/about-claude/model-deprecations), [models overview](https://platform.claude.com/docs/en/about-claude/models/overview), [context windows](https://platform.claude.com/docs/en/build-with-claude/context-windows), [data residency](https://platform.claude.com/docs/en/manage-claude/data-residency), [fast mode](https://platform.claude.com/docs/en/build-with-claude/fast-mode), [web search tool](https://platform.claude.com/docs/en/agents-and-tools/tool-use/web-search-tool), and the Fable 5.1, Opus 4.5 and Haiku 4.5 model pages.
- OpenRouter: the free public `https://openrouter.ai/api/v1/models` catalog (31 `anthropic/*` IDs) and `/models/{id}/endpoints` for every ID, plus [service tiers](https://openrouter.ai/docs/guides/features/service-tiers), [prompt caching](https://openrouter.ai/docs/guides/best-practices/prompt-caching) and [web search](https://openrouter.ai/docs/guides/features/server-tools/web-search).
- No API keys, credentials, paid or generative calls were used.

## 1. Summary

Rates are per million tokens, except web search, which is per search. "Cache read" is Anthropic's "cache hits and refreshes". Write columns are 5-minute and 1-hour cache writes.

### Native `anthropic:` identities (Claude API, synchronous Messages)

| Model (API ID) | Priced identity | Input | Output | Cache read | 5m write | 1h write | Web search | Why qualified |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Fable 5.1 (`claude-fable-5-1`) | `claude-fable-5-1-global` | 10 | 50 | 0.25 | 12.50 | 20 | 0.01 | US geo 1.1x |
| Mythos 5.1 (`claude-mythos-5-1`), limited access | `claude-mythos-5-1-global` | 10 | 50 | 0.25 | 12.50 | 20 | 0.01 | US geo 1.1x |
| Fable 5 (`claude-fable-5`) | `claude-fable-5-global` | 10 | 50 | 1 | 12.50 | 20 | 0.01 | US geo 1.1x |
| Mythos 5 (`claude-mythos-5`), limited access | `claude-mythos-5-global` | 10 | 50 | 1 | 12.50 | 20 | 0.01 | US geo 1.1x |
| Opus 5.5 (`claude-opus-5-5`) | existing `claude-opus-5-5-global-standard` | 4 | 20 | 0.20 | 5 | 8 | 0.01 (optional add) | US geo, fast mode |
| Opus 5 (`claude-opus-5`) | `claude-opus-5-global-standard` | 5 | 25 | 0.50 | 6.25 | 10 | 0.01 | US geo, fast mode |
| Opus 4.8 (`claude-opus-4-8`) | `claude-opus-4-8-global-standard` | 5 | 25 | 0.50 | 6.25 | 10 | 0.01 | US geo, fast mode |
| Opus 4.7 (`claude-opus-4-7`) | `claude-opus-4-7-global` | 5 | 25 | 0.50 | 6.25 | 10 | 0.01 | US geo |
| Opus 4.6 (`claude-opus-4-6`) | `claude-opus-4-6-global` | 5 | 25 | 0.50 | 6.25 | 10 | 0.01 | US geo |
| Opus 4.5 (`claude-opus-4-5-20251101`) | `claude-opus-4-5-20251101` + alias `claude-opus-4-5` | 5 | 25 | 0.50 | 6.25 | 10 | 0.01 | none: single price |
| Sonnet 5.5 (`claude-sonnet-5-5`) | existing `claude-sonnet-5-5-global-standard` | 2 | 10 | 0.10 | 2.50 | 4 | 0.01 (optional add) | US geo |
| Sonnet 5 (`claude-sonnet-5`) | existing `claude-sonnet-5-global` (update) | 2 | 10 | 0.20 | 2.50 | 4 | 0.01 | US geo |
| Sonnet 4.6 (`claude-sonnet-4-6`) | `claude-sonnet-4-6-global` | 3 | 15 | 0.30 | 3.75 | 6 | 0.01 | US geo |
| Haiku 5.5 (`claude-haiku-5-5`), prompt ≤ 100k | existing `claude-haiku-5-5-global-short` | 0.10 | 0.50 | 0.01 | 0.125 | 0.20 | 0.01 (optional add) | US geo, prompt tier |
| Haiku 5.5, prompt > 100k | existing `claude-haiku-5-5-global-long` | 0.50 | 2.50 | 0.05 | 0.625 | 1 | 0.01 (optional add) | US geo, prompt tier |
| Haiku 4.5 (`claude-haiku-4-5-20251001`) | `claude-haiku-4-5-20251001` + alias `claude-haiku-4-5` | 1 | 5 | 0.10 | 1.25 | 2 | 0.01 | none: single price |

US geography (`inference_geo: "us"`) is 1.1x on every token category for Claude 4.6 and later models; following the existing convention, it is excluded rather than published. If the writer wants US bases, they are exactly 1.1x the global rates above. For example, Opus 5 US is 5.5 / 27.5 / 0.55 / 6.875 / 11. Web search is not a token category and stays $10 per 1,000.

### OpenRouter `anthropic/*` identities

| OpenRouter ID | Default-routing endpoints | Disposition | Bound basis rates (input / output / read / 5m / 1h) |
| --- | --- | --- | --- |
| `anthropic/claude-fable-5.1` | 4, all at list price | **Live catalog sufficient** (see conflict C1) | n/a |
| `anthropic/claude-fable-5` | 6; `google-vertex/europe` 1.1x | Bound `-standard-max` + block generic | 11 / 55 / 1.1 / 13.75 / 22 |
| `anthropic/claude-opus-5.5` | 10 standard + opt-in `anthropic/fast` | Bound `-standard-max` + block generic | 4.4 / 22 / 0.22 / 5.5 / 8.8 |
| `anthropic/claude-opus-5` | 10 standard + opt-in `anthropic/fast` | Bound + block | 5.5 / 27.5 / 0.55 / 6.875 / 11 |
| `anthropic/claude-opus-4.8` | 10 standard + opt-in `anthropic/fast` | Bound + block | 5.5 / 27.5 / 0.55 / 6.875 / 11 |
| `anthropic/claude-opus-4.7` | 8; three regional 1.1x | Bound + block | 5.5 / 27.5 / 0.55 / 6.875 / 11 |
| `anthropic/claude-opus-4.6` | 6; `google-vertex/europe` 1.1x | Bound + block | 5.5 / 27.5 / 0.55 / 6.875 / 11 |
| `anthropic/claude-opus-4.5` | 6; `amazon-bedrock/eu-west-1` 1.1x | Bound + block | 5.5 / 27.5 / 0.55 / 6.875 / 11 |
| `anthropic/claude-sonnet-5.5` | 8; three regional 1.1x | Bound + block | 2.2 / 11 / 0.11 / 2.75 / 4.4 |
| `anthropic/claude-sonnet-5` | 10; five regional 1.1x | Bound + block | 2.2 / 11 / 0.22 / 2.75 / 4.4 |
| `anthropic/claude-sonnet-4.6` | 9; four regional 1.1x | Bound + block | 3.3 / 16.5 / 0.33 / 4.125 / 6.6 |
| `anthropic/claude-haiku-4.5` | 8; four regional 1.1x | Bound + block | 1.1 / 5.5 / 0.11 / 1.375 / 2.2 |
| `anthropic/claude-haiku-5.5` | 10; five regional 1.1x; `overrides` at `min_prompt_tokens` 100000 on every endpoint | Bound `-standard-short-max` and `-standard-long-max` + block | short 0.11 / 0.55 / 0.011 / 0.1375 / 0.22; long 0.55 / 2.75 / 0.055 / 0.6875 / 1.1 |

Every OpenRouter global-endpoint rate (Anthropic, `claude-on-aws`, Azure global, Bedrock global, Vertex global) matches Anthropic's pricing page exactly, including the 0.025x and 0.05x cache-read multipliers. Every regional endpoint is exactly 1.1x. All 13 bound rates were taken from the catalog maximum over non-`fast` endpoints and independently asserted equal to 1.1 × Anthropic's published rate. Every endpoint publishes `web_search` 0.01 per search, Anthropic's native $10 per 1,000 passed through. No Mythos model is listed on OpenRouter.

**Counts:** 24 new price entries (11 native, 13 OpenRouter), 1 required replacement (`anthropic:claude-sonnet-5-global`), 4 optional web-search updates to existing entries, 34 `fallback_blocked` additions and 2 aliases.

## 2. Ready-to-paste PHP entries

### 2a. New native `anthropic:` entries (11)

Paste into `prices`. Order is not significant for these entries.

```php
        'anthropic:claude-fable-5-1-global' => [
            'source' => 'https://platform.claude.com/docs/en/about-claude/pricing',
            'notes' => 'Checked 2026-10-10. Billing basis for claude-fable-5-1 (Claude Fable 5.1) with global geography (inference_geo global, the default), synchronous Messages API only; not a provider alias. All context lengths up to 1M tokens bill at one rate. No fast mode or prompt-length tier. Cache reads are 0.025x input. Excludes US geography (inference_geo us bills 1.1x on every token category), batch, Priority Tier, Bedrock, Google Cloud and Foundry. Thinking is output usage. Cache input partitions are exclusive; write units distinguish 5-minute and 1-hour TTLs, and no generic cache-write rate is published. web_searches counts usage.server_tool_use.web_search_requests at USD 10 per 1,000 searches (failed searches are not billed; the adapters do not map this field yet, so pass it explicitly). Code execution, web fetch tokens and other server tools are not priced here.',
            'rates' => [
                'input_tokens' => ['amount' => '10', 'per' => '1000000'],
                'output_tokens' => ['amount' => '50', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.25', 'per' => '1000000'],
                'cache_write_input_tokens_5m' => ['amount' => '12.50', 'per' => '1000000'],
                'cache_write_input_tokens_1h' => ['amount' => '20', 'per' => '1000000'],
                'web_searches' => ['amount' => '10', 'per' => '1000'],
            ],
        ],
        'anthropic:claude-mythos-5-1-global' => [
            'source' => 'https://platform.claude.com/docs/en/about-claude/pricing',
            'notes' => 'Checked 2026-10-10. Billing basis for claude-mythos-5-1 (Claude Mythos 5.1) with global geography (inference_geo global, the default), synchronous Messages API only; not a provider alias. All context lengths up to 1M tokens bill at one rate. Limited access: only organizations verified through an Anthropic verification program can call it; same rates as Claude Fable 5.1. No fast mode or prompt-length tier. Cache reads are 0.025x input. Excludes US geography (inference_geo us bills 1.1x on every token category), batch, Priority Tier, Bedrock, Google Cloud and Foundry. Thinking is output usage. Cache input partitions are exclusive; write units distinguish 5-minute and 1-hour TTLs, and no generic cache-write rate is published. web_searches counts usage.server_tool_use.web_search_requests at USD 10 per 1,000 searches (failed searches are not billed; the adapters do not map this field yet, so pass it explicitly). Code execution, web fetch tokens and other server tools are not priced here.',
            'rates' => [
                'input_tokens' => ['amount' => '10', 'per' => '1000000'],
                'output_tokens' => ['amount' => '50', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.25', 'per' => '1000000'],
                'cache_write_input_tokens_5m' => ['amount' => '12.50', 'per' => '1000000'],
                'cache_write_input_tokens_1h' => ['amount' => '20', 'per' => '1000000'],
                'web_searches' => ['amount' => '10', 'per' => '1000'],
            ],
        ],
        'anthropic:claude-fable-5-global' => [
            'source' => 'https://platform.claude.com/docs/en/about-claude/pricing',
            'notes' => 'Checked 2026-10-10. Billing basis for claude-fable-5 (Claude Fable 5, legacy) with global geography (inference_geo global, the default), synchronous Messages API only; not a provider alias. All context lengths up to 1M tokens bill at one rate. No fast mode or prompt-length tier. Cache reads are 0.1x input, unlike Fable 5.1. Excludes US geography (inference_geo us bills 1.1x on every token category), batch, Priority Tier, Bedrock, Google Cloud and Foundry. Thinking is output usage. Cache input partitions are exclusive; write units distinguish 5-minute and 1-hour TTLs, and no generic cache-write rate is published. web_searches counts usage.server_tool_use.web_search_requests at USD 10 per 1,000 searches (failed searches are not billed; the adapters do not map this field yet, so pass it explicitly). Code execution, web fetch tokens and other server tools are not priced here.',
            'rates' => [
                'input_tokens' => ['amount' => '10', 'per' => '1000000'],
                'output_tokens' => ['amount' => '50', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '1', 'per' => '1000000'],
                'cache_write_input_tokens_5m' => ['amount' => '12.50', 'per' => '1000000'],
                'cache_write_input_tokens_1h' => ['amount' => '20', 'per' => '1000000'],
                'web_searches' => ['amount' => '10', 'per' => '1000'],
            ],
        ],
        'anthropic:claude-mythos-5-global' => [
            'source' => 'https://platform.claude.com/docs/en/about-claude/pricing',
            'notes' => 'Checked 2026-10-10. Billing basis for claude-mythos-5 (Claude Mythos 5, legacy) with global geography (inference_geo global, the default), synchronous Messages API only; not a provider alias. All context lengths up to 1M tokens bill at one rate. Limited access: only organizations verified through an Anthropic verification program can call it; same rates as Claude Fable 5. No fast mode or prompt-length tier. Excludes US geography (inference_geo us bills 1.1x on every token category), batch, Priority Tier, Bedrock, Google Cloud and Foundry. Thinking is output usage. Cache input partitions are exclusive; write units distinguish 5-minute and 1-hour TTLs, and no generic cache-write rate is published. web_searches counts usage.server_tool_use.web_search_requests at USD 10 per 1,000 searches (failed searches are not billed; the adapters do not map this field yet, so pass it explicitly). Code execution, web fetch tokens and other server tools are not priced here.',
            'rates' => [
                'input_tokens' => ['amount' => '10', 'per' => '1000000'],
                'output_tokens' => ['amount' => '50', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '1', 'per' => '1000000'],
                'cache_write_input_tokens_5m' => ['amount' => '12.50', 'per' => '1000000'],
                'cache_write_input_tokens_1h' => ['amount' => '20', 'per' => '1000000'],
                'web_searches' => ['amount' => '10', 'per' => '1000'],
            ],
        ],
        'anthropic:claude-opus-5-global-standard' => [
            'source' => 'https://platform.claude.com/docs/en/about-claude/pricing',
            'notes' => 'Checked 2026-10-10. Billing basis for claude-opus-5 (Claude Opus 5, legacy) with global geography (inference_geo global, the default), synchronous Messages API only; not a provider alias. All context lengths up to 1M tokens bill at one rate. Standard speed only: fast mode (speed fast, usage.speed fast) bills USD 10/M input and 50/M output and is excluded. Excludes US geography (inference_geo us bills 1.1x on every token category), batch, Priority Tier, Bedrock, Google Cloud and Foundry. Thinking is output usage. Cache input partitions are exclusive; write units distinguish 5-minute and 1-hour TTLs, and no generic cache-write rate is published. web_searches counts usage.server_tool_use.web_search_requests at USD 10 per 1,000 searches (failed searches are not billed; the adapters do not map this field yet, so pass it explicitly). Code execution, web fetch tokens and other server tools are not priced here.',
            'rates' => [
                'input_tokens' => ['amount' => '5', 'per' => '1000000'],
                'output_tokens' => ['amount' => '25', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.50', 'per' => '1000000'],
                'cache_write_input_tokens_5m' => ['amount' => '6.25', 'per' => '1000000'],
                'cache_write_input_tokens_1h' => ['amount' => '10', 'per' => '1000000'],
                'web_searches' => ['amount' => '10', 'per' => '1000'],
            ],
        ],
        'anthropic:claude-opus-4-8-global-standard' => [
            'source' => 'https://platform.claude.com/docs/en/about-claude/pricing',
            'notes' => 'Checked 2026-10-10. Billing basis for claude-opus-4-8 (Claude Opus 4.8, legacy) with global geography (inference_geo global, the default), synchronous Messages API only; not a provider alias. All context lengths up to 1M tokens bill at one rate. Standard speed only: fast mode (speed fast, usage.speed fast) bills USD 10/M input and 50/M output and is excluded. Excludes US geography (inference_geo us bills 1.1x on every token category), batch, Priority Tier, Bedrock, Google Cloud and Foundry. Thinking is output usage. Cache input partitions are exclusive; write units distinguish 5-minute and 1-hour TTLs, and no generic cache-write rate is published. web_searches counts usage.server_tool_use.web_search_requests at USD 10 per 1,000 searches (failed searches are not billed; the adapters do not map this field yet, so pass it explicitly). Code execution, web fetch tokens and other server tools are not priced here.',
            'rates' => [
                'input_tokens' => ['amount' => '5', 'per' => '1000000'],
                'output_tokens' => ['amount' => '25', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.50', 'per' => '1000000'],
                'cache_write_input_tokens_5m' => ['amount' => '6.25', 'per' => '1000000'],
                'cache_write_input_tokens_1h' => ['amount' => '10', 'per' => '1000000'],
                'web_searches' => ['amount' => '10', 'per' => '1000'],
            ],
        ],
        'anthropic:claude-opus-4-7-global' => [
            'source' => 'https://platform.claude.com/docs/en/about-claude/pricing',
            'notes' => 'Checked 2026-10-10. Billing basis for claude-opus-4-7 (Claude Opus 4.7, legacy) with global geography (inference_geo global, the default), synchronous Messages API only; not a provider alias. All context lengths up to 1M tokens bill at one rate. No fast mode: speed fast returns an error. No prompt-length tier. Excludes US geography (inference_geo us bills 1.1x on every token category), batch, Priority Tier, Bedrock, Google Cloud and Foundry. Thinking is output usage. Cache input partitions are exclusive; write units distinguish 5-minute and 1-hour TTLs, and no generic cache-write rate is published. web_searches counts usage.server_tool_use.web_search_requests at USD 10 per 1,000 searches (failed searches are not billed; the adapters do not map this field yet, so pass it explicitly). Code execution, web fetch tokens and other server tools are not priced here.',
            'rates' => [
                'input_tokens' => ['amount' => '5', 'per' => '1000000'],
                'output_tokens' => ['amount' => '25', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.50', 'per' => '1000000'],
                'cache_write_input_tokens_5m' => ['amount' => '6.25', 'per' => '1000000'],
                'cache_write_input_tokens_1h' => ['amount' => '10', 'per' => '1000000'],
                'web_searches' => ['amount' => '10', 'per' => '1000'],
            ],
        ],
        'anthropic:claude-opus-4-6-global' => [
            'source' => 'https://platform.claude.com/docs/en/about-claude/pricing',
            'notes' => 'Checked 2026-10-10. Billing basis for claude-opus-4-6 (Claude Opus 4.6, legacy) with global geography (inference_geo global, the default), synchronous Messages API only; not a provider alias. All context lengths up to 1M tokens bill at one rate. No fast mode: a speed fast request runs and bills at standard rates and reports usage.speed standard. No prompt-length tier. Excludes US geography (inference_geo us bills 1.1x on every token category), batch, Priority Tier, Bedrock, Google Cloud and Foundry. Thinking is output usage. Cache input partitions are exclusive; write units distinguish 5-minute and 1-hour TTLs, and no generic cache-write rate is published. web_searches counts usage.server_tool_use.web_search_requests at USD 10 per 1,000 searches (failed searches are not billed; the adapters do not map this field yet, so pass it explicitly). Code execution, web fetch tokens and other server tools are not priced here.',
            'rates' => [
                'input_tokens' => ['amount' => '5', 'per' => '1000000'],
                'output_tokens' => ['amount' => '25', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.50', 'per' => '1000000'],
                'cache_write_input_tokens_5m' => ['amount' => '6.25', 'per' => '1000000'],
                'cache_write_input_tokens_1h' => ['amount' => '10', 'per' => '1000000'],
                'web_searches' => ['amount' => '10', 'per' => '1000'],
            ],
        ],
        'anthropic:claude-sonnet-4-6-global' => [
            'source' => 'https://platform.claude.com/docs/en/about-claude/pricing',
            'notes' => 'Checked 2026-10-10. Billing basis for claude-sonnet-4-6 (Claude Sonnet 4.6, legacy) with global geography (inference_geo global, the default), synchronous Messages API only; not a provider alias. All context lengths up to 1M tokens bill at one rate. No fast mode or prompt-length tier. Excludes US geography (inference_geo us bills 1.1x on every token category), batch, Priority Tier, Bedrock, Google Cloud and Foundry. Thinking is output usage. Cache input partitions are exclusive; write units distinguish 5-minute and 1-hour TTLs, and no generic cache-write rate is published. web_searches counts usage.server_tool_use.web_search_requests at USD 10 per 1,000 searches (failed searches are not billed; the adapters do not map this field yet, so pass it explicitly). Code execution, web fetch tokens and other server tools are not priced here.',
            'rates' => [
                'input_tokens' => ['amount' => '3', 'per' => '1000000'],
                'output_tokens' => ['amount' => '15', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.30', 'per' => '1000000'],
                'cache_write_input_tokens_5m' => ['amount' => '3.75', 'per' => '1000000'],
                'cache_write_input_tokens_1h' => ['amount' => '6', 'per' => '1000000'],
                'web_searches' => ['amount' => '10', 'per' => '1000'],
            ],
        ],
        'anthropic:claude-opus-4-5-20251101' => [
            'source' => 'https://platform.claude.com/docs/en/about-claude/pricing',
            'notes' => 'Checked 2026-10-10. Claude API pinned snapshot claude-opus-4-5-20251101 (Claude Opus 4.5); the documented alias claude-opus-4-5 resolves to it. Synchronous Messages API. Legacy model, retirement not sooner than 2026-11-24 per https://platform.claude.com/docs/en/about-claude/model-deprecations. It does not accept inference_geo (requests with it return 400) and has no fast mode, and its 200k context window has no long-context tier, so the Claude API has one price for it. Excludes batch, Priority Tier and partner-cloud regional pricing (Bedrock and Google Cloud bill regional endpoints 1.1x under their own provider identities). Thinking is output usage. Cache input partitions are exclusive; write units distinguish 5-minute and 1-hour TTLs, and no generic cache-write rate is published. web_searches counts usage.server_tool_use.web_search_requests at USD 10 per 1,000 searches (failed searches are not billed; the adapters do not map this field yet, so pass it explicitly). Code execution, web fetch tokens and other server tools are not priced here.',
            'rates' => [
                'input_tokens' => ['amount' => '5', 'per' => '1000000'],
                'output_tokens' => ['amount' => '25', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.50', 'per' => '1000000'],
                'cache_write_input_tokens_5m' => ['amount' => '6.25', 'per' => '1000000'],
                'cache_write_input_tokens_1h' => ['amount' => '10', 'per' => '1000000'],
                'web_searches' => ['amount' => '10', 'per' => '1000'],
            ],
        ],
        'anthropic:claude-haiku-4-5-20251001' => [
            'source' => 'https://platform.claude.com/docs/en/about-claude/pricing',
            'notes' => 'Checked 2026-10-10. Claude API pinned snapshot claude-haiku-4-5-20251001 (Claude Haiku 4.5); the documented alias claude-haiku-4-5 resolves to it. Synchronous Messages API. Legacy model, retirement not sooner than 2026-10-15 per https://platform.claude.com/docs/en/about-claude/model-deprecations. It does not accept inference_geo (requests with it return 400) and has no fast mode, and its 200k context window has no long-context tier, so the Claude API has one price for it. Excludes batch, Priority Tier and partner-cloud regional pricing (Bedrock and Google Cloud bill regional endpoints 1.1x under their own provider identities). Thinking is output usage. Cache input partitions are exclusive; write units distinguish 5-minute and 1-hour TTLs, and no generic cache-write rate is published. web_searches counts usage.server_tool_use.web_search_requests at USD 10 per 1,000 searches (failed searches are not billed; the adapters do not map this field yet, so pass it explicitly). Code execution, web fetch tokens and other server tools are not priced here.',
            'rates' => [
                'input_tokens' => ['amount' => '1', 'per' => '1000000'],
                'output_tokens' => ['amount' => '5', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.10', 'per' => '1000000'],
                'cache_write_input_tokens_5m' => ['amount' => '1.25', 'per' => '1000000'],
                'cache_write_input_tokens_1h' => ['amount' => '2', 'per' => '1000000'],
                'web_searches' => ['amount' => '10', 'per' => '1000'],
            ],
        ],
```

### 2b. Required replacement for the existing `anthropic:claude-sonnet-5-global`

Replace the whole existing entry. See drift D1.

```php
        'anthropic:claude-sonnet-5-global' => [
            'source' => 'https://platform.claude.com/docs/en/about-claude/pricing',
            'notes' => 'Checked 2026-10-10. Billing basis for claude-sonnet-5 (Claude Sonnet 5, legacy) with global geography (inference_geo global, the default), synchronous Messages API only; not a provider alias. The USD 2/M input and 10/M output launch price is now the standard price; the scheduled 2026-09-01 increase to 3/15 did not occur (pricing page footnote 3). All context lengths up to 1M tokens bill at one rate; no fast mode. Cache reads are 0.1x input (0.20/M), not the 0.05x of Sonnet 5.5. Excludes US geography (1.1x), batch, Priority Tier, Bedrock, Google Cloud and Foundry. Thinking is output usage. Cache input partitions are exclusive; write units distinguish 5-minute and 1-hour TTLs, and no generic cache-write rate is published. web_searches counts usage.server_tool_use.web_search_requests at USD 10 per 1,000 searches (failed searches are not billed; the adapters do not map this field yet, so pass it explicitly). Code execution, web fetch tokens and other server tools are not priced here.',
            'rates' => [
                'input_tokens' => ['amount' => '2', 'per' => '1000000'],
                'output_tokens' => ['amount' => '10', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.20', 'per' => '1000000'],
                'cache_write_input_tokens_5m' => ['amount' => '2.50', 'per' => '1000000'],
                'cache_write_input_tokens_1h' => ['amount' => '4', 'per' => '1000000'],
                'web_searches' => ['amount' => '10', 'per' => '1000'],
            ],
        ],
```

### 2c. Optional web-search additions to existing Anthropic entries

The four existing 5.5 entries have correct token rates. They omit web search and say they exclude server tools. For consistency with the new entries, the writer may add the following line to `claude-opus-5-5-global-standard`, `claude-sonnet-5-5-global-standard`, `claude-haiku-5-5-global-short` and `claude-haiku-5-5-global-long`:

```php
                'web_searches' => ['amount' => '10', 'per' => '1000'],
```

If they do, restart each note with `Checked 2026-10-10.` (CONTRIBUTING requires it for changed entries). Replace "batch and server tools" with "batch and server tools other than web search", and append the web-search sentence used in 2a. If not, leave the four entries untouched; their rates are unchanged.

### 2d. New OpenRouter bound bases (13)

Each source is the dated canonical endpoints URL, following the existing OpenRouter precedent. All canonical URLs resolved on 2026-10-10.

```php
        'openrouter:anthropic/claude-fable-5-standard-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/anthropic/claude-5-fable-20260609/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for anthropic/claude-fable-5 (canonical anthropic/claude-5-fable-20260609) under OpenRouter default routing at the standard service tier; not the :batch variant. Default routing load-balances across global endpoints at Anthropic\'s list price (USD 10/M input, 50/M output) and regional endpoints (google-vertex/europe) at a 1.1x uplift, so every request is priced at the regional rates and can overstate a globally served request by 10%. The global rates match https://platform.claude.com/docs/en/about-claude/pricing. Cache-write units are TTL-specific from the catalog\'s input_cache_write (5-minute) and input_cache_write_1h fields; OpenRouter usage reports only an aggregate cache_write_tokens, so an aggregate without a TTL split stays a missing unit instead of being billed at the cheaper 5-minute rate. Thinking is completion usage; the catalog publishes no internal_reasoning rate. web_searches counts native Anthropic web search only (usage.server_tool_use.web_search_requests with the native engine) at USD 0.01 per search, passed through per https://openrouter.ai/docs/guides/features/server-tools/web-search. Anthropic web search is unavailable on Bedrock; Exa and other engines are distinct, unpriced units. The generic openrouter:anthropic/claude-fable-5 identity is fallback-blocked. Snapshot invalidation: a change to the canonical revision, default-tier endpoint set, token rates or search pricing requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '11', 'per' => '1000000'],
                'output_tokens' => ['amount' => '55', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '1.1', 'per' => '1000000'],
                'cache_write_input_tokens_5m' => ['amount' => '13.75', 'per' => '1000000'],
                'cache_write_input_tokens_1h' => ['amount' => '22', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.01', 'per' => '1'],
            ],
        ],
        'openrouter:anthropic/claude-opus-5.5-standard-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/anthropic/claude-opus-5.5-20260921/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for anthropic/claude-opus-5.5 (canonical anthropic/claude-opus-5.5-20260921) under OpenRouter default routing at the standard service tier; not the :batch variant. Default routing load-balances across global endpoints at Anthropic\'s list price (USD 4/M input, 20/M output) and regional endpoints (amazon-bedrock/eu-west-1, amazon-bedrock/us-east-1, azure/us, google-vertex/europe, google-vertex/us) at a 1.1x uplift, so every request is priced at the regional rates and can overstate a globally served request by 10%. The anthropic/fast endpoint is Anthropic fast mode, an opt-in priority tier (speed fast, service_tier fast or priority, :nitro, or the tier slug) that default routing never selects; it is excluded and callers must not opt into it. The global rates match https://platform.claude.com/docs/en/about-claude/pricing. Cache-write units are TTL-specific from the catalog\'s input_cache_write (5-minute) and input_cache_write_1h fields; OpenRouter usage reports only an aggregate cache_write_tokens, so an aggregate without a TTL split stays a missing unit instead of being billed at the cheaper 5-minute rate. Thinking is completion usage; the catalog publishes no internal_reasoning rate. web_searches counts native Anthropic web search only (usage.server_tool_use.web_search_requests with the native engine) at USD 0.01 per search, passed through per https://openrouter.ai/docs/guides/features/server-tools/web-search. Anthropic web search is unavailable on Bedrock; Exa and other engines are distinct, unpriced units. The generic openrouter:anthropic/claude-opus-5.5 identity is fallback-blocked. Snapshot invalidation: a change to the canonical revision, default-tier endpoint set, token rates or search pricing requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '4.4', 'per' => '1000000'],
                'output_tokens' => ['amount' => '22', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.22', 'per' => '1000000'],
                'cache_write_input_tokens_5m' => ['amount' => '5.5', 'per' => '1000000'],
                'cache_write_input_tokens_1h' => ['amount' => '8.8', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.01', 'per' => '1'],
            ],
        ],
        'openrouter:anthropic/claude-opus-5-standard-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/anthropic/claude-opus-5-20260723/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for anthropic/claude-opus-5 (canonical anthropic/claude-opus-5-20260723) under OpenRouter default routing at the standard service tier; not the :batch variant. Default routing load-balances across global endpoints at Anthropic\'s list price (USD 5/M input, 25/M output) and regional endpoints (amazon-bedrock/eu-west-1, amazon-bedrock/us-east-1, azure/us, google-vertex/europe, google-vertex/us) at a 1.1x uplift, so every request is priced at the regional rates and can overstate a globally served request by 10%. The anthropic/fast endpoint is Anthropic fast mode, an opt-in priority tier (speed fast, service_tier fast or priority, :nitro, or the tier slug) that default routing never selects; it is excluded and callers must not opt into it. The global rates match https://platform.claude.com/docs/en/about-claude/pricing. Cache-write units are TTL-specific from the catalog\'s input_cache_write (5-minute) and input_cache_write_1h fields; OpenRouter usage reports only an aggregate cache_write_tokens, so an aggregate without a TTL split stays a missing unit instead of being billed at the cheaper 5-minute rate. Thinking is completion usage; the catalog publishes no internal_reasoning rate. web_searches counts native Anthropic web search only (usage.server_tool_use.web_search_requests with the native engine) at USD 0.01 per search, passed through per https://openrouter.ai/docs/guides/features/server-tools/web-search. Anthropic web search is unavailable on Bedrock; Exa and other engines are distinct, unpriced units. The generic openrouter:anthropic/claude-opus-5 identity is fallback-blocked. Snapshot invalidation: a change to the canonical revision, default-tier endpoint set, token rates or search pricing requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '5.5', 'per' => '1000000'],
                'output_tokens' => ['amount' => '27.5', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.55', 'per' => '1000000'],
                'cache_write_input_tokens_5m' => ['amount' => '6.875', 'per' => '1000000'],
                'cache_write_input_tokens_1h' => ['amount' => '11', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.01', 'per' => '1'],
            ],
        ],
        'openrouter:anthropic/claude-opus-4.8-standard-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/anthropic/claude-4.8-opus-20260528/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for anthropic/claude-opus-4.8 (canonical anthropic/claude-4.8-opus-20260528) under OpenRouter default routing at the standard service tier; not the :batch variant. Default routing load-balances across global endpoints at Anthropic\'s list price (USD 5/M input, 25/M output) and regional endpoints (amazon-bedrock/eu-west-1, amazon-bedrock/us, azure/us, google-vertex/europe, google-vertex/us) at a 1.1x uplift, so every request is priced at the regional rates and can overstate a globally served request by 10%. The anthropic/fast endpoint is Anthropic fast mode, an opt-in priority tier (speed fast, service_tier fast or priority, :nitro, or the tier slug) that default routing never selects; it is excluded and callers must not opt into it. The global rates match https://platform.claude.com/docs/en/about-claude/pricing. Cache-write units are TTL-specific from the catalog\'s input_cache_write (5-minute) and input_cache_write_1h fields; OpenRouter usage reports only an aggregate cache_write_tokens, so an aggregate without a TTL split stays a missing unit instead of being billed at the cheaper 5-minute rate. Thinking is completion usage; the catalog publishes no internal_reasoning rate. web_searches counts native Anthropic web search only (usage.server_tool_use.web_search_requests with the native engine) at USD 0.01 per search, passed through per https://openrouter.ai/docs/guides/features/server-tools/web-search. Anthropic web search is unavailable on Bedrock; Exa and other engines are distinct, unpriced units. The generic openrouter:anthropic/claude-opus-4.8 identity is fallback-blocked. Snapshot invalidation: a change to the canonical revision, default-tier endpoint set, token rates or search pricing requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '5.5', 'per' => '1000000'],
                'output_tokens' => ['amount' => '27.5', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.55', 'per' => '1000000'],
                'cache_write_input_tokens_5m' => ['amount' => '6.875', 'per' => '1000000'],
                'cache_write_input_tokens_1h' => ['amount' => '11', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.01', 'per' => '1'],
            ],
        ],
        'openrouter:anthropic/claude-opus-4.7-standard-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/anthropic/claude-4.7-opus-20260416/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for anthropic/claude-opus-4.7 (canonical anthropic/claude-4.7-opus-20260416) under OpenRouter default routing at the standard service tier; not the :batch variant. Default routing load-balances across global endpoints at Anthropic\'s list price (USD 5/M input, 25/M output) and regional endpoints (amazon-bedrock/eu-west-1, google-vertex/europe, google-vertex/us) at a 1.1x uplift, so every request is priced at the regional rates and can overstate a globally served request by 10%. The global rates match https://platform.claude.com/docs/en/about-claude/pricing. Cache-write units are TTL-specific from the catalog\'s input_cache_write (5-minute) and input_cache_write_1h fields; OpenRouter usage reports only an aggregate cache_write_tokens, so an aggregate without a TTL split stays a missing unit instead of being billed at the cheaper 5-minute rate. Thinking is completion usage; the catalog publishes no internal_reasoning rate. web_searches counts native Anthropic web search only (usage.server_tool_use.web_search_requests with the native engine) at USD 0.01 per search, passed through per https://openrouter.ai/docs/guides/features/server-tools/web-search. Anthropic web search is unavailable on Bedrock; Exa and other engines are distinct, unpriced units. The generic openrouter:anthropic/claude-opus-4.7 identity is fallback-blocked. Snapshot invalidation: a change to the canonical revision, default-tier endpoint set, token rates or search pricing requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '5.5', 'per' => '1000000'],
                'output_tokens' => ['amount' => '27.5', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.55', 'per' => '1000000'],
                'cache_write_input_tokens_5m' => ['amount' => '6.875', 'per' => '1000000'],
                'cache_write_input_tokens_1h' => ['amount' => '11', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.01', 'per' => '1'],
            ],
        ],
        'openrouter:anthropic/claude-opus-4.6-standard-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/anthropic/claude-4.6-opus-20260205/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for anthropic/claude-opus-4.6 (canonical anthropic/claude-4.6-opus-20260205) under OpenRouter default routing at the standard service tier; not the :batch variant. Default routing load-balances across global endpoints at Anthropic\'s list price (USD 5/M input, 25/M output) and regional endpoints (google-vertex/europe) at a 1.1x uplift, so every request is priced at the regional rates and can overstate a globally served request by 10%. The global rates match https://platform.claude.com/docs/en/about-claude/pricing. Cache-write units are TTL-specific from the catalog\'s input_cache_write (5-minute) and input_cache_write_1h fields; OpenRouter usage reports only an aggregate cache_write_tokens, so an aggregate without a TTL split stays a missing unit instead of being billed at the cheaper 5-minute rate. Thinking is completion usage; the catalog publishes no internal_reasoning rate. web_searches counts native Anthropic web search only (usage.server_tool_use.web_search_requests with the native engine) at USD 0.01 per search, passed through per https://openrouter.ai/docs/guides/features/server-tools/web-search. Anthropic web search is unavailable on Bedrock; Exa and other engines are distinct, unpriced units. The generic openrouter:anthropic/claude-opus-4.6 identity is fallback-blocked. Snapshot invalidation: a change to the canonical revision, default-tier endpoint set, token rates or search pricing requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '5.5', 'per' => '1000000'],
                'output_tokens' => ['amount' => '27.5', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.55', 'per' => '1000000'],
                'cache_write_input_tokens_5m' => ['amount' => '6.875', 'per' => '1000000'],
                'cache_write_input_tokens_1h' => ['amount' => '11', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.01', 'per' => '1'],
            ],
        ],
        'openrouter:anthropic/claude-opus-4.5-standard-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/anthropic/claude-4.5-opus-20251124/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for anthropic/claude-opus-4.5 (canonical anthropic/claude-4.5-opus-20251124) under OpenRouter default routing at the standard service tier; not the :batch variant. Default routing load-balances across global endpoints at Anthropic\'s list price (USD 5/M input, 25/M output) and regional endpoints (amazon-bedrock/eu-west-1) at a 1.1x uplift, so every request is priced at the regional rates and can overstate a globally served request by 10%. The global rates match https://platform.claude.com/docs/en/about-claude/pricing. Cache-write units are TTL-specific from the catalog\'s input_cache_write (5-minute) and input_cache_write_1h fields; OpenRouter usage reports only an aggregate cache_write_tokens, so an aggregate without a TTL split stays a missing unit instead of being billed at the cheaper 5-minute rate. Thinking is completion usage; the catalog publishes no internal_reasoning rate. web_searches counts native Anthropic web search only (usage.server_tool_use.web_search_requests with the native engine) at USD 0.01 per search, passed through per https://openrouter.ai/docs/guides/features/server-tools/web-search. Anthropic web search is unavailable on Bedrock; Exa and other engines are distinct, unpriced units. The generic openrouter:anthropic/claude-opus-4.5 identity is fallback-blocked. Snapshot invalidation: a change to the canonical revision, default-tier endpoint set, token rates or search pricing requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '5.5', 'per' => '1000000'],
                'output_tokens' => ['amount' => '27.5', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.55', 'per' => '1000000'],
                'cache_write_input_tokens_5m' => ['amount' => '6.875', 'per' => '1000000'],
                'cache_write_input_tokens_1h' => ['amount' => '11', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.01', 'per' => '1'],
            ],
        ],
        'openrouter:anthropic/claude-sonnet-5.5-standard-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/anthropic/claude-sonnet-5.5-20260928/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for anthropic/claude-sonnet-5.5 (canonical anthropic/claude-sonnet-5.5-20260928) under OpenRouter default routing at the standard service tier; not the :batch variant. Default routing load-balances across global endpoints at Anthropic\'s list price (USD 2/M input, 10/M output) and regional endpoints (azure/us, google-vertex/europe, google-vertex/us) at a 1.1x uplift, so every request is priced at the regional rates and can overstate a globally served request by 10%. The global rates match https://platform.claude.com/docs/en/about-claude/pricing. Cache-write units are TTL-specific from the catalog\'s input_cache_write (5-minute) and input_cache_write_1h fields; OpenRouter usage reports only an aggregate cache_write_tokens, so an aggregate without a TTL split stays a missing unit instead of being billed at the cheaper 5-minute rate. Thinking is completion usage; the catalog publishes no internal_reasoning rate. web_searches counts native Anthropic web search only (usage.server_tool_use.web_search_requests with the native engine) at USD 0.01 per search, passed through per https://openrouter.ai/docs/guides/features/server-tools/web-search. Anthropic web search is unavailable on Bedrock; Exa and other engines are distinct, unpriced units. The generic openrouter:anthropic/claude-sonnet-5.5 identity is fallback-blocked. Snapshot invalidation: a change to the canonical revision, default-tier endpoint set, token rates or search pricing requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '2.2', 'per' => '1000000'],
                'output_tokens' => ['amount' => '11', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.11', 'per' => '1000000'],
                'cache_write_input_tokens_5m' => ['amount' => '2.75', 'per' => '1000000'],
                'cache_write_input_tokens_1h' => ['amount' => '4.4', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.01', 'per' => '1'],
            ],
        ],
        'openrouter:anthropic/claude-sonnet-5-standard-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/anthropic/claude-sonnet-5-20260630/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for anthropic/claude-sonnet-5 (canonical anthropic/claude-sonnet-5-20260630) under OpenRouter default routing at the standard service tier; not the :batch variant. Default routing load-balances across global endpoints at Anthropic\'s list price (USD 2/M input, 10/M output) and regional endpoints (amazon-bedrock/eu-west-1, amazon-bedrock/us-east-1, azure/us, google-vertex/europe, google-vertex/us) at a 1.1x uplift, so every request is priced at the regional rates and can overstate a globally served request by 10%. The global rates match https://platform.claude.com/docs/en/about-claude/pricing. Cache-write units are TTL-specific from the catalog\'s input_cache_write (5-minute) and input_cache_write_1h fields; OpenRouter usage reports only an aggregate cache_write_tokens, so an aggregate without a TTL split stays a missing unit instead of being billed at the cheaper 5-minute rate. Thinking is completion usage; the catalog publishes no internal_reasoning rate. web_searches counts native Anthropic web search only (usage.server_tool_use.web_search_requests with the native engine) at USD 0.01 per search, passed through per https://openrouter.ai/docs/guides/features/server-tools/web-search. Anthropic web search is unavailable on Bedrock; Exa and other engines are distinct, unpriced units. The generic openrouter:anthropic/claude-sonnet-5 identity is fallback-blocked. Snapshot invalidation: a change to the canonical revision, default-tier endpoint set, token rates or search pricing requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '2.2', 'per' => '1000000'],
                'output_tokens' => ['amount' => '11', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.22', 'per' => '1000000'],
                'cache_write_input_tokens_5m' => ['amount' => '2.75', 'per' => '1000000'],
                'cache_write_input_tokens_1h' => ['amount' => '4.4', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.01', 'per' => '1'],
            ],
        ],
        'openrouter:anthropic/claude-sonnet-4.6-standard-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/anthropic/claude-4.6-sonnet-20260217/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for anthropic/claude-sonnet-4.6 (canonical anthropic/claude-4.6-sonnet-20260217) under OpenRouter default routing at the standard service tier; not the :batch variant. Default routing load-balances across global endpoints at Anthropic\'s list price (USD 3/M input, 15/M output) and regional endpoints (amazon-bedrock/eu-west-1, amazon-bedrock/us, google-vertex/europe, google-vertex/us-east5) at a 1.1x uplift, so every request is priced at the regional rates and can overstate a globally served request by 10%. The global rates match https://platform.claude.com/docs/en/about-claude/pricing. Cache-write units are TTL-specific from the catalog\'s input_cache_write (5-minute) and input_cache_write_1h fields; OpenRouter usage reports only an aggregate cache_write_tokens, so an aggregate without a TTL split stays a missing unit instead of being billed at the cheaper 5-minute rate. Thinking is completion usage; the catalog publishes no internal_reasoning rate. web_searches counts native Anthropic web search only (usage.server_tool_use.web_search_requests with the native engine) at USD 0.01 per search, passed through per https://openrouter.ai/docs/guides/features/server-tools/web-search. Anthropic web search is unavailable on Bedrock; Exa and other engines are distinct, unpriced units. The generic openrouter:anthropic/claude-sonnet-4.6 identity is fallback-blocked. Snapshot invalidation: a change to the canonical revision, default-tier endpoint set, token rates or search pricing requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '3.3', 'per' => '1000000'],
                'output_tokens' => ['amount' => '16.5', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.33', 'per' => '1000000'],
                'cache_write_input_tokens_5m' => ['amount' => '4.125', 'per' => '1000000'],
                'cache_write_input_tokens_1h' => ['amount' => '6.6', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.01', 'per' => '1'],
            ],
        ],
        'openrouter:anthropic/claude-haiku-4.5-standard-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/anthropic/claude-4.5-haiku-20251001/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for anthropic/claude-haiku-4.5 (canonical anthropic/claude-4.5-haiku-20251001) under OpenRouter default routing at the standard service tier; not the :batch variant. Default routing load-balances across global endpoints at Anthropic\'s list price (USD 1/M input, 5/M output) and regional endpoints (amazon-bedrock/eu-west-1, amazon-bedrock/us, google-vertex/europe, google-vertex/us-east5) at a 1.1x uplift, so every request is priced at the regional rates and can overstate a globally served request by 10%. The global rates match https://platform.claude.com/docs/en/about-claude/pricing. Cache-write units are TTL-specific from the catalog\'s input_cache_write (5-minute) and input_cache_write_1h fields; OpenRouter usage reports only an aggregate cache_write_tokens, so an aggregate without a TTL split stays a missing unit instead of being billed at the cheaper 5-minute rate. Thinking is completion usage; the catalog publishes no internal_reasoning rate. web_searches counts native Anthropic web search only (usage.server_tool_use.web_search_requests with the native engine) at USD 0.01 per search, passed through per https://openrouter.ai/docs/guides/features/server-tools/web-search. Anthropic web search is unavailable on Bedrock; Exa and other engines are distinct, unpriced units. The generic openrouter:anthropic/claude-haiku-4.5 identity is fallback-blocked. Snapshot invalidation: a change to the canonical revision, default-tier endpoint set, token rates or search pricing requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '1.1', 'per' => '1000000'],
                'output_tokens' => ['amount' => '5.5', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.11', 'per' => '1000000'],
                'cache_write_input_tokens_5m' => ['amount' => '1.375', 'per' => '1000000'],
                'cache_write_input_tokens_1h' => ['amount' => '2.2', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.01', 'per' => '1'],
            ],
        ],
        'openrouter:anthropic/claude-haiku-5.5-standard-short-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/anthropic/claude-haiku-5.5-20261007/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for anthropic/claude-haiku-5.5 (canonical anthropic/claude-haiku-5.5-20261007) under OpenRouter default routing at the standard service tier; not the :batch variant. Applies only when the prompt, counting cache reads and writes, has fewer than 100,000 tokens; use the -standard-long-max basis at 100,000 tokens or more (catalog override min_prompt_tokens 100000). Default routing load-balances across global endpoints at Anthropic\'s list price (USD 0.1/M input, 0.5/M output) and regional endpoints (amazon-bedrock/eu-west-1, amazon-bedrock/us-east-1, azure/us, google-vertex/europe, google-vertex/us) at a 1.1x uplift, so every request is priced at the regional rates and can overstate a globally served request by 10%. The global rates match https://platform.claude.com/docs/en/about-claude/pricing. Cache-write units are TTL-specific from the catalog\'s input_cache_write (5-minute) and input_cache_write_1h fields; OpenRouter usage reports only an aggregate cache_write_tokens, so an aggregate without a TTL split stays a missing unit instead of being billed at the cheaper 5-minute rate. Thinking is completion usage; the catalog publishes no internal_reasoning rate. web_searches counts native Anthropic web search only (usage.server_tool_use.web_search_requests with the native engine) at USD 0.01 per search, passed through per https://openrouter.ai/docs/guides/features/server-tools/web-search. Anthropic web search is unavailable on Bedrock; Exa and other engines are distinct, unpriced units. The generic openrouter:anthropic/claude-haiku-5.5 identity is fallback-blocked. Snapshot invalidation: a change to the canonical revision, default-tier endpoint set, token rates or search pricing requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '0.11', 'per' => '1000000'],
                'output_tokens' => ['amount' => '0.55', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.011', 'per' => '1000000'],
                'cache_write_input_tokens_5m' => ['amount' => '0.1375', 'per' => '1000000'],
                'cache_write_input_tokens_1h' => ['amount' => '0.22', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.01', 'per' => '1'],
            ],
        ],
        'openrouter:anthropic/claude-haiku-5.5-standard-long-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/anthropic/claude-haiku-5.5-20261007/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for anthropic/claude-haiku-5.5 (canonical anthropic/claude-haiku-5.5-20261007) under OpenRouter default routing at the standard service tier; not the :batch variant. Applies only when the prompt, counting cache reads and writes, has at least 100,000 tokens: the catalog override starts at min_prompt_tokens 100000, while Anthropic bills more only for prompts over 100,000, so a prompt of exactly 100,000 tokens takes this conservative long basis. The higher tier applies to the whole request. Default routing load-balances across global endpoints at Anthropic\'s list price (USD 0.5/M input, 2.5/M output) and regional endpoints (amazon-bedrock/eu-west-1, amazon-bedrock/us-east-1, azure/us, google-vertex/europe, google-vertex/us) at a 1.1x uplift, so every request is priced at the regional rates and can overstate a globally served request by 10%. The global rates match https://platform.claude.com/docs/en/about-claude/pricing. Cache-write units are TTL-specific from the catalog\'s input_cache_write (5-minute) and input_cache_write_1h fields; OpenRouter usage reports only an aggregate cache_write_tokens, so an aggregate without a TTL split stays a missing unit instead of being billed at the cheaper 5-minute rate. Thinking is completion usage; the catalog publishes no internal_reasoning rate. web_searches counts native Anthropic web search only (usage.server_tool_use.web_search_requests with the native engine) at USD 0.01 per search, passed through per https://openrouter.ai/docs/guides/features/server-tools/web-search. Anthropic web search is unavailable on Bedrock; Exa and other engines are distinct, unpriced units. The generic openrouter:anthropic/claude-haiku-5.5 identity is fallback-blocked. Snapshot invalidation: a change to the canonical revision, default-tier endpoint set, token rates or search pricing requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '0.55', 'per' => '1000000'],
                'output_tokens' => ['amount' => '2.75', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.055', 'per' => '1000000'],
                'cache_write_input_tokens_5m' => ['amount' => '0.6875', 'per' => '1000000'],
                'cache_write_input_tokens_1h' => ['amount' => '1.1', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.01', 'per' => '1'],
            ],
        ],
```

## 3. Proposed `fallback_blocked` and alias changes

### 3a. `fallback_blocked`

Add after `'anthropic:claude-sonnet-5-us',`. Keep the existing Anthropic comment block (dated 2026-10-09) or extend it with: "Checked 2026-10-10. Claude 4.6 and later models, including Fable and Mythos, bill `inference_geo: us` at 1.1x, and Opus 5 and Opus 4.8 also have fast mode, so their generic identities cannot select a rate."

```php
        'anthropic:claude-fable-5-1',
        'anthropic:claude-mythos-5-1',
        'anthropic:claude-fable-5',
        'anthropic:claude-mythos-5',
        'anthropic:claude-opus-5',
        'anthropic:claude-opus-4-8',
        'anthropic:claude-opus-4-7',
        'anthropic:claude-opus-4-6',
        'anthropic:claude-sonnet-4-6',
```

Add after `'openrouter:google/gemini-3.1-flash-lite',`. The bound bases are themselves fallback-blocked, like the existing `-272k` and `-standard-max` precedents:

```php
        // Checked 2026-10-10 against https://openrouter.ai/api/v1/models/{canonical}/endpoints
        // for each Anthropic model. Default routing spans list-price global endpoints
        // and 1.1x regional Bedrock, Vertex and Azure endpoints (Haiku 5.5 also has a
        // 100k prompt tier the live source ignores), so the generic identities must
        // not take the live catalog's headline price. Use the bound bases.
        'openrouter:anthropic/claude-fable-5-standard-max',
        'openrouter:anthropic/claude-opus-5.5-standard-max',
        'openrouter:anthropic/claude-opus-5-standard-max',
        'openrouter:anthropic/claude-opus-4.8-standard-max',
        'openrouter:anthropic/claude-opus-4.7-standard-max',
        'openrouter:anthropic/claude-opus-4.6-standard-max',
        'openrouter:anthropic/claude-opus-4.5-standard-max',
        'openrouter:anthropic/claude-sonnet-5.5-standard-max',
        'openrouter:anthropic/claude-sonnet-5-standard-max',
        'openrouter:anthropic/claude-sonnet-4.6-standard-max',
        'openrouter:anthropic/claude-haiku-4.5-standard-max',
        'openrouter:anthropic/claude-haiku-5.5-standard-short-max',
        'openrouter:anthropic/claude-haiku-5.5-standard-long-max',
        'openrouter:anthropic/claude-fable-5',
        'openrouter:anthropic/claude-opus-5.5',
        'openrouter:anthropic/claude-opus-5',
        'openrouter:anthropic/claude-opus-4.8',
        'openrouter:anthropic/claude-opus-4.7',
        'openrouter:anthropic/claude-opus-4.6',
        'openrouter:anthropic/claude-opus-4.5',
        'openrouter:anthropic/claude-sonnet-5.5',
        'openrouter:anthropic/claude-sonnet-5',
        'openrouter:anthropic/claude-sonnet-4.6',
        'openrouter:anthropic/claude-haiku-4.5',
        'openrouter:anthropic/claude-haiku-5.5',
```

The invariant test `keeps every fallback-blocked identity unpriced unless it is an allowlisted bound basis` compares `array_intersect($blocked, priced)` with the allowlist **in blocked-list order**. Append these 13 lines to `$boundBasesAllowlist` in this order, after `'openrouter:google/gemini-3.1-flash-lite-standard-max',`:

```php
        'openrouter:anthropic/claude-fable-5-standard-max',
        'openrouter:anthropic/claude-opus-5.5-standard-max',
        'openrouter:anthropic/claude-opus-5-standard-max',
        'openrouter:anthropic/claude-opus-4.8-standard-max',
        'openrouter:anthropic/claude-opus-4.7-standard-max',
        'openrouter:anthropic/claude-opus-4.6-standard-max',
        'openrouter:anthropic/claude-opus-4.5-standard-max',
        'openrouter:anthropic/claude-sonnet-5.5-standard-max',
        'openrouter:anthropic/claude-sonnet-5-standard-max',
        'openrouter:anthropic/claude-sonnet-4.6-standard-max',
        'openrouter:anthropic/claude-haiku-4.5-standard-max',
        'openrouter:anthropic/claude-haiku-5.5-standard-short-max',
        'openrouter:anthropic/claude-haiku-5.5-standard-long-max',
```

Not blocked, on purpose: `anthropic:claude-opus-4-5-20251101` and `anthropic:claude-haiku-4-5-20251001` (single price; priced directly), and `openrouter:anthropic/claude-fable-5.1` (live catalog sufficient).

### 3b. `aliases`

Anthropic documents both as convenience aliases that resolve to the pinned dated snapshot. Neither target is fallback-blocked.

```php
        'anthropic:claude-opus-4-5' => 'anthropic:claude-opus-4-5-20251101',
        'anthropic:claude-haiku-4-5' => 'anthropic:claude-haiku-4-5-20251001',
```

Do not alias Claude 4.6-and-later IDs. From the 4.6 generation on, the dateless ID is itself the pinned snapshot, and those identities are fallback-blocked.

### 3c. Dry run of this proposal

I applied sections 2a, 2b, 2d, 3a and 3b to a scratch copy of `main` (`1c8f4f0`), with version 12, the new `retrieved_at`, the date-regex test extended to `2026-10-10`, the version/date test pins bumped and the allowlist extended. `php -l` passed. `composer test` passed 376 and failed 2. Both failures pin the old incomplete Sonnet 5 entry and need new expectations:

- `PackagePricingSourceTest` › "quotes Claude Sonnet 5 global routing exactly…": the partial quote is now 2.65 instead of 2.64 (+1 web search at 0.01), and `web_searches` is no longer missing.
- `PricingResolverTest` › "uses the complete global-routing Claude snapshot…": 2.640004 instead of 2.64 (+1 one-hour cache-write token at $4/M), and `cache_write_input_tokens_1h` is no longer missing.

Scratch assertions with hand-derived totals also passed:

- Fable 5.1 global with 1,000 tokens in each token unit plus 2 searches = 0.11275.
- `quote('anthropic', 'claude-haiku-4-5', 1M in, 1M out)` = 6 via the alias.
- `openrouter:anthropic/claude-haiku-5.5-standard-long-max`, 200k in + 10k out = 0.1375, partial when an aggregate-only cache write is present.
- The four sampled generic identities are blocked and unpriced.

The writer still needs exact-decimal tests for the new entries, README tables and changelog updates.

## 4. Drift found in existing Anthropic entries

| ID | Entry | Finding | Action |
| --- | --- | --- | --- |
| D1 | `anthropic:claude-sonnet-5-global` (checked 2026-09-17) | Input 2 and output 10 are still correct. Pricing-page footnote 3 now says the $2/$10 introductory price is the standard price and the scheduled 2026-09-01 increase to $3/$15 will not occur. The entry omits cache read 0.20 (0.1x, not Sonnet 5.5's 0.05x), 5m write 2.50, 1h write 4 and web search, so any cached request quotes partial. | Replace with 2b. |
| D2 | `claude-opus-5-5-global-standard`, `claude-sonnet-5-5-global-standard`, `claude-haiku-5-5-global-short/-long` | Every rate, the 100,000-token Haiku threshold, the whole-request tier rule and the cache-read multipliers match the page. No rate drift. | Optional web search (2c). |
| D3 | `claude-sonnet-5-5-global-standard` naming | It carries a `-standard` speed suffix, but Sonnet 5.5 has no fast mode. The new entries use `-global` when a model has no fast mode, matching `claude-sonnet-5-global`, and `-global-standard` only for Opus 5 and Opus 4.8, which have fast mode. | No rename; renaming breaks callers. Mention the inconsistency in the README if useful. |
| D4 | Anthropic `fallback_blocked` comment and README "generic identities" paragraph | They list only the 5.5 family. | Update with 3a. |

## 5. Excluded, with reasons

| Item | Reason |
| --- | --- |
| Claude Sonnet 4.5 (`claude-sonnet-4-5-20250929`) | Deprecated 2026-09-30; retires on the Claude API 2026-11-30. Still on the pricing page at $3/$15, cache read 0.30, writes 3.75/6. |
| Claude Mythos Preview | Deprecated 2026-06-09; not in the model pricing table. |
| Claude Opus 4.1, Opus 4, Sonnet 4, Haiku 3.5 | Retired on the Claude API; still on partner clouds at partner pricing. Not `anthropic:` identities. |
| Sonnet 3.7, Haiku 3, Opus 3, Sonnet 3.5, Claude 2.x and 1.x | Retired. |
| OpenRouter `anthropic/claude-sonnet-4.5`, `claude-opus-4.1`, `claude-sonnet-4` | Deprecated or retired upstream (see C2 for a safety note). |
| Batch API (50% off) and every OpenRouter `:batch` ID | Asynchronous Batch API, excluded by scope. The live catalog prices `:batch` IDs from a single Anthropic endpoint. |
| Priority Tier | Anthropic no longer sells it; commitments are contract-priced. |
| Fast mode | Opus 5.5 $8/$40, Opus 5 and Opus 4.8 $10/$50 per M, with cache multipliers and US 1.1x stacking. Also excluded: OpenRouter `anthropic/fast` tier endpoints, opt-in only, and the deprecated OpenRouter `*-fast` model IDs. |
| Code execution | $0.05 per container-hour beyond 1,550 free hours per organization each month, with a 5-minute minimum. Free when used with `web_search_20260209`/`web_fetch_20260209` or later. Not per-request; no package unit. |
| Web fetch | No charge beyond tokens. |
| Tool-use system prompt, bash, text editor, computer use and browser use overheads | Ordinary input tokens; no separate rate. |
| Claude Managed Agents session runtime | $0.08 per session-hour; not the Messages API. |
| Claude Platform on AWS and Microsoft Foundry | Billed in CCUs at the same USD per-model rates (US Data Zone = 1.1x); not separate `anthropic:` identities. |
| Amazon Bedrock and Google Cloud direct | Partner-operated regional pricing under their own providers. |
| US geography bases | Excluded by the existing convention (see §1 for exact 1.1x values). |

## 6. Conflicts and uncertainties

- **C1. Package OpenRouter source drops `input_cache_write_1h` and `overrides`.** `OpenRouterPricingSource::RATE_MAPPING` maps `input_cache_write` (the 5-minute rate) to generic `cache_write_input_tokens` and ignores the 1h field and `overrides`. For `anthropic/claude-fable-5.1`, which I call live catalog sufficient on token rates, an aggregate cache-write count that includes 1-hour writes bills at 12.50 instead of 20 per M, an underestimate. OpenRouter usage reports only an aggregate `cache_write_tokens`. The catalog does not conflict with Anthropic; the package mapping loses information. Options: fix the source (map `input_cache_write_1h`, and decline to price aggregate writes for models that publish it), or also bind and block Fable 5.1. This is the writer's or maintainer's call; it also affects every `:batch` ID.
- **C2. Excluded OpenRouter Sonnet 4.5 and Sonnet 4 have `overrides` above 200k** that the live source ignores. Sonnet 4.5 also has 1.1x regional endpoints. Live quotes for long prompts underprice. Consider blocking `openrouter:anthropic/claude-sonnet-4.5` and `openrouter:anthropic/claude-sonnet-4` even though they are out of scope. OpenRouter also reports a 1M context for Sonnet 4.5 on most endpoints, while Anthropic's docs say Sonnet 4.5 has 200k.
- **C3. Fable and Mythos geography.** The data residency docs say "Claude 4.6 and later models" support `inference_geo` at 1.1x and name only Opus 4.5, Sonnet 4.5 and Haiku 4.5 and earlier as unsupported. Fable 5/5.1 and Mythos 5/5.1 are not named explicitly. I treated them as later models: global basis plus blocked generic. That is the safe side; if they lacked US geography, the generic would merely be unavailable.
- **C4. Mythos 5 and 5.1 are limited access.** Anthropic's pricing page lists them with prices; only verified organizations can call them. I included them. Drop them if the writer prefers generally available models only.
- **C5. Haiku 5.5 threshold boundary on OpenRouter.** Anthropic bills the higher tier for prompts *over* 100,000 tokens; OpenRouter's override uses `min_prompt_tokens: 100000`. The OpenRouter bases therefore switch to `-standard-long-max` at ≥ 100,000, which is conservative at exactly 100,000. Native bases keep Anthropic's "at most 100,000" rule.
- **C6. OpenRouter cache writes are TTL-only in the bound bases.** OpenRouter reports an aggregate cache-write count without a TTL split, so on these bases a cached-write request quotes partial unless the caller supplies the split. That matches the README rule for Anthropic bases. Alternative: also publish a generic `cache_write_input_tokens` at the 1h rate as a worst-case ceiling, which keeps quotes complete but overstates 5-minute writes by 60%. I chose fail-closed.
- **C7. Web search mapping.** The package has a `web_searches` unit, but no adapter maps Anthropic's `usage.server_tool_use.web_search_requests`; callers must pass it. On OpenRouter, Anthropic's native search is unavailable on Bedrock endpoints, so an `auto` search served there may run Exa, a distinct unpriced unit. The notes restrict `web_searches` to the native engine, as the Gemini precedent does. OpenRouter's native-search list names "Claude 4 and later (all Opus/Sonnet variants)" and not Haiku, so Haiku search support through OpenRouter is unverified. The rate is harmless when the count is zero.
- **C8. Imminent legacy retirements.** Haiku 4.5 retires no sooner than 2026-10-15, and Opus 4.5 no sooner than 2026-11-24. Re-check before release; on retirement, remove the entries and aliases.
- **C9. OpenRouter dated canonical slugs** (for example `anthropic/claude-opus-5.5-20260921`) are not blocked, following the Gemini precedent. The live source matches only `id`. If an application reports the canonical slug, it falls through to the snapshot (unpriced) and then Portkey.
- **C10. Doc inconsistency on fast mode.** OpenRouter's Claude Code guide says fast mode exists on Opus 4.6, 4.7, 4.8 and 5 and omits 5.5. Anthropic's fast-mode page and the live endpoint list (`anthropic/fast` on 5.5, 5 and 4.8 only) agree with each other. This does not affect the standard bases.
- **C11. Pricing-page cache-read wording.** The prompt-caching section says "A cache hit costs 10% of the standard input price" generally, with explicit exceptions (0.025x Fable 5.1/Mythos 5.1, 0.05x Opus 5.5/Sonnet 5.5). The model table, model pages and OpenRouter all agree on per-model values, so there is no conflict in the rates used.
