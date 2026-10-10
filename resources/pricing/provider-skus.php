<?php

declare(strict_types=1);

/*
 * Reviewed package-owned snapshot for providers without a suitable stable,
 * machine-readable runtime catalog. See .agents/skills/fetching-provider-pricing
 * before changing rates, units, identities, or provenance.
 */
return [
    'version' => 12,
    'retrieved_at' => '2026-10-10T01:47:24+00:00',
    'effective_at' => null,
    'currency' => 'USD',
    'fallback_blocked' => [
        // Checked 2026-10-09 against https://platform.claude.com/docs/en/about-claude/pricing
        // and https://platform.claude.com/docs/en/about-claude/models/overview.
        // Generic identities cannot distinguish geography, fast mode or Haiku's
        // prompt-length tier. The qualified entries below are billing bases,
        // not provider model IDs or aliases; callers must enforce their scope.
        'anthropic:claude-opus-5-5',
        'anthropic:claude-sonnet-5-5',
        'anthropic:claude-haiku-5-5',
        // Checked 2026-10-09 against https://developers.openai.com/api/docs/pricing
        // and https://developers.openai.com/api/docs/guides/decisions.
        // Generic model/API identities are unavailable because context, regional
        // processing and service tier change rates; Decisions is separately billed.
        'openai:gpt-6.1-sol',
        'openai:gpt-6-luna',
        'openai:decisions',
        // Checked 2026-10-09 against https://api-docs.deepseek.com/quick_start/pricing.
        // No generic V4 SKU is documented. Pro is time-tiered; retired Flash names
        // now run V4.1-Flash. Do not price them as the retired V4-Flash model.
        'deepseek:deepseek-v4',
        'deepseek:deepseek-v4-pro',
        'deepseek:deepseek-v4-flash',
        'deepseek:deepseek-v4-flash-vision-exp',
        'anthropic:claude-sonnet-5',
        'anthropic:claude-sonnet-5-us',
        // Checked 2026-10-10 against https://platform.claude.com/docs/en/about-claude/pricing
        // and https://platform.claude.com/docs/en/manage-claude/data-residency. Claude 4.6
        // and later models, including Fable and Mythos, bill inference_geo us at 1.1x,
        // and Opus 5 and Opus 4.8 also have fast mode, so their generic identities
        // cannot select a rate. Use the -global[-standard] bases.
        'anthropic:claude-fable-5-1',
        'anthropic:claude-mythos-5-1',
        'anthropic:claude-fable-5',
        'anthropic:claude-mythos-5',
        'anthropic:claude-opus-5',
        'anthropic:claude-opus-4-8',
        'anthropic:claude-opus-4-7',
        'anthropic:claude-opus-4-6',
        'anthropic:claude-sonnet-4-6',
        'openrouter:openai/gpt-5.6-terra-272k',
        'openrouter:openai/gpt-6-luna-272k',
        'openrouter:google/gemini-3.1-flash-lite-standard-max',
        // Checked 2026-10-09 against
        // https://openrouter.ai/api/v1/models/google/gemini-3.1-flash-lite-20260507/endpoints.
        // Default routing spans endpoints at 0.25/1.50 and 0.275/1.65 per M, and
        // the live catalog's cache-write rate conflicts with OpenRouter's caching
        // guide, so the generic identity must not take the live catalog's
        // headline price. Use the -standard-max basis above.
        'openrouter:google/gemini-3.1-flash-lite',
        // Checked 2026-10-10 against https://openrouter.ai/api/v1/models/{canonical}/endpoints
        // for each Anthropic model. Default routing spans list-price global endpoints
        // and 1.1x regional Bedrock, Vertex and Azure endpoints (Haiku 5.5 also has a
        // 100k prompt tier the live source cannot apply), so the generic identities must
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
        // Checked 2026-10-10 against https://ai.google.dev/gemini-api/docs/pricing,
        // https://ai.google.dev/gemini-api/docs/deprecations and the flex/priority guides.
        // service_tier (standard, flex, priority) is a request field on the same model ID,
        // the Pro models are prompt-length tiered, and 3.8/3.6 Flash double their rates
        // on 2027-01-01T00:00Z, so generic native IDs cannot select a rate. Use the
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
        'gemini:gemini-flash-latest',
        'gemini:gemini-flash-lite-latest',
        'gemini:gemini-pro-latest',
        // Bound OpenRouter bases priced only by the snapshot (on the invariant-test allowlist).
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
        // OpenRouter source cannot apply. 2.5 Pro (and its retired preview ID) also leave
        // OpenRouter on 2026-10-20, so no basis is proposed for them.
        'openrouter:google/gemini-3.6-flash',
        'openrouter:google/gemini-3.5-flash-lite',
        'openrouter:google/gemini-3.5-flash',
        'openrouter:google/gemini-3.1-pro-preview',
        'openrouter:google/gemini-3.1-pro-preview-customtools',
        'openrouter:google/gemini-2.5-pro',
        'openrouter:google/gemini-2.5-pro-preview',
        // Checked 2026-10-10 against https://developers.openai.com/api/docs/pricing,
        // the model pages and https://developers.openai.com/api/docs/deprecations.
        // Generic and dated snapshot IDs cannot carry service tier (batch, flex,
        // fast, ultrafast), regional/FedRAMP uplift or the 272K context tier, so
        // only the qualified -standard bases are priced.
        'openai:gpt-6-astra',
        'openai:gpt-6-sol',
        'openai:gpt-5.6-sol',
        'openai:gpt-5.6-terra',
        'openai:gpt-5.6-luna',
        'openai:gpt-5.6-cyber',
        'openai:gpt-5.5',
        'openai:gpt-5.5-pro',
        'openai:gpt-5.4',
        'openai:gpt-5.4-pro',
        'openai:gpt-5.4-mini',
        'openai:gpt-5.2',
        'openai:gpt-5.2-pro',
        'openai:gpt-4.1',
        'openai:gpt-4.1-mini',
        'openai:gpt-4o',
        'openai:gpt-4o-mini',
        'openai:chat-latest',
        'openai:gpt-rosalind-research',
        // Moving aliases to tiered models (blue -> gpt-5.6-sol, red -> gpt-5.6-cyber);
        // an alias may not target a fallback-blocked identity.
        'openai:gpt-daybreak-blue-latest',
        'openai:gpt-daybreak-red-latest',
        // Search model: per-search fee undocumented; a token-only quote could be
        // complete but low. Deliberately unpriced.
        'openai:gpt-5-search-api',
        // Dated snapshots of the models above. fallback_blocked matches exact keys,
        // so a response reporting a dated ID must not fall through to Portkey.
        'openai:gpt-5.5-2026-04-23',
        'openai:gpt-5.5-pro-2026-04-23',
        'openai:gpt-5.4-2026-03-05',
        'openai:gpt-5.4-pro-2026-03-05',
        'openai:gpt-5.4-mini-2026-03-17',
        'openai:gpt-5.2-2025-12-11',
        'openai:gpt-5.2-pro-2025-12-11',
        'openai:gpt-4.1-2025-04-14',
        'openai:gpt-4.1-mini-2025-04-14',
        'openai:gpt-4o-2024-08-06',
        'openai:gpt-4o-2024-11-20',
        'openai:gpt-4o-mini-2024-07-18',
        // Checked 2026-10-10 against https://openrouter.ai/api/v1/models/{canonical}/endpoints.
        // Default routing load-balances base (openai, azure) and regional 1.1x
        // (azure/us, azure/eu, azure/swedencentral, amazon-bedrock/*) endpoints,
        // and openai/gpt-5.6-sol's openai endpoint carries a 50% discount, so the
        // live catalog's headline rate is not a safe price. Use the -standard-max bases.
        'openrouter:openai/gpt-6.1-sol',
        'openrouter:openai/gpt-6.1-sol-pro',
        'openrouter:openai/gpt-6-astra',
        'openrouter:openai/gpt-6-astra-pro',
        'openrouter:openai/gpt-6-sol',
        'openrouter:openai/gpt-6-sol-pro',
        'openrouter:openai/gpt-6-luna',
        'openrouter:openai/gpt-6-luna-pro',
        'openrouter:openai/gpt-5.6-sol',
        'openrouter:openai/gpt-5.6-sol-pro',
        'openrouter:openai/gpt-5.6-terra',
        'openrouter:openai/gpt-5.6-terra-pro',
        'openrouter:openai/gpt-5.6-luna',
        'openrouter:openai/gpt-5.6-luna-pro',
        'openrouter:openai/gpt-5.5',
        'openrouter:openai/gpt-5.4',
        'openrouter:openai/gpt-5.4-mini',
        'openrouter:openai/gpt-4.1',
        'openrouter:openai/gpt-4.1-mini',
        'openrouter:openai/gpt-4o-mini',
        // Bound bases priced only by the snapshot (on the invariant-test allowlist).
        'openrouter:openai/gpt-6.1-sol-standard-max-short',
        'openrouter:openai/gpt-6.1-sol-standard-max-long',
        'openrouter:openai/gpt-6.1-sol-pro-standard-max-short',
        'openrouter:openai/gpt-6.1-sol-pro-standard-max-long',
        'openrouter:openai/gpt-6-astra-standard-max-short',
        'openrouter:openai/gpt-6-astra-standard-max-long',
        'openrouter:openai/gpt-6-astra-pro-standard-max-short',
        'openrouter:openai/gpt-6-astra-pro-standard-max-long',
        'openrouter:openai/gpt-6-sol-standard-max-short',
        'openrouter:openai/gpt-6-sol-standard-max-long',
        'openrouter:openai/gpt-6-sol-pro-standard-max-short',
        'openrouter:openai/gpt-6-sol-pro-standard-max-long',
        'openrouter:openai/gpt-6-luna-standard-max-short',
        'openrouter:openai/gpt-6-luna-standard-max-long',
        'openrouter:openai/gpt-6-luna-pro-standard-max-short',
        'openrouter:openai/gpt-6-luna-pro-standard-max-long',
        'openrouter:openai/gpt-5.6-sol-standard-max-short',
        'openrouter:openai/gpt-5.6-sol-standard-max-long',
        'openrouter:openai/gpt-5.6-sol-pro-standard-max-short',
        'openrouter:openai/gpt-5.6-sol-pro-standard-max-long',
        'openrouter:openai/gpt-5.6-terra-standard-max-short',
        'openrouter:openai/gpt-5.6-terra-standard-max-long',
        'openrouter:openai/gpt-5.6-terra-pro-standard-max-short',
        'openrouter:openai/gpt-5.6-terra-pro-standard-max-long',
        'openrouter:openai/gpt-5.6-luna-standard-max-short',
        'openrouter:openai/gpt-5.6-luna-standard-max-long',
        'openrouter:openai/gpt-5.6-luna-pro-standard-max-short',
        'openrouter:openai/gpt-5.6-luna-pro-standard-max-long',
        'openrouter:openai/gpt-5.5-standard-max-short',
        'openrouter:openai/gpt-5.5-standard-max-long',
        'openrouter:openai/gpt-5.4-standard-max-short',
        'openrouter:openai/gpt-5.4-standard-max-long',
        'openrouter:openai/gpt-5.4-mini-standard-max',
        'openrouter:openai/gpt-4.1-standard-max',
        'openrouter:openai/gpt-4.1-mini-standard-max',
        'openrouter:openai/gpt-4o-mini-standard-max',
        // Open-weight models served by many third-party hosts whose default routing
        // spans roughly a 10x price range; no bound basis is published.
        'openrouter:openai/gpt-oss-120b',
        'openrouter:openai/gpt-oss-20b',
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
        // Bound bases priced only by the snapshot (on the invariant-test allowlist).
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
        'you:research-frontier',
        'searchapi:search',
        'tavily:search',
        'jina:search',
        'serpapi:search',
        'serpbase:search',
        'serpbase:news',
        'gemini:deep-research',
        'parallel:search',
        'valyu:search',
        'firecrawl:search',
    ],
    /*
     * Provider-documented moving aliases that currently resolve to a reviewed
     * versioned identity. The alias keeps its own public identity; only the
     * rate lookup follows the target. Re-review whenever the provider moves an
     * alias, because a completed response reports the versioned model it ran.
     */
    'aliases' => [
        'typesafe:jev-latest' => 'typesafe:jev-1.13.0',
        'typesafe:jev-preview' => 'typesafe:jev-1.13.0',
        'anthropic:claude-opus-4-5' => 'anthropic:claude-opus-4-5-20251101',
        'anthropic:claude-haiku-4-5' => 'anthropic:claude-haiku-4-5-20251001',
    ],
    'prices' => [
        'anthropic:claude-opus-5-5-global-standard' => [
            'source' => 'https://platform.claude.com/docs/en/about-claude/pricing',
            'notes' => 'Checked 2026-10-09. Billing basis for claude-opus-5-5, global geography, standard speed, synchronous Messages API only; not a provider alias. All context lengths. Excludes fast mode, US geography (1.1x), batch and server tools. Cache input partitions are exclusive; write units distinguish 5-minute and 1-hour TTLs.',
            'rates' => [
                'input_tokens' => ['amount' => '4', 'per' => '1000000'],
                'output_tokens' => ['amount' => '20', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.20', 'per' => '1000000'],
                'cache_write_input_tokens_5m' => ['amount' => '5', 'per' => '1000000'],
                'cache_write_input_tokens_1h' => ['amount' => '8', 'per' => '1000000'],
            ],
        ],
        'anthropic:claude-sonnet-5-5-global-standard' => [
            'source' => 'https://platform.claude.com/docs/en/about-claude/pricing',
            'notes' => 'Checked 2026-10-09. Billing basis for claude-sonnet-5-5, global geography, synchronous Messages API only; not a provider alias. All context lengths. Excludes US geography (1.1x), batch and server tools. Cache input partitions are exclusive; write units distinguish 5-minute and 1-hour TTLs.',
            'rates' => [
                'input_tokens' => ['amount' => '2', 'per' => '1000000'],
                'output_tokens' => ['amount' => '10', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.10', 'per' => '1000000'],
                'cache_write_input_tokens_5m' => ['amount' => '2.50', 'per' => '1000000'],
                'cache_write_input_tokens_1h' => ['amount' => '4', 'per' => '1000000'],
            ],
        ],
        'anthropic:claude-haiku-5-5-global-short' => [
            'source' => 'https://platform.claude.com/docs/en/about-claude/pricing',
            'notes' => 'Checked 2026-10-09. Billing basis for claude-haiku-5-5 with global geography and at most 100,000 prompt tokens, including cache reads and writes. Synchronous Messages API only; not an alias. Excludes US geography (1.1x), batch and server tools. Cache input partitions are exclusive; TTL-specific writes are not interchangeable.',
            'rates' => [
                'input_tokens' => ['amount' => '0.10', 'per' => '1000000'],
                'output_tokens' => ['amount' => '0.50', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.01', 'per' => '1000000'],
                'cache_write_input_tokens_5m' => ['amount' => '0.125', 'per' => '1000000'],
                'cache_write_input_tokens_1h' => ['amount' => '0.20', 'per' => '1000000'],
            ],
        ],
        'anthropic:claude-haiku-5-5-global-long' => [
            'source' => 'https://platform.claude.com/docs/en/about-claude/pricing',
            'notes' => 'Checked 2026-10-09. Billing basis for claude-haiku-5-5 with global geography and more than 100,000 prompt tokens, including cache reads and writes. The higher tier applies to the entire request. Synchronous Messages API only; not an alias. Excludes US geography (1.1x), batch and server tools. Cache input partitions are exclusive; TTL-specific writes are not interchangeable.',
            'rates' => [
                'input_tokens' => ['amount' => '0.50', 'per' => '1000000'],
                'output_tokens' => ['amount' => '2.50', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.05', 'per' => '1000000'],
                'cache_write_input_tokens_5m' => ['amount' => '0.625', 'per' => '1000000'],
                'cache_write_input_tokens_1h' => ['amount' => '1', 'per' => '1000000'],
            ],
        ],
        'anthropic:claude-fable-5-1-global' => [
            'source' => 'https://platform.claude.com/docs/en/about-claude/pricing',
            'notes' => 'Checked 2026-10-10. Billing basis for claude-fable-5-1 (Claude Fable 5.1) with global geography (inference_geo global, the default), synchronous Messages API only; not a provider alias. All context lengths up to 1M tokens bill at one rate. No fast mode or prompt-length tier. Cache reads are 0.025x input. Excludes US geography (inference_geo us bills 1.1x on every token category), batch, Priority Tier, Bedrock, Google Cloud and Foundry. Thinking is output usage. Cache input partitions are exclusive; write units distinguish 5-minute and 1-hour TTLs, and no generic cache-write rate is published. web_searches counts usage.server_tool_use.web_search_requests at USD 10 per 1,000 searches (failed searches are not billed; the adapters map it from non-streamed responses). Code execution, web fetch tokens and other server tools are not priced here.',
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
            'notes' => 'Checked 2026-10-10. Billing basis for claude-mythos-5-1 (Claude Mythos 5.1) with global geography (inference_geo global, the default), synchronous Messages API only; not a provider alias. All context lengths up to 1M tokens bill at one rate. Limited access: only organizations verified through an Anthropic verification program can call it; same rates as Claude Fable 5.1. No fast mode or prompt-length tier. Cache reads are 0.025x input. Excludes US geography (inference_geo us bills 1.1x on every token category), batch, Priority Tier, Bedrock, Google Cloud and Foundry. Thinking is output usage. Cache input partitions are exclusive; write units distinguish 5-minute and 1-hour TTLs, and no generic cache-write rate is published. web_searches counts usage.server_tool_use.web_search_requests at USD 10 per 1,000 searches (failed searches are not billed; the adapters map it from non-streamed responses). Code execution, web fetch tokens and other server tools are not priced here.',
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
            'notes' => 'Checked 2026-10-10. Billing basis for claude-fable-5 (Claude Fable 5, legacy) with global geography (inference_geo global, the default), synchronous Messages API only; not a provider alias. All context lengths up to 1M tokens bill at one rate. No fast mode or prompt-length tier. Cache reads are 0.1x input, unlike Fable 5.1. Excludes US geography (inference_geo us bills 1.1x on every token category), batch, Priority Tier, Bedrock, Google Cloud and Foundry. Thinking is output usage. Cache input partitions are exclusive; write units distinguish 5-minute and 1-hour TTLs, and no generic cache-write rate is published. web_searches counts usage.server_tool_use.web_search_requests at USD 10 per 1,000 searches (failed searches are not billed; the adapters map it from non-streamed responses). Code execution, web fetch tokens and other server tools are not priced here.',
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
            'notes' => 'Checked 2026-10-10. Billing basis for claude-mythos-5 (Claude Mythos 5, legacy) with global geography (inference_geo global, the default), synchronous Messages API only; not a provider alias. All context lengths up to 1M tokens bill at one rate. Limited access: only organizations verified through an Anthropic verification program can call it; same rates as Claude Fable 5. No fast mode or prompt-length tier. Excludes US geography (inference_geo us bills 1.1x on every token category), batch, Priority Tier, Bedrock, Google Cloud and Foundry. Thinking is output usage. Cache input partitions are exclusive; write units distinguish 5-minute and 1-hour TTLs, and no generic cache-write rate is published. web_searches counts usage.server_tool_use.web_search_requests at USD 10 per 1,000 searches (failed searches are not billed; the adapters map it from non-streamed responses). Code execution, web fetch tokens and other server tools are not priced here.',
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
            'notes' => 'Checked 2026-10-10. Billing basis for claude-opus-5 (Claude Opus 5, legacy) with global geography (inference_geo global, the default), synchronous Messages API only; not a provider alias. All context lengths up to 1M tokens bill at one rate. Standard speed only: fast mode (speed fast, usage.speed fast) bills USD 10/M input and 50/M output and is excluded. Excludes US geography (inference_geo us bills 1.1x on every token category), batch, Priority Tier, Bedrock, Google Cloud and Foundry. Thinking is output usage. Cache input partitions are exclusive; write units distinguish 5-minute and 1-hour TTLs, and no generic cache-write rate is published. web_searches counts usage.server_tool_use.web_search_requests at USD 10 per 1,000 searches (failed searches are not billed; the adapters map it from non-streamed responses). Code execution, web fetch tokens and other server tools are not priced here.',
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
            'notes' => 'Checked 2026-10-10. Billing basis for claude-opus-4-8 (Claude Opus 4.8, legacy) with global geography (inference_geo global, the default), synchronous Messages API only; not a provider alias. All context lengths up to 1M tokens bill at one rate. Standard speed only: fast mode (speed fast, usage.speed fast) bills USD 10/M input and 50/M output and is excluded. Excludes US geography (inference_geo us bills 1.1x on every token category), batch, Priority Tier, Bedrock, Google Cloud and Foundry. Thinking is output usage. Cache input partitions are exclusive; write units distinguish 5-minute and 1-hour TTLs, and no generic cache-write rate is published. web_searches counts usage.server_tool_use.web_search_requests at USD 10 per 1,000 searches (failed searches are not billed; the adapters map it from non-streamed responses). Code execution, web fetch tokens and other server tools are not priced here.',
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
            'notes' => 'Checked 2026-10-10. Billing basis for claude-opus-4-7 (Claude Opus 4.7, legacy) with global geography (inference_geo global, the default), synchronous Messages API only; not a provider alias. All context lengths up to 1M tokens bill at one rate. No fast mode: speed fast returns an error. No prompt-length tier. Excludes US geography (inference_geo us bills 1.1x on every token category), batch, Priority Tier, Bedrock, Google Cloud and Foundry. Thinking is output usage. Cache input partitions are exclusive; write units distinguish 5-minute and 1-hour TTLs, and no generic cache-write rate is published. web_searches counts usage.server_tool_use.web_search_requests at USD 10 per 1,000 searches (failed searches are not billed; the adapters map it from non-streamed responses). Code execution, web fetch tokens and other server tools are not priced here.',
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
            'notes' => 'Checked 2026-10-10. Billing basis for claude-opus-4-6 (Claude Opus 4.6, legacy) with global geography (inference_geo global, the default), synchronous Messages API only; not a provider alias. All context lengths up to 1M tokens bill at one rate. No fast mode: a speed fast request runs and bills at standard rates and reports usage.speed standard. No prompt-length tier. Excludes US geography (inference_geo us bills 1.1x on every token category), batch, Priority Tier, Bedrock, Google Cloud and Foundry. Thinking is output usage. Cache input partitions are exclusive; write units distinguish 5-minute and 1-hour TTLs, and no generic cache-write rate is published. web_searches counts usage.server_tool_use.web_search_requests at USD 10 per 1,000 searches (failed searches are not billed; the adapters map it from non-streamed responses). Code execution, web fetch tokens and other server tools are not priced here.',
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
            'notes' => 'Checked 2026-10-10. Billing basis for claude-sonnet-4-6 (Claude Sonnet 4.6, legacy) with global geography (inference_geo global, the default), synchronous Messages API only; not a provider alias. All context lengths up to 1M tokens bill at one rate. No fast mode or prompt-length tier. Excludes US geography (inference_geo us bills 1.1x on every token category), batch, Priority Tier, Bedrock, Google Cloud and Foundry. Thinking is output usage. Cache input partitions are exclusive; write units distinguish 5-minute and 1-hour TTLs, and no generic cache-write rate is published. web_searches counts usage.server_tool_use.web_search_requests at USD 10 per 1,000 searches (failed searches are not billed; the adapters map it from non-streamed responses). Code execution, web fetch tokens and other server tools are not priced here.',
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
            'notes' => 'Checked 2026-10-10. Claude API pinned snapshot claude-opus-4-5-20251101 (Claude Opus 4.5); the documented alias claude-opus-4-5 resolves to it. Synchronous Messages API. Legacy model, retirement not sooner than 2026-11-24 per https://platform.claude.com/docs/en/about-claude/model-deprecations. It does not accept inference_geo (requests with it return 400) and has no fast mode, and its 200k context window has no long-context tier, so the Claude API has one price for it. Excludes batch, Priority Tier and partner-cloud regional pricing (Bedrock and Google Cloud bill regional endpoints 1.1x under their own provider identities). Thinking is output usage. Cache input partitions are exclusive; write units distinguish 5-minute and 1-hour TTLs, and no generic cache-write rate is published. web_searches counts usage.server_tool_use.web_search_requests at USD 10 per 1,000 searches (failed searches are not billed; the adapters map it from non-streamed responses). Code execution, web fetch tokens and other server tools are not priced here.',
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
            'notes' => 'Checked 2026-10-10. Claude API pinned snapshot claude-haiku-4-5-20251001 (Claude Haiku 4.5); the documented alias claude-haiku-4-5 resolves to it. Synchronous Messages API. Legacy model, retirement not sooner than 2026-10-15 per https://platform.claude.com/docs/en/about-claude/model-deprecations. It does not accept inference_geo (requests with it return 400) and has no fast mode, and its 200k context window has no long-context tier, so the Claude API has one price for it. Excludes batch, Priority Tier and partner-cloud regional pricing (Bedrock and Google Cloud bill regional endpoints 1.1x under their own provider identities). Thinking is output usage. Cache input partitions are exclusive; write units distinguish 5-minute and 1-hour TTLs, and no generic cache-write rate is published. web_searches counts usage.server_tool_use.web_search_requests at USD 10 per 1,000 searches (failed searches are not billed; the adapters map it from non-streamed responses). Code execution, web fetch tokens and other server tools are not priced here.',
            'rates' => [
                'input_tokens' => ['amount' => '1', 'per' => '1000000'],
                'output_tokens' => ['amount' => '5', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.10', 'per' => '1000000'],
                'cache_write_input_tokens_5m' => ['amount' => '1.25', 'per' => '1000000'],
                'cache_write_input_tokens_1h' => ['amount' => '2', 'per' => '1000000'],
                'web_searches' => ['amount' => '10', 'per' => '1000'],
            ],
        ],
        'openai:gpt-6.1-sol-standard-short' => [
            'source' => 'https://developers.openai.com/api/docs/pricing',
            'notes' => 'Checked 2026-10-09. Direct OpenAI billing basis for gpt-6.1-sol, standard service tier, at most 272,000 input tokens; not an alias. Excludes regional/FedRAMP 10% uplift, batch, flex, fast, ultrafast and tools. Reasoning is output usage; input cache partitions are exclusive.',
            'rates' => [
                'input_tokens' => ['amount' => '2', 'per' => '1000000'],
                'output_tokens' => ['amount' => '10', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.10', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '2.50', 'per' => '1000000'],
            ],
        ],
        'openai:gpt-6.1-sol-standard-long' => [
            'source' => 'https://developers.openai.com/api/docs/pricing',
            'notes' => 'Checked 2026-10-09. Direct OpenAI billing basis for gpt-6.1-sol, standard service tier, more than 272,000 input tokens; not an alias. Excludes regional/FedRAMP 10% uplift, batch, flex, fast, ultrafast and tools. Reasoning is output usage; input cache partitions are exclusive.',
            'rates' => [
                'input_tokens' => ['amount' => '4', 'per' => '1000000'],
                'output_tokens' => ['amount' => '15', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.20', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '5', 'per' => '1000000'],
            ],
        ],
        'openai:gpt-6-luna-standard-short' => [
            'source' => 'https://developers.openai.com/api/docs/pricing',
            'notes' => 'Checked 2026-10-09. Direct OpenAI billing basis for gpt-6-luna generation, standard service tier, at most 272,000 input tokens; not an alias. Not Decisions. Excludes regional/FedRAMP 10% uplift, batch, flex, fast and tools. Reasoning is output usage; input cache partitions are exclusive.',
            'rates' => [
                'input_tokens' => ['amount' => '0.10', 'per' => '1000000'],
                'output_tokens' => ['amount' => '0.50', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.01', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '0.125', 'per' => '1000000'],
            ],
        ],
        'openai:gpt-6-luna-standard-long' => [
            'source' => 'https://developers.openai.com/api/docs/pricing',
            'notes' => 'Checked 2026-10-09. Direct OpenAI billing basis for gpt-6-luna generation, standard service tier, more than 272,000 input tokens; not an alias. Not Decisions. Excludes regional/FedRAMP 10% uplift, batch, flex, fast and tools. Reasoning is output usage; input cache partitions are exclusive.',
            'rates' => [
                'input_tokens' => ['amount' => '0.20', 'per' => '1000000'],
                'output_tokens' => ['amount' => '0.75', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.02', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '0.25', 'per' => '1000000'],
            ],
        ],
        'openai:decisions-gpt-6-luna-short' => [
            'source' => 'https://developers.openai.com/api/docs/guides/decisions',
            'notes' => 'Checked 2026-10-09. Public beta POST /v1/decisions with gpt-6-luna: input only, USD 0.10/M; no cache-read, cache-write or output-token charges. Billing basis, not a model alias. At most 272,000 input tokens per https://developers.openai.com/api/docs/pricing. Excludes regional processing premium and long-context multiplier. Do not reuse generation prices for this endpoint.',
            'rates' => [
                'input_tokens' => ['amount' => '0.10', 'per' => '1000000'],
                'output_tokens' => ['amount' => '0', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '0', 'per' => '1000000'],
            ],
        ],
        'openai:decisions-gpt-6-luna-long' => [
            'source' => 'https://developers.openai.com/api/docs/guides/decisions',
            'notes' => 'Checked 2026-10-09. Public beta POST /v1/decisions with gpt-6-luna: input-only billing with the long-context 2x input multiplier (more than 272,000 input tokens) documented at https://developers.openai.com/api/docs/pricing. No cache-read, cache-write or output-token charges. Billing basis, not a model alias. Excludes regional processing premium.',
            'rates' => [
                'input_tokens' => ['amount' => '0.20', 'per' => '1000000'],
                'output_tokens' => ['amount' => '0', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '0', 'per' => '1000000'],
            ],
        ],
        'openai:gpt-6-astra-standard-short' => [
            'source' => 'https://developers.openai.com/api/docs/pricing',
            'notes' => 'Checked 2026-10-10. Direct OpenAI billing basis for gpt-6-astra (snapshot gpt-6-astra), standard service tier, at most 272,000 input tokens; not an alias. Excludes batch, flex, fast (formerly priority), ultrafast, regional processing and FedRAMP endpoints (10% uplift for models released on or after 2026-03-05), built-in tools other than web search. Reasoning is output usage. Cache writes bill at 1.25x and cache reads at 0.1x the applicable uncached input rate; input partitions (uncached, cached, cache-write) are exclusive. web_searches counts web_search_call items whose action is search (open_page and find_in_page are not billed) at USD 10 per 1,000 for both web_search and web_search_preview on reasoning models (https://developers.openai.com/api/docs/guides/tools-web-search); search content tokens bill at the model token rates inside input usage. The adapters do not count OpenAI web searches yet, because OpenAI usage reports no search count, so callers must pass web_searches or the searches go unbilled. Includes reasoning.mode pro, which OpenAI bills at the standard token rates.',
            'rates' => [
                'input_tokens' => ['amount' => '10', 'per' => '1000000'],
                'output_tokens' => ['amount' => '50', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '1', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '12.50', 'per' => '1000000'],
                'web_searches' => ['amount' => '10', 'per' => '1000'],
            ],
        ],
        'openai:gpt-6-astra-standard-long' => [
            'source' => 'https://developers.openai.com/api/docs/pricing',
            'notes' => 'Checked 2026-10-10. Direct OpenAI billing basis for gpt-6-astra (snapshot gpt-6-astra), standard service tier, more than 272,000 input tokens; the long-context rates apply to the full request; not an alias. Excludes batch, flex, fast (formerly priority), ultrafast, regional processing and FedRAMP endpoints (10% uplift for models released on or after 2026-03-05), built-in tools other than web search. Reasoning is output usage. Cache writes bill at 1.25x and cache reads at 0.1x the applicable uncached input rate; input partitions (uncached, cached, cache-write) are exclusive. web_searches counts web_search_call items whose action is search (open_page and find_in_page are not billed) at USD 10 per 1,000 for both web_search and web_search_preview on reasoning models (https://developers.openai.com/api/docs/guides/tools-web-search); search content tokens bill at the model token rates inside input usage. The adapters do not count OpenAI web searches yet, because OpenAI usage reports no search count, so callers must pass web_searches or the searches go unbilled. Includes reasoning.mode pro, which OpenAI bills at the standard token rates.',
            'rates' => [
                'input_tokens' => ['amount' => '20', 'per' => '1000000'],
                'output_tokens' => ['amount' => '75', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '2', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '25', 'per' => '1000000'],
                'web_searches' => ['amount' => '10', 'per' => '1000'],
            ],
        ],
        'openai:gpt-6-sol-standard-short' => [
            'source' => 'https://developers.openai.com/api/docs/pricing',
            'notes' => 'Checked 2026-10-10. Direct OpenAI billing basis for gpt-6-sol (snapshot gpt-6-sol), standard service tier, at most 272,000 input tokens; not an alias. Excludes batch, flex, fast (formerly priority), regional processing and FedRAMP endpoints (10% uplift for models released on or after 2026-03-05), built-in tools other than web search. Reasoning is output usage. Cache writes bill at 1.25x and cache reads at 0.1x the applicable uncached input rate; input partitions (uncached, cached, cache-write) are exclusive. web_searches counts web_search_call items whose action is search (open_page and find_in_page are not billed) at USD 10 per 1,000 for both web_search and web_search_preview on reasoning models (https://developers.openai.com/api/docs/guides/tools-web-search); search content tokens bill at the model token rates inside input usage. The adapters do not count OpenAI web searches yet, because OpenAI usage reports no search count, so callers must pass web_searches or the searches go unbilled. Includes reasoning.mode pro, which OpenAI bills at the standard token rates.',
            'rates' => [
                'input_tokens' => ['amount' => '2', 'per' => '1000000'],
                'output_tokens' => ['amount' => '10', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.20', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '2.50', 'per' => '1000000'],
                'web_searches' => ['amount' => '10', 'per' => '1000'],
            ],
        ],
        'openai:gpt-6-sol-standard-long' => [
            'source' => 'https://developers.openai.com/api/docs/pricing',
            'notes' => 'Checked 2026-10-10. Direct OpenAI billing basis for gpt-6-sol (snapshot gpt-6-sol), standard service tier, more than 272,000 input tokens; the long-context rates apply to the full request; not an alias. Excludes batch, flex, fast (formerly priority), regional processing and FedRAMP endpoints (10% uplift for models released on or after 2026-03-05), built-in tools other than web search. Reasoning is output usage. Cache writes bill at 1.25x and cache reads at 0.1x the applicable uncached input rate; input partitions (uncached, cached, cache-write) are exclusive. web_searches counts web_search_call items whose action is search (open_page and find_in_page are not billed) at USD 10 per 1,000 for both web_search and web_search_preview on reasoning models (https://developers.openai.com/api/docs/guides/tools-web-search); search content tokens bill at the model token rates inside input usage. The adapters do not count OpenAI web searches yet, because OpenAI usage reports no search count, so callers must pass web_searches or the searches go unbilled. Includes reasoning.mode pro, which OpenAI bills at the standard token rates.',
            'rates' => [
                'input_tokens' => ['amount' => '4', 'per' => '1000000'],
                'output_tokens' => ['amount' => '15', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.40', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '5', 'per' => '1000000'],
                'web_searches' => ['amount' => '10', 'per' => '1000'],
            ],
        ],
        'openai:gpt-5.6-sol-standard-short' => [
            'source' => 'https://developers.openai.com/api/docs/pricing',
            'notes' => 'Checked 2026-10-10. Direct OpenAI billing basis for gpt-5.6-sol (snapshot gpt-5.6-sol), standard service tier, at most 272,000 input tokens; not an alias. Excludes batch, flex, fast (formerly priority), regional processing and FedRAMP endpoints (10% uplift for models released on or after 2026-03-05), built-in tools other than web search. Reasoning is output usage. Cache writes bill at 1.25x and cache reads at 0.1x the applicable uncached input rate; input partitions (uncached, cached, cache-write) are exclusive. web_searches counts web_search_call items whose action is search (open_page and find_in_page are not billed) at USD 10 per 1,000 for both web_search and web_search_preview on reasoning models (https://developers.openai.com/api/docs/guides/tools-web-search); search content tokens bill at the model token rates inside input usage. The adapters do not count OpenAI web searches yet, because OpenAI usage reports no search count, so callers must pass web_searches or the searches go unbilled. Promotional rates that OpenAI guarantees only through at least 2026-11-21 (described as a 20% input and 33% output reduction, implying a prior USD 5/M input and 30/M output); re-review on or before that date. Includes reasoning.mode pro, which bills at the standard token rates.',
            'rates' => [
                'input_tokens' => ['amount' => '4', 'per' => '1000000'],
                'output_tokens' => ['amount' => '20', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.40', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '5', 'per' => '1000000'],
                'web_searches' => ['amount' => '10', 'per' => '1000'],
            ],
        ],
        'openai:gpt-5.6-sol-standard-long' => [
            'source' => 'https://developers.openai.com/api/docs/pricing',
            'notes' => 'Checked 2026-10-10. Direct OpenAI billing basis for gpt-5.6-sol (snapshot gpt-5.6-sol), standard service tier, more than 272,000 input tokens; the long-context rates apply to the full request; not an alias. Excludes batch, flex, fast (formerly priority), regional processing and FedRAMP endpoints (10% uplift for models released on or after 2026-03-05), built-in tools other than web search. Reasoning is output usage. Cache writes bill at 1.25x and cache reads at 0.1x the applicable uncached input rate; input partitions (uncached, cached, cache-write) are exclusive. web_searches counts web_search_call items whose action is search (open_page and find_in_page are not billed) at USD 10 per 1,000 for both web_search and web_search_preview on reasoning models (https://developers.openai.com/api/docs/guides/tools-web-search); search content tokens bill at the model token rates inside input usage. The adapters do not count OpenAI web searches yet, because OpenAI usage reports no search count, so callers must pass web_searches or the searches go unbilled. Promotional rates that OpenAI guarantees only through at least 2026-11-21 (described as a 20% input and 33% output reduction, implying a prior USD 5/M input and 30/M output); re-review on or before that date. Includes reasoning.mode pro, which bills at the standard token rates.',
            'rates' => [
                'input_tokens' => ['amount' => '8', 'per' => '1000000'],
                'output_tokens' => ['amount' => '30', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.80', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '10', 'per' => '1000000'],
                'web_searches' => ['amount' => '10', 'per' => '1000'],
            ],
        ],
        'openai:gpt-5.6-terra-standard-short' => [
            'source' => 'https://developers.openai.com/api/docs/pricing',
            'notes' => 'Checked 2026-10-10. Direct OpenAI billing basis for gpt-5.6-terra (snapshot gpt-5.6-terra), standard service tier, at most 272,000 input tokens; not an alias. Excludes batch, flex, fast (formerly priority), regional processing and FedRAMP endpoints (10% uplift for models released on or after 2026-03-05), built-in tools other than web search. Reasoning is output usage. Cache writes bill at 1.25x and cache reads at 0.1x the applicable uncached input rate; input partitions (uncached, cached, cache-write) are exclusive. web_searches counts web_search_call items whose action is search (open_page and find_in_page are not billed) at USD 10 per 1,000 for both web_search and web_search_preview on reasoning models (https://developers.openai.com/api/docs/guides/tools-web-search); search content tokens bill at the model token rates inside input usage. The adapters do not count OpenAI web searches yet, because OpenAI usage reports no search count, so callers must pass web_searches or the searches go unbilled. Includes reasoning.mode pro, which OpenAI bills at the standard token rates.',
            'rates' => [
                'input_tokens' => ['amount' => '2', 'per' => '1000000'],
                'output_tokens' => ['amount' => '12', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.20', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '2.50', 'per' => '1000000'],
                'web_searches' => ['amount' => '10', 'per' => '1000'],
            ],
        ],
        'openai:gpt-5.6-terra-standard-long' => [
            'source' => 'https://developers.openai.com/api/docs/pricing',
            'notes' => 'Checked 2026-10-10. Direct OpenAI billing basis for gpt-5.6-terra (snapshot gpt-5.6-terra), standard service tier, more than 272,000 input tokens; the long-context rates apply to the full request; not an alias. Excludes batch, flex, fast (formerly priority), regional processing and FedRAMP endpoints (10% uplift for models released on or after 2026-03-05), built-in tools other than web search. Reasoning is output usage. Cache writes bill at 1.25x and cache reads at 0.1x the applicable uncached input rate; input partitions (uncached, cached, cache-write) are exclusive. web_searches counts web_search_call items whose action is search (open_page and find_in_page are not billed) at USD 10 per 1,000 for both web_search and web_search_preview on reasoning models (https://developers.openai.com/api/docs/guides/tools-web-search); search content tokens bill at the model token rates inside input usage. The adapters do not count OpenAI web searches yet, because OpenAI usage reports no search count, so callers must pass web_searches or the searches go unbilled. Includes reasoning.mode pro, which OpenAI bills at the standard token rates.',
            'rates' => [
                'input_tokens' => ['amount' => '4', 'per' => '1000000'],
                'output_tokens' => ['amount' => '18', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.40', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '5', 'per' => '1000000'],
                'web_searches' => ['amount' => '10', 'per' => '1000'],
            ],
        ],
        'openai:gpt-5.6-luna-standard-short' => [
            'source' => 'https://developers.openai.com/api/docs/pricing',
            'notes' => 'Checked 2026-10-10. Direct OpenAI billing basis for gpt-5.6-luna (snapshot gpt-5.6-luna), standard service tier, at most 272,000 input tokens; not an alias. Excludes batch, flex, fast (formerly priority), regional processing and FedRAMP endpoints (10% uplift for models released on or after 2026-03-05), built-in tools other than web search. Reasoning is output usage. Cache writes bill at 1.25x and cache reads at 0.1x the applicable uncached input rate; input partitions (uncached, cached, cache-write) are exclusive. web_searches counts web_search_call items whose action is search (open_page and find_in_page are not billed) at USD 10 per 1,000 for both web_search and web_search_preview on reasoning models (https://developers.openai.com/api/docs/guides/tools-web-search); search content tokens bill at the model token rates inside input usage. The adapters do not count OpenAI web searches yet, because OpenAI usage reports no search count, so callers must pass web_searches or the searches go unbilled. Includes reasoning.mode pro, which OpenAI bills at the standard token rates.',
            'rates' => [
                'input_tokens' => ['amount' => '0.20', 'per' => '1000000'],
                'output_tokens' => ['amount' => '1.20', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.02', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '0.25', 'per' => '1000000'],
                'web_searches' => ['amount' => '10', 'per' => '1000'],
            ],
        ],
        'openai:gpt-5.6-luna-standard-long' => [
            'source' => 'https://developers.openai.com/api/docs/pricing',
            'notes' => 'Checked 2026-10-10. Direct OpenAI billing basis for gpt-5.6-luna (snapshot gpt-5.6-luna), standard service tier, more than 272,000 input tokens; the long-context rates apply to the full request; not an alias. Excludes batch, flex, fast (formerly priority), regional processing and FedRAMP endpoints (10% uplift for models released on or after 2026-03-05), built-in tools other than web search. Reasoning is output usage. Cache writes bill at 1.25x and cache reads at 0.1x the applicable uncached input rate; input partitions (uncached, cached, cache-write) are exclusive. web_searches counts web_search_call items whose action is search (open_page and find_in_page are not billed) at USD 10 per 1,000 for both web_search and web_search_preview on reasoning models (https://developers.openai.com/api/docs/guides/tools-web-search); search content tokens bill at the model token rates inside input usage. The adapters do not count OpenAI web searches yet, because OpenAI usage reports no search count, so callers must pass web_searches or the searches go unbilled. Includes reasoning.mode pro, which OpenAI bills at the standard token rates.',
            'rates' => [
                'input_tokens' => ['amount' => '0.40', 'per' => '1000000'],
                'output_tokens' => ['amount' => '1.80', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.04', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '0.50', 'per' => '1000000'],
                'web_searches' => ['amount' => '10', 'per' => '1000'],
            ],
        ],
        'openai:gpt-5.5-standard-short' => [
            'source' => 'https://developers.openai.com/api/docs/pricing',
            'notes' => 'Checked 2026-10-10. Direct OpenAI billing basis for gpt-5.5 (snapshot gpt-5.5-2026-04-23), standard service tier, at most 272,000 input tokens; not an alias. Excludes batch, flex, fast (formerly priority), regional processing and FedRAMP endpoints (10% uplift for models released on or after 2026-03-05), built-in tools other than web search. Reasoning is output usage. OpenAI charges no additional cache-write fee before GPT-5.6, and input tokens bill at exactly one of the uncached, cached or cache-write rates (https://developers.openai.com/api/docs/guides/prompt-caching), so cache_write_input_tokens is priced at the uncached input rate; input partitions are exclusive. web_searches counts web_search_call items whose action is search (open_page and find_in_page are not billed) at USD 10 per 1,000 for both web_search and web_search_preview on reasoning models (https://developers.openai.com/api/docs/guides/tools-web-search); search content tokens bill at the model token rates inside input usage. The adapters do not count OpenAI web searches yet, because OpenAI usage reports no search count, so callers must pass web_searches or the searches go unbilled.',
            'rates' => [
                'input_tokens' => ['amount' => '5', 'per' => '1000000'],
                'output_tokens' => ['amount' => '30', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.50', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '5', 'per' => '1000000'],
                'web_searches' => ['amount' => '10', 'per' => '1000'],
            ],
        ],
        'openai:gpt-5.5-standard-long' => [
            'source' => 'https://developers.openai.com/api/docs/pricing',
            'notes' => 'Checked 2026-10-10. Direct OpenAI billing basis for gpt-5.5 (snapshot gpt-5.5-2026-04-23), standard service tier, more than 272,000 input tokens; the long-context rates apply to the full request; not an alias. Excludes batch, flex, fast (formerly priority), regional processing and FedRAMP endpoints (10% uplift for models released on or after 2026-03-05), built-in tools other than web search. Reasoning is output usage. OpenAI charges no additional cache-write fee before GPT-5.6, and input tokens bill at exactly one of the uncached, cached or cache-write rates (https://developers.openai.com/api/docs/guides/prompt-caching), so cache_write_input_tokens is priced at the uncached input rate; input partitions are exclusive. web_searches counts web_search_call items whose action is search (open_page and find_in_page are not billed) at USD 10 per 1,000 for both web_search and web_search_preview on reasoning models (https://developers.openai.com/api/docs/guides/tools-web-search); search content tokens bill at the model token rates inside input usage. The adapters do not count OpenAI web searches yet, because OpenAI usage reports no search count, so callers must pass web_searches or the searches go unbilled.',
            'rates' => [
                'input_tokens' => ['amount' => '10', 'per' => '1000000'],
                'output_tokens' => ['amount' => '45', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '1', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '10', 'per' => '1000000'],
                'web_searches' => ['amount' => '10', 'per' => '1000'],
            ],
        ],
        'openai:gpt-5.5-pro-standard-short' => [
            'source' => 'https://developers.openai.com/api/docs/pricing',
            'notes' => 'Checked 2026-10-10. Direct OpenAI billing basis for gpt-5.5-pro (snapshot gpt-5.5-pro-2026-04-23), standard service tier, at most 272,000 input tokens; not an alias. Excludes batch, flex, regional processing and FedRAMP endpoints (10% uplift for models released on or after 2026-03-05), built-in tools other than web search. Reasoning is output usage. OpenAI publishes no cached-input discount or cache-write rate for this model, so cached_input_tokens and cache_write_input_tokens stay unpriced and a reported count leaves the quote partial. web_searches counts web_search_call items whose action is search (open_page and find_in_page are not billed) at USD 10 per 1,000 for both web_search and web_search_preview on reasoning models (https://developers.openai.com/api/docs/guides/tools-web-search); search content tokens bill at the model token rates inside input usage. The adapters do not count OpenAI web searches yet, because OpenAI usage reports no search count, so callers must pass web_searches or the searches go unbilled.',
            'rates' => [
                'input_tokens' => ['amount' => '30', 'per' => '1000000'],
                'output_tokens' => ['amount' => '180', 'per' => '1000000'],
                'web_searches' => ['amount' => '10', 'per' => '1000'],
            ],
        ],
        'openai:gpt-5.5-pro-standard-long' => [
            'source' => 'https://developers.openai.com/api/docs/pricing',
            'notes' => 'Checked 2026-10-10. Direct OpenAI billing basis for gpt-5.5-pro (snapshot gpt-5.5-pro-2026-04-23), standard service tier, more than 272,000 input tokens; the long-context rates apply to the full request; not an alias. Excludes batch, flex, regional processing and FedRAMP endpoints (10% uplift for models released on or after 2026-03-05), built-in tools other than web search. Reasoning is output usage. OpenAI publishes no cached-input discount or cache-write rate for this model, so cached_input_tokens and cache_write_input_tokens stay unpriced and a reported count leaves the quote partial. web_searches counts web_search_call items whose action is search (open_page and find_in_page are not billed) at USD 10 per 1,000 for both web_search and web_search_preview on reasoning models (https://developers.openai.com/api/docs/guides/tools-web-search); search content tokens bill at the model token rates inside input usage. The adapters do not count OpenAI web searches yet, because OpenAI usage reports no search count, so callers must pass web_searches or the searches go unbilled.',
            'rates' => [
                'input_tokens' => ['amount' => '60', 'per' => '1000000'],
                'output_tokens' => ['amount' => '270', 'per' => '1000000'],
                'web_searches' => ['amount' => '10', 'per' => '1000'],
            ],
        ],
        'openai:gpt-5.4-standard-short' => [
            'source' => 'https://developers.openai.com/api/docs/pricing',
            'notes' => 'Checked 2026-10-10. Direct OpenAI billing basis for gpt-5.4 (snapshot gpt-5.4-2026-03-05), standard service tier, at most 272,000 input tokens; not an alias. Excludes batch, flex, fast (formerly priority), regional processing and FedRAMP endpoints (10% uplift for models released on or after 2026-03-05), built-in tools other than web search. Reasoning is output usage. OpenAI charges no additional cache-write fee before GPT-5.6, and input tokens bill at exactly one of the uncached, cached or cache-write rates (https://developers.openai.com/api/docs/guides/prompt-caching), so cache_write_input_tokens is priced at the uncached input rate; input partitions are exclusive. web_searches counts web_search_call items whose action is search (open_page and find_in_page are not billed) at USD 10 per 1,000 for both web_search and web_search_preview on reasoning models (https://developers.openai.com/api/docs/guides/tools-web-search); search content tokens bill at the model token rates inside input usage. The adapters do not count OpenAI web searches yet, because OpenAI usage reports no search count, so callers must pass web_searches or the searches go unbilled.',
            'rates' => [
                'input_tokens' => ['amount' => '2.50', 'per' => '1000000'],
                'output_tokens' => ['amount' => '15', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.25', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '2.50', 'per' => '1000000'],
                'web_searches' => ['amount' => '10', 'per' => '1000'],
            ],
        ],
        'openai:gpt-5.4-standard-long' => [
            'source' => 'https://developers.openai.com/api/docs/pricing',
            'notes' => 'Checked 2026-10-10. Direct OpenAI billing basis for gpt-5.4 (snapshot gpt-5.4-2026-03-05), standard service tier, more than 272,000 input tokens; the long-context rates apply to the full request; not an alias. Excludes batch, flex, fast (formerly priority), regional processing and FedRAMP endpoints (10% uplift for models released on or after 2026-03-05), built-in tools other than web search. Reasoning is output usage. OpenAI charges no additional cache-write fee before GPT-5.6, and input tokens bill at exactly one of the uncached, cached or cache-write rates (https://developers.openai.com/api/docs/guides/prompt-caching), so cache_write_input_tokens is priced at the uncached input rate; input partitions are exclusive. web_searches counts web_search_call items whose action is search (open_page and find_in_page are not billed) at USD 10 per 1,000 for both web_search and web_search_preview on reasoning models (https://developers.openai.com/api/docs/guides/tools-web-search); search content tokens bill at the model token rates inside input usage. The adapters do not count OpenAI web searches yet, because OpenAI usage reports no search count, so callers must pass web_searches or the searches go unbilled.',
            'rates' => [
                'input_tokens' => ['amount' => '5', 'per' => '1000000'],
                'output_tokens' => ['amount' => '22.50', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.50', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '5', 'per' => '1000000'],
                'web_searches' => ['amount' => '10', 'per' => '1000'],
            ],
        ],
        'openai:gpt-5.4-pro-standard-short' => [
            'source' => 'https://developers.openai.com/api/docs/pricing',
            'notes' => 'Checked 2026-10-10. Direct OpenAI billing basis for gpt-5.4-pro (snapshot gpt-5.4-pro-2026-03-05), standard service tier, at most 272,000 input tokens; not an alias. Excludes batch, flex, regional processing and FedRAMP endpoints (10% uplift for models released on or after 2026-03-05), built-in tools other than web search. Reasoning is output usage. OpenAI publishes no cached-input discount or cache-write rate for this model, so cached_input_tokens and cache_write_input_tokens stay unpriced and a reported count leaves the quote partial. web_searches counts web_search_call items whose action is search (open_page and find_in_page are not billed) at USD 10 per 1,000 for both web_search and web_search_preview on reasoning models (https://developers.openai.com/api/docs/guides/tools-web-search); search content tokens bill at the model token rates inside input usage. The adapters do not count OpenAI web searches yet, because OpenAI usage reports no search count, so callers must pass web_searches or the searches go unbilled.',
            'rates' => [
                'input_tokens' => ['amount' => '30', 'per' => '1000000'],
                'output_tokens' => ['amount' => '180', 'per' => '1000000'],
                'web_searches' => ['amount' => '10', 'per' => '1000'],
            ],
        ],
        'openai:gpt-5.4-pro-standard-long' => [
            'source' => 'https://developers.openai.com/api/docs/pricing',
            'notes' => 'Checked 2026-10-10. Direct OpenAI billing basis for gpt-5.4-pro (snapshot gpt-5.4-pro-2026-03-05), standard service tier, more than 272,000 input tokens; the long-context rates apply to the full request; not an alias. Excludes batch, flex, regional processing and FedRAMP endpoints (10% uplift for models released on or after 2026-03-05), built-in tools other than web search. Reasoning is output usage. OpenAI publishes no cached-input discount or cache-write rate for this model, so cached_input_tokens and cache_write_input_tokens stay unpriced and a reported count leaves the quote partial. web_searches counts web_search_call items whose action is search (open_page and find_in_page are not billed) at USD 10 per 1,000 for both web_search and web_search_preview on reasoning models (https://developers.openai.com/api/docs/guides/tools-web-search); search content tokens bill at the model token rates inside input usage. The adapters do not count OpenAI web searches yet, because OpenAI usage reports no search count, so callers must pass web_searches or the searches go unbilled.',
            'rates' => [
                'input_tokens' => ['amount' => '60', 'per' => '1000000'],
                'output_tokens' => ['amount' => '270', 'per' => '1000000'],
                'web_searches' => ['amount' => '10', 'per' => '1000'],
            ],
        ],
        'openai:gpt-5.4-mini-standard' => [
            'source' => 'https://developers.openai.com/api/docs/pricing',
            'notes' => 'Checked 2026-10-10. Direct OpenAI billing basis for gpt-5.4-mini (snapshot gpt-5.4-mini-2026-03-17), standard service tier, all prompt lengths. Maximum input is 272,000 tokens, so no long-context tier applies.; not an alias. Excludes batch, flex, fast (formerly priority), regional processing and FedRAMP endpoints (10% uplift for models released on or after 2026-03-05), built-in tools other than web search. Reasoning is output usage. OpenAI charges no additional cache-write fee before GPT-5.6, and input tokens bill at exactly one of the uncached, cached or cache-write rates (https://developers.openai.com/api/docs/guides/prompt-caching), so cache_write_input_tokens is priced at the uncached input rate; input partitions are exclusive. web_searches counts web_search_call items whose action is search (open_page and find_in_page are not billed) at USD 10 per 1,000 for both web_search and web_search_preview on reasoning models (https://developers.openai.com/api/docs/guides/tools-web-search); search content tokens bill at the model token rates inside input usage. The adapters do not count OpenAI web searches yet, because OpenAI usage reports no search count, so callers must pass web_searches or the searches go unbilled.',
            'rates' => [
                'input_tokens' => ['amount' => '0.75', 'per' => '1000000'],
                'output_tokens' => ['amount' => '4.50', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.075', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '0.75', 'per' => '1000000'],
                'web_searches' => ['amount' => '10', 'per' => '1000'],
            ],
        ],
        'openai:gpt-5.2-standard' => [
            'source' => 'https://developers.openai.com/api/docs/pricing',
            'notes' => 'Checked 2026-10-10. Direct OpenAI billing basis for gpt-5.2 (snapshot gpt-5.2-2025-12-11), standard service tier, all prompt lengths. 400,000-token context with 128,000 max output; OpenAI publishes no long-context tier.; not an alias. Excludes batch, flex, fast (formerly priority), regional processing and FedRAMP endpoints (10% uplift for models released on or after 2026-03-05), built-in tools other than web search. Reasoning is output usage. OpenAI charges no additional cache-write fee before GPT-5.6, and input tokens bill at exactly one of the uncached, cached or cache-write rates (https://developers.openai.com/api/docs/guides/prompt-caching), so cache_write_input_tokens is priced at the uncached input rate; input partitions are exclusive. web_searches counts web_search_call items whose action is search (open_page and find_in_page are not billed) at USD 10 per 1,000 for both web_search and web_search_preview on reasoning models (https://developers.openai.com/api/docs/guides/tools-web-search); search content tokens bill at the model token rates inside input usage. The adapters do not count OpenAI web searches yet, because OpenAI usage reports no search count, so callers must pass web_searches or the searches go unbilled.',
            'rates' => [
                'input_tokens' => ['amount' => '1.75', 'per' => '1000000'],
                'output_tokens' => ['amount' => '14', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.175', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '1.75', 'per' => '1000000'],
                'web_searches' => ['amount' => '10', 'per' => '1000'],
            ],
        ],
        'openai:gpt-5.2-pro-standard' => [
            'source' => 'https://developers.openai.com/api/docs/pricing',
            'notes' => 'Checked 2026-10-10. Direct OpenAI billing basis for gpt-5.2-pro (snapshot gpt-5.2-pro-2025-12-11), standard service tier, all prompt lengths. 400,000-token context with 128,000 max output; OpenAI publishes no long-context tier.; not an alias. Excludes batch, regional processing and FedRAMP endpoints (10% uplift for models released on or after 2026-03-05), built-in tools other than web search. Reasoning is output usage. OpenAI publishes no cached-input discount or cache-write rate for this model, so cached_input_tokens and cache_write_input_tokens stay unpriced and a reported count leaves the quote partial. web_searches counts web_search_call items whose action is search (open_page and find_in_page are not billed) at USD 10 per 1,000 for both web_search and web_search_preview on reasoning models (https://developers.openai.com/api/docs/guides/tools-web-search); search content tokens bill at the model token rates inside input usage. The adapters do not count OpenAI web searches yet, because OpenAI usage reports no search count, so callers must pass web_searches or the searches go unbilled.',
            'rates' => [
                'input_tokens' => ['amount' => '21', 'per' => '1000000'],
                'output_tokens' => ['amount' => '168', 'per' => '1000000'],
                'web_searches' => ['amount' => '10', 'per' => '1000'],
            ],
        ],
        'openai:gpt-4.1-standard' => [
            'source' => 'https://developers.openai.com/api/docs/pricing',
            'notes' => 'Checked 2026-10-10. Direct OpenAI billing basis for gpt-4.1 (snapshot gpt-4.1-2025-04-14), standard service tier, all prompt lengths. Flat rates across the 1,047,576-token context window; OpenAI publishes no long-context tier.; not an alias. Excludes batch, fast (formerly priority), regional processing and FedRAMP endpoints (10% uplift for models released on or after 2026-03-05), built-in tools. Reasoning is output usage. OpenAI charges no additional cache-write fee before GPT-5.6, and input tokens bill at exactly one of the uncached, cached or cache-write rates (https://developers.openai.com/api/docs/guides/prompt-caching), so cache_write_input_tokens is priced at the uncached input rate; input partitions are exclusive. Web search is deliberately unpriced: on non-reasoning models web_search bills USD 10 per 1,000 but web_search_preview bills USD 25 per 1,000, and the package cannot tell them apart.',
            'rates' => [
                'input_tokens' => ['amount' => '2', 'per' => '1000000'],
                'output_tokens' => ['amount' => '8', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.50', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '2', 'per' => '1000000'],
            ],
        ],
        'openai:gpt-4.1-mini-standard' => [
            'source' => 'https://developers.openai.com/api/docs/pricing',
            'notes' => 'Checked 2026-10-10. Direct OpenAI billing basis for gpt-4.1-mini (snapshot gpt-4.1-mini-2025-04-14), standard service tier, all prompt lengths. Flat rates across the 1,047,576-token context window; OpenAI publishes no long-context tier.; not an alias. Excludes batch, fast (formerly priority), regional processing and FedRAMP endpoints (10% uplift for models released on or after 2026-03-05), built-in tools. Reasoning is output usage. OpenAI charges no additional cache-write fee before GPT-5.6, and input tokens bill at exactly one of the uncached, cached or cache-write rates (https://developers.openai.com/api/docs/guides/prompt-caching), so cache_write_input_tokens is priced at the uncached input rate; input partitions are exclusive. Web search is deliberately unpriced: on non-reasoning models web_search bills USD 10 per 1,000 but web_search_preview bills USD 25 per 1,000, and the package cannot tell them apart.',
            'rates' => [
                'input_tokens' => ['amount' => '0.40', 'per' => '1000000'],
                'output_tokens' => ['amount' => '1.60', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.10', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '0.40', 'per' => '1000000'],
            ],
        ],
        'openai:gpt-4o-standard' => [
            'source' => 'https://developers.openai.com/api/docs/pricing',
            'notes' => 'Checked 2026-10-10. Direct OpenAI billing basis for gpt-4o (snapshot gpt-4o-2024-08-06 (default) and gpt-4o-2024-11-20), standard service tier, all prompt lengths. 128,000-token context; no long-context tier.; not an alias. Excludes batch, fast (formerly priority), regional processing and FedRAMP endpoints (10% uplift for models released on or after 2026-03-05), built-in tools. Reasoning is output usage. OpenAI charges no additional cache-write fee before GPT-5.6, and input tokens bill at exactly one of the uncached, cached or cache-write rates (https://developers.openai.com/api/docs/guides/prompt-caching), so cache_write_input_tokens is priced at the uncached input rate; input partitions are exclusive. Web search is deliberately unpriced: on non-reasoning models web_search bills USD 10 per 1,000 but web_search_preview bills USD 25 per 1,000, and the package cannot tell them apart. Does not cover gpt-4o-2024-05-13, which OpenAI prices separately at USD 5/M input and 15/M output and shuts down on 2026-10-23.',
            'rates' => [
                'input_tokens' => ['amount' => '2.50', 'per' => '1000000'],
                'output_tokens' => ['amount' => '10', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '1.25', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '2.50', 'per' => '1000000'],
            ],
        ],
        'openai:gpt-4o-mini-standard' => [
            'source' => 'https://developers.openai.com/api/docs/pricing',
            'notes' => 'Checked 2026-10-10. Direct OpenAI billing basis for gpt-4o-mini (snapshot gpt-4o-mini-2024-07-18), standard service tier, all prompt lengths. 128,000-token context; no long-context tier.; not an alias. Excludes batch, fast (formerly priority), regional processing and FedRAMP endpoints (10% uplift for models released on or after 2026-03-05), built-in tools. Reasoning is output usage. OpenAI charges no additional cache-write fee before GPT-5.6, and input tokens bill at exactly one of the uncached, cached or cache-write rates (https://developers.openai.com/api/docs/guides/prompt-caching), so cache_write_input_tokens is priced at the uncached input rate; input partitions are exclusive. Web search is deliberately unpriced: on non-reasoning models web_search bills USD 10 per 1,000 but web_search_preview bills USD 25 per 1,000, and the package cannot tell them apart.',
            'rates' => [
                'input_tokens' => ['amount' => '0.15', 'per' => '1000000'],
                'output_tokens' => ['amount' => '0.60', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.075', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '0.15', 'per' => '1000000'],
            ],
        ],
        'openai:chat-latest-standard' => [
            'source' => 'https://developers.openai.com/api/docs/pricing',
            'notes' => 'Checked 2026-10-10. Direct OpenAI billing basis for chat-latest (snapshot chat-latest, a moving alias for the latest ChatGPT Instant model), standard service tier, all prompt lengths. Maximum input is 272,000 tokens; no long-context tier.; not an alias. Excludes regional processing and FedRAMP endpoints (10% uplift for models released on or after 2026-03-05), built-in tools. Reasoning is output usage. OpenAI charges no additional cache-write fee before GPT-5.6, and input tokens bill at exactly one of the uncached, cached or cache-write rates (https://developers.openai.com/api/docs/guides/prompt-caching), so cache_write_input_tokens is priced at the uncached input rate; input partitions are exclusive. Web search is deliberately unpriced: on non-reasoning models web_search bills USD 10 per 1,000 but web_search_preview bills USD 25 per 1,000, and the package cannot tell them apart. chat-latest moves when ChatGPT ships a new Instant model; re-review the rate whenever it moves. OpenRouter lists it as openai/gpt-chat-latest (canonical gpt-chat-latest-20260505).',
            'rates' => [
                'input_tokens' => ['amount' => '5', 'per' => '1000000'],
                'output_tokens' => ['amount' => '30', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.50', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '5', 'per' => '1000000'],
            ],
        ],
        'zai:glm-5.3' => [
            'source' => 'https://docs.z.ai/guides/overview/pricing',
            'notes' => 'Checked 2026-10-09. Direct Z.AI pay-as-you-go API, not Coding Plan or OpenRouter. Exclusive input/cache-hit partitions; output includes reasoning. Cache storage is only limited-time free and is deliberately unpriced; storage or tool usage must not be silently omitted.',
            'rates' => [
                'input_tokens' => ['amount' => '1.4', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.26', 'per' => '1000000'],
                'output_tokens' => ['amount' => '4.4', 'per' => '1000000'],
            ],
        ],
        'zai:glm-5.3-flash' => [
            'source' => 'https://docs.z.ai/guides/overview/pricing',
            'notes' => 'Checked 2026-10-09. Direct Z.AI pay-as-you-go API, not Coding Plan, FlashX or OpenRouter. Exclusive input/cache-hit partitions; output includes reasoning. Cache storage is only limited-time free and is deliberately unpriced; storage or tool usage must not be silently omitted.',
            'rates' => [
                'input_tokens' => ['amount' => '0.15', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.03', 'per' => '1000000'],
                'output_tokens' => ['amount' => '0.50', 'per' => '1000000'],
            ],
        ],
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
        'deepseek:deepseek-v4-pro-peak' => [
            'source' => 'https://api-docs.deepseek.com/quick_start/pricing',
            'notes' => 'Checked 2026-10-09. Direct deepseek-v4-pro currently runs DeepSeek-V4-Pro-0813. Synthetic peak billing basis, not a provider alias: USD 1.32/M cache-miss input, 0.044/M cache-hit input, 3.96/M output. Peak hours 01:00-04:00 and 06:00-10:00 UTC Monday-Friday except Chinese public holidays; off-peak is half price. May be used as a conservative reservation at any time, not an exact off-peak invoice. Exclusive input/cache-hit partitions; thinking is output usage. Generic V4 and retired V4-Flash are not this model.',
            'rates' => [
                'input_tokens' => ['amount' => '1.32', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.044', 'per' => '1000000'],
                'output_tokens' => ['amount' => '3.96', 'per' => '1000000'],
            ],
        ],
        'deepseek:deepseek-flash-peak' => [
            'source' => 'https://api-docs.deepseek.com/quick_start/pricing',
            'notes' => 'Checked 2026-10-10. Direct DeepSeek API model deepseek-flash, which currently runs DeepSeek-V4.1-Flash (released 2026-09-10). Synthetic peak billing basis, not a provider alias: USD 0.30/M cache-miss input, 0.006/M cache-hit input, 1.20/M output. Peak hours are 01:00-04:00 and 06:00-10:00 UTC Monday-Friday except Chinese public holidays; all other hours, weekends and Chinese public holidays are off-peak at half price. May be used as a conservative reservation at any time, not an exact off-peak invoice. Exclusive input/cache-hit partitions; thinking (the default mode) is output usage. The legacy names deepseek-v4-flash and deepseek-v4-flash-vision-exp are temporarily routed to V4.1-Flash and billed at this price, but stay fallback-blocked identities rather than aliases because the price is time-tiered.',
            'rates' => [
                'input_tokens' => ['amount' => '0.3', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.006', 'per' => '1000000'],
                'output_tokens' => ['amount' => '1.2', 'per' => '1000000'],
            ],
        ],
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
        'moonshot:kimi-k3' => [
            'source' => 'https://platform.kimi.ai/docs/pricing/chat',
            'notes' => 'Checked 2026-10-10. Direct Kimi API Platform (international, USD; platform.moonshot.ai now redirects to platform.kimi.ai, API host api.moonshot.ai), pay-as-you-go, not Kimi Membership, Kimi Code or batch. USD 3.00/M cache-miss input, 0.30/M cached input, 15.00/M output, cache writes 3.00/M (5-minute TTL, the default) and 6.00/M (1-hour TTL) per https://platform.kimi.ai/docs/guide/context-caching. Cache reads, writes and uncached input are exclusive partitions of prompt_tokens; reasoning is output usage. Chat Completions and Responses report only an aggregate cache_write_tokens; only the Messages API reports the TTL split, so an aggregate write without a split stays a missing unit. 1M context, no context tier. Built-in $web_search (legacy, deprecating 2026-10-20) and the /v1/tools REST endpoints are separately billed and not priced here. laravel/ai\'s openai-compatible driver drops cache_write_tokens, so through that driver every cache write bills as uncached input: exact for 5-minute writes, 3.00/M short for 1-hour writes.',
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
        'dashscope:qwen3.8-max-intl' => [
            'source' => 'https://www.alibabacloud.com/help/en/model-studio/model-pricing',
            'notes' => 'Checked 2026-10-10. qwen3.8-max (flagship; also covers the qwen3.8-max-0902 snapshot at identical rates): Alibaba Cloud Model Studio Singapore region (dashscope-intl), International deployment scope, standard real-time list price; not China (Beijing), Hong Kong, Frankfurt, Virginia or Tokyo, not Global, US or EU deployment scope, not batch, not limited-time discounts, free quota not subtracted. Billing basis, not a provider alias. Applies to requests with at most 1,000,000 input tokens per request. Thinking and non-thinking modes bill the same output rate. Cache-hit tokens are deliberately unpriced: https://www.alibabacloud.com/help/en/model-studio/context-cache says the qwen3.8 cache-hit rate is not the usual 10%/20% and is published only in the console, so a cached_input_tokens count keeps the quote partial. cache_write_input_tokens is explicit cache creation at 125% of the input price (5-minute TTL). Reasoning (chain of thought) is output usage. laravel/ai\'s openai-compatible driver drops cache_creation_input_tokens, so through that driver explicit cache creation bills as uncached input, 25% short.',
            'rates' => [
                'input_tokens' => ['amount' => '2', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '2.5', 'per' => '1000000'],
                'output_tokens' => ['amount' => '6', 'per' => '1000000'],
            ],
        ],
        'dashscope:qwen3.8-flash-intl' => [
            'source' => 'https://www.alibabacloud.com/help/en/model-studio/model-pricing',
            'notes' => 'Checked 2026-10-10. qwen3.8-flash: Alibaba Cloud Model Studio Singapore region (dashscope-intl), International deployment scope, standard real-time list price; not China (Beijing), Hong Kong, Frankfurt, Virginia or Tokyo, not Global, US or EU deployment scope, not batch, not limited-time discounts, free quota not subtracted. Billing basis, not a provider alias. Applies to requests with at most 1,000,000 input tokens per request. Cache-hit tokens are deliberately unpriced: https://www.alibabacloud.com/help/en/model-studio/context-cache says the qwen3.8 cache-hit rate is not the usual 10%/20% and is published only in the console, so a cached_input_tokens count keeps the quote partial. cache_write_input_tokens is explicit cache creation at 125% of the input price (5-minute TTL). Reasoning (chain of thought) is output usage. laravel/ai\'s openai-compatible driver drops cache_creation_input_tokens, so through that driver explicit cache creation bills as uncached input, 25% short.',
            'rates' => [
                'input_tokens' => ['amount' => '0.15', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '0.1875', 'per' => '1000000'],
                'output_tokens' => ['amount' => '0.47', 'per' => '1000000'],
            ],
        ],
        'dashscope:qwen3.7-max-intl' => [
            'source' => 'https://www.alibabacloud.com/help/en/model-studio/model-pricing',
            'notes' => 'Checked 2026-10-10. qwen3.7-max (currently qwen3.7-max-2026-05-20; previous-generation flagship): Alibaba Cloud Model Studio Singapore region (dashscope-intl), International deployment scope, standard real-time list price; not China (Beijing), Hong Kong, Frankfurt, Virginia or Tokyo, not Global, US or EU deployment scope, not batch, not limited-time discounts, free quota not subtracted. Billing basis, not a provider alias. Applies to requests with at most 1,000,000 input tokens per request. Thinking and non-thinking modes bill the same output rate. cached_input_tokens is the implicit-cache hit rate (20% of the tier input price per https://www.alibabacloud.com/help/en/model-studio/context-cache); explicit-cache hits bill at 10%, so this overstates them. cache_write_input_tokens is explicit cache creation at 125% of the tier input price (5-minute TTL); implicit caching has no write charge. Reasoning (chain of thought) is output usage. laravel/ai\'s openai-compatible driver drops cache_creation_input_tokens, so through that driver explicit cache creation bills as uncached input, 25% short.',
            'rates' => [
                'input_tokens' => ['amount' => '2.5', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.5', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '3.125', 'per' => '1000000'],
                'output_tokens' => ['amount' => '7.5', 'per' => '1000000'],
            ],
        ],
        'dashscope:qwen3.7-plus-intl-256k' => [
            'source' => 'https://www.alibabacloud.com/help/en/model-studio/model-pricing',
            'notes' => 'Checked 2026-10-10. qwen3.7-plus (currently qwen3.7-plus-2026-05-26; the generic alias carries a limited-time 20% discount that is excluded): Alibaba Cloud Model Studio Singapore region (dashscope-intl), International deployment scope, standard real-time list price; not China (Beijing), Hong Kong, Frankfurt, Virginia or Tokyo, not Global, US or EU deployment scope, not batch, not limited-time discounts, free quota not subtracted. Billing basis, not a provider alias. Applies to requests with at most 256,000 input tokens per request. The tier is chosen by the total input tokens of the request, including cache hits and cache creation, and every token of the request, including output, bills at that tier. cached_input_tokens is the implicit-cache hit rate (20% of the tier input price per https://www.alibabacloud.com/help/en/model-studio/context-cache); explicit-cache hits bill at 10%, so this overstates them. cache_write_input_tokens is explicit cache creation at 125% of the tier input price (5-minute TTL); implicit caching has no write charge. Reasoning (chain of thought) is output usage. laravel/ai\'s openai-compatible driver drops cache_creation_input_tokens, so through that driver explicit cache creation bills as uncached input, 25% short.',
            'rates' => [
                'input_tokens' => ['amount' => '0.4', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.08', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '0.5', 'per' => '1000000'],
                'output_tokens' => ['amount' => '1.6', 'per' => '1000000'],
            ],
        ],
        'dashscope:qwen3.7-plus-intl-1m' => [
            'source' => 'https://www.alibabacloud.com/help/en/model-studio/model-pricing',
            'notes' => 'Checked 2026-10-10. qwen3.7-plus (currently qwen3.7-plus-2026-05-26; the generic alias carries a limited-time 20% discount that is excluded): Alibaba Cloud Model Studio Singapore region (dashscope-intl), International deployment scope, standard real-time list price; not China (Beijing), Hong Kong, Frankfurt, Virginia or Tokyo, not Global, US or EU deployment scope, not batch, not limited-time discounts, free quota not subtracted. Billing basis, not a provider alias. Applies to requests with more than 256,000 and at most 1,000,000 input tokens per request. The tier is chosen by the total input tokens of the request, including cache hits and cache creation, and every token of the request, including output, bills at that tier. cached_input_tokens is the implicit-cache hit rate (20% of the tier input price per https://www.alibabacloud.com/help/en/model-studio/context-cache); explicit-cache hits bill at 10%, so this overstates them. cache_write_input_tokens is explicit cache creation at 125% of the tier input price (5-minute TTL); implicit caching has no write charge. Reasoning (chain of thought) is output usage. laravel/ai\'s openai-compatible driver drops cache_creation_input_tokens, so through that driver explicit cache creation bills as uncached input, 25% short.',
            'rates' => [
                'input_tokens' => ['amount' => '1.2', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.24', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '1.5', 'per' => '1000000'],
                'output_tokens' => ['amount' => '4.8', 'per' => '1000000'],
            ],
        ],
        'dashscope:qwen3.7-flash-intl-32k' => [
            'source' => 'https://www.alibabacloud.com/help/en/model-studio/model-pricing',
            'notes' => 'Checked 2026-10-10. qwen3.7-flash (currently qwen3.7-flash-2026-07-15): Alibaba Cloud Model Studio Singapore region (dashscope-intl), International deployment scope, standard real-time list price; not China (Beijing), Hong Kong, Frankfurt, Virginia or Tokyo, not Global, US or EU deployment scope, not batch, not limited-time discounts, free quota not subtracted. Billing basis, not a provider alias. Applies to requests with at most 32,000 input tokens per request. The tier is chosen by the total input tokens of the request, including cache hits and cache creation, and every token of the request, including output, bills at that tier. cached_input_tokens is the implicit-cache hit rate (20% of the tier input price per https://www.alibabacloud.com/help/en/model-studio/context-cache); explicit-cache hits bill at 10%, so this overstates them. cache_write_input_tokens is explicit cache creation at 125% of the tier input price (5-minute TTL); implicit caching has no write charge. Reasoning (chain of thought) is output usage. laravel/ai\'s openai-compatible driver drops cache_creation_input_tokens, so through that driver explicit cache creation bills as uncached input, 25% short.',
            'rates' => [
                'input_tokens' => ['amount' => '0.03', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.006', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '0.0375', 'per' => '1000000'],
                'output_tokens' => ['amount' => '0.13', 'per' => '1000000'],
            ],
        ],
        'dashscope:qwen3.7-flash-intl-256k' => [
            'source' => 'https://www.alibabacloud.com/help/en/model-studio/model-pricing',
            'notes' => 'Checked 2026-10-10. qwen3.7-flash (currently qwen3.7-flash-2026-07-15): Alibaba Cloud Model Studio Singapore region (dashscope-intl), International deployment scope, standard real-time list price; not China (Beijing), Hong Kong, Frankfurt, Virginia or Tokyo, not Global, US or EU deployment scope, not batch, not limited-time discounts, free quota not subtracted. Billing basis, not a provider alias. Applies to requests with more than 32,000 and at most 256,000 input tokens per request. The tier is chosen by the total input tokens of the request, including cache hits and cache creation, and every token of the request, including output, bills at that tier. cached_input_tokens is the implicit-cache hit rate (20% of the tier input price per https://www.alibabacloud.com/help/en/model-studio/context-cache); explicit-cache hits bill at 10%, so this overstates them. cache_write_input_tokens is explicit cache creation at 125% of the tier input price (5-minute TTL); implicit caching has no write charge. Reasoning (chain of thought) is output usage. laravel/ai\'s openai-compatible driver drops cache_creation_input_tokens, so through that driver explicit cache creation bills as uncached input, 25% short.',
            'rates' => [
                'input_tokens' => ['amount' => '0.1', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.02', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '0.125', 'per' => '1000000'],
                'output_tokens' => ['amount' => '0.4', 'per' => '1000000'],
            ],
        ],
        'dashscope:qwen3.7-flash-intl-1m' => [
            'source' => 'https://www.alibabacloud.com/help/en/model-studio/model-pricing',
            'notes' => 'Checked 2026-10-10. qwen3.7-flash (currently qwen3.7-flash-2026-07-15): Alibaba Cloud Model Studio Singapore region (dashscope-intl), International deployment scope, standard real-time list price; not China (Beijing), Hong Kong, Frankfurt, Virginia or Tokyo, not Global, US or EU deployment scope, not batch, not limited-time discounts, free quota not subtracted. Billing basis, not a provider alias. Applies to requests with more than 256,000 and at most 1,000,000 input tokens per request. The tier is chosen by the total input tokens of the request, including cache hits and cache creation, and every token of the request, including output, bills at that tier. cached_input_tokens is the implicit-cache hit rate (20% of the tier input price per https://www.alibabacloud.com/help/en/model-studio/context-cache); explicit-cache hits bill at 10%, so this overstates them. cache_write_input_tokens is explicit cache creation at 125% of the tier input price (5-minute TTL); implicit caching has no write charge. Reasoning (chain of thought) is output usage. laravel/ai\'s openai-compatible driver drops cache_creation_input_tokens, so through that driver explicit cache creation bills as uncached input, 25% short.',
            'rates' => [
                'input_tokens' => ['amount' => '0.2', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.04', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '0.25', 'per' => '1000000'],
                'output_tokens' => ['amount' => '0.8', 'per' => '1000000'],
            ],
        ],
        'dashscope:qwen3-max-intl-32k' => [
            'source' => 'https://www.alibabacloud.com/help/en/model-studio/model-pricing',
            'notes' => 'Checked 2026-10-10. qwen3-max (currently qwen3-max-2026-01-23; also the 2025-09-23 snapshot at identical rates): Alibaba Cloud Model Studio Singapore region (dashscope-intl), International deployment scope, standard real-time list price; not China (Beijing), Hong Kong, Frankfurt, Virginia or Tokyo, not Global, US or EU deployment scope, not batch, not limited-time discounts, free quota not subtracted. Billing basis, not a provider alias. Applies to requests with at most 32,000 input tokens per request. The tier is chosen by the total input tokens of the request, including cache hits and cache creation, and every token of the request, including output, bills at that tier. cached_input_tokens is the implicit-cache hit rate (20% of the tier input price per https://www.alibabacloud.com/help/en/model-studio/context-cache); explicit-cache hits bill at 10%, so this overstates them. cache_write_input_tokens is explicit cache creation at 125% of the tier input price (5-minute TTL); implicit caching has no write charge. Reasoning (chain of thought) is output usage. laravel/ai\'s openai-compatible driver drops cache_creation_input_tokens, so through that driver explicit cache creation bills as uncached input, 25% short.',
            'rates' => [
                'input_tokens' => ['amount' => '1.2', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.24', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '1.5', 'per' => '1000000'],
                'output_tokens' => ['amount' => '6', 'per' => '1000000'],
            ],
        ],
        'dashscope:qwen3-max-intl-128k' => [
            'source' => 'https://www.alibabacloud.com/help/en/model-studio/model-pricing',
            'notes' => 'Checked 2026-10-10. qwen3-max (currently qwen3-max-2026-01-23; also the 2025-09-23 snapshot at identical rates): Alibaba Cloud Model Studio Singapore region (dashscope-intl), International deployment scope, standard real-time list price; not China (Beijing), Hong Kong, Frankfurt, Virginia or Tokyo, not Global, US or EU deployment scope, not batch, not limited-time discounts, free quota not subtracted. Billing basis, not a provider alias. Applies to requests with more than 32,000 and at most 128,000 input tokens per request. The tier is chosen by the total input tokens of the request, including cache hits and cache creation, and every token of the request, including output, bills at that tier. cached_input_tokens is the implicit-cache hit rate (20% of the tier input price per https://www.alibabacloud.com/help/en/model-studio/context-cache); explicit-cache hits bill at 10%, so this overstates them. cache_write_input_tokens is explicit cache creation at 125% of the tier input price (5-minute TTL); implicit caching has no write charge. Reasoning (chain of thought) is output usage. laravel/ai\'s openai-compatible driver drops cache_creation_input_tokens, so through that driver explicit cache creation bills as uncached input, 25% short.',
            'rates' => [
                'input_tokens' => ['amount' => '2.4', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.48', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '3', 'per' => '1000000'],
                'output_tokens' => ['amount' => '12', 'per' => '1000000'],
            ],
        ],
        'dashscope:qwen3-max-intl-256k' => [
            'source' => 'https://www.alibabacloud.com/help/en/model-studio/model-pricing',
            'notes' => 'Checked 2026-10-10. qwen3-max (currently qwen3-max-2026-01-23; also the 2025-09-23 snapshot at identical rates): Alibaba Cloud Model Studio Singapore region (dashscope-intl), International deployment scope, standard real-time list price; not China (Beijing), Hong Kong, Frankfurt, Virginia or Tokyo, not Global, US or EU deployment scope, not batch, not limited-time discounts, free quota not subtracted. Billing basis, not a provider alias. Applies to requests with more than 128,000 and at most 256,000 input tokens per request. The tier is chosen by the total input tokens of the request, including cache hits and cache creation, and every token of the request, including output, bills at that tier. cached_input_tokens is the implicit-cache hit rate (20% of the tier input price per https://www.alibabacloud.com/help/en/model-studio/context-cache); explicit-cache hits bill at 10%, so this overstates them. cache_write_input_tokens is explicit cache creation at 125% of the tier input price (5-minute TTL); implicit caching has no write charge. Reasoning (chain of thought) is output usage. laravel/ai\'s openai-compatible driver drops cache_creation_input_tokens, so through that driver explicit cache creation bills as uncached input, 25% short.',
            'rates' => [
                'input_tokens' => ['amount' => '3', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.6', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '3.75', 'per' => '1000000'],
                'output_tokens' => ['amount' => '15', 'per' => '1000000'],
            ],
        ],
        'dashscope:qwen-plus-intl-nonthinking-256k' => [
            'source' => 'https://www.alibabacloud.com/help/en/model-studio/model-pricing',
            'notes' => 'Checked 2026-10-10. qwen-plus (currently qwen-plus-2025-12-01; also qwen-plus-latest), non-thinking mode only; the output rate differs by mode: Alibaba Cloud Model Studio Singapore region (dashscope-intl), International deployment scope, standard real-time list price; not China (Beijing), Hong Kong, Frankfurt, Virginia or Tokyo, not Global, US or EU deployment scope, not batch, not limited-time discounts, free quota not subtracted. Billing basis, not a provider alias. Applies to requests with at most 256,000 input tokens per request. The tier is chosen by the total input tokens of the request, including cache hits and cache creation, and every token of the request, including output, bills at that tier. cached_input_tokens is the implicit-cache hit rate (20% of the tier input price per https://www.alibabacloud.com/help/en/model-studio/context-cache); explicit-cache hits bill at 10%, so this overstates them. cache_write_input_tokens is explicit cache creation at 125% of the tier input price (5-minute TTL); implicit caching has no write charge. Reasoning (chain of thought) is output usage. laravel/ai\'s openai-compatible driver drops cache_creation_input_tokens, so through that driver explicit cache creation bills as uncached input, 25% short.',
            'rates' => [
                'input_tokens' => ['amount' => '0.4', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.08', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '0.5', 'per' => '1000000'],
                'output_tokens' => ['amount' => '1.2', 'per' => '1000000'],
            ],
        ],
        'dashscope:qwen-plus-intl-thinking-256k' => [
            'source' => 'https://www.alibabacloud.com/help/en/model-studio/model-pricing',
            'notes' => 'Checked 2026-10-10. qwen-plus (currently qwen-plus-2025-12-01; also qwen-plus-latest), thinking mode only; the output rate differs by mode: Alibaba Cloud Model Studio Singapore region (dashscope-intl), International deployment scope, standard real-time list price; not China (Beijing), Hong Kong, Frankfurt, Virginia or Tokyo, not Global, US or EU deployment scope, not batch, not limited-time discounts, free quota not subtracted. Billing basis, not a provider alias. Applies to requests with at most 256,000 input tokens per request. The tier is chosen by the total input tokens of the request, including cache hits and cache creation, and every token of the request, including output, bills at that tier. cached_input_tokens is the implicit-cache hit rate (20% of the tier input price per https://www.alibabacloud.com/help/en/model-studio/context-cache); explicit-cache hits bill at 10%, so this overstates them. cache_write_input_tokens is explicit cache creation at 125% of the tier input price (5-minute TTL); implicit caching has no write charge. Reasoning (chain of thought) is output usage. laravel/ai\'s openai-compatible driver drops cache_creation_input_tokens, so through that driver explicit cache creation bills as uncached input, 25% short.',
            'rates' => [
                'input_tokens' => ['amount' => '0.4', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.08', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '0.5', 'per' => '1000000'],
                'output_tokens' => ['amount' => '4', 'per' => '1000000'],
            ],
        ],
        'dashscope:qwen-plus-intl-nonthinking-1m' => [
            'source' => 'https://www.alibabacloud.com/help/en/model-studio/model-pricing',
            'notes' => 'Checked 2026-10-10. qwen-plus (currently qwen-plus-2025-12-01; also qwen-plus-latest), non-thinking mode only; the output rate differs by mode: Alibaba Cloud Model Studio Singapore region (dashscope-intl), International deployment scope, standard real-time list price; not China (Beijing), Hong Kong, Frankfurt, Virginia or Tokyo, not Global, US or EU deployment scope, not batch, not limited-time discounts, free quota not subtracted. Billing basis, not a provider alias. Applies to requests with more than 256,000 and at most 1,000,000 input tokens per request. The tier is chosen by the total input tokens of the request, including cache hits and cache creation, and every token of the request, including output, bills at that tier. cached_input_tokens is the implicit-cache hit rate (20% of the tier input price per https://www.alibabacloud.com/help/en/model-studio/context-cache); explicit-cache hits bill at 10%, so this overstates them. cache_write_input_tokens is explicit cache creation at 125% of the tier input price (5-minute TTL); implicit caching has no write charge. Reasoning (chain of thought) is output usage. laravel/ai\'s openai-compatible driver drops cache_creation_input_tokens, so through that driver explicit cache creation bills as uncached input, 25% short.',
            'rates' => [
                'input_tokens' => ['amount' => '1.2', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.24', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '1.5', 'per' => '1000000'],
                'output_tokens' => ['amount' => '3.6', 'per' => '1000000'],
            ],
        ],
        'dashscope:qwen-plus-intl-thinking-1m' => [
            'source' => 'https://www.alibabacloud.com/help/en/model-studio/model-pricing',
            'notes' => 'Checked 2026-10-10. qwen-plus (currently qwen-plus-2025-12-01; also qwen-plus-latest), thinking mode only; the output rate differs by mode: Alibaba Cloud Model Studio Singapore region (dashscope-intl), International deployment scope, standard real-time list price; not China (Beijing), Hong Kong, Frankfurt, Virginia or Tokyo, not Global, US or EU deployment scope, not batch, not limited-time discounts, free quota not subtracted. Billing basis, not a provider alias. Applies to requests with more than 256,000 and at most 1,000,000 input tokens per request. The tier is chosen by the total input tokens of the request, including cache hits and cache creation, and every token of the request, including output, bills at that tier. cached_input_tokens is the implicit-cache hit rate (20% of the tier input price per https://www.alibabacloud.com/help/en/model-studio/context-cache); explicit-cache hits bill at 10%, so this overstates them. cache_write_input_tokens is explicit cache creation at 125% of the tier input price (5-minute TTL); implicit caching has no write charge. Reasoning (chain of thought) is output usage. laravel/ai\'s openai-compatible driver drops cache_creation_input_tokens, so through that driver explicit cache creation bills as uncached input, 25% short.',
            'rates' => [
                'input_tokens' => ['amount' => '1.2', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.24', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '1.5', 'per' => '1000000'],
                'output_tokens' => ['amount' => '12', 'per' => '1000000'],
            ],
        ],
        'dashscope:qwen-flash-intl-256k' => [
            'source' => 'https://www.alibabacloud.com/help/en/model-studio/model-pricing',
            'notes' => 'Checked 2026-10-10. qwen-flash (currently qwen-flash-2025-07-28): Alibaba Cloud Model Studio Singapore region (dashscope-intl), International deployment scope, standard real-time list price; not China (Beijing), Hong Kong, Frankfurt, Virginia or Tokyo, not Global, US or EU deployment scope, not batch, not limited-time discounts, free quota not subtracted. Billing basis, not a provider alias. Applies to requests with at most 256,000 input tokens per request. The tier is chosen by the total input tokens of the request, including cache hits and cache creation, and every token of the request, including output, bills at that tier. cached_input_tokens is the implicit-cache hit rate (20% of the tier input price per https://www.alibabacloud.com/help/en/model-studio/context-cache); explicit-cache hits bill at 10%, so this overstates them. cache_write_input_tokens is explicit cache creation at 125% of the tier input price (5-minute TTL); implicit caching has no write charge. Reasoning (chain of thought) is output usage. laravel/ai\'s openai-compatible driver drops cache_creation_input_tokens, so through that driver explicit cache creation bills as uncached input, 25% short.',
            'rates' => [
                'input_tokens' => ['amount' => '0.05', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.01', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '0.0625', 'per' => '1000000'],
                'output_tokens' => ['amount' => '0.4', 'per' => '1000000'],
            ],
        ],
        'dashscope:qwen-flash-intl-1m' => [
            'source' => 'https://www.alibabacloud.com/help/en/model-studio/model-pricing',
            'notes' => 'Checked 2026-10-10. qwen-flash (currently qwen-flash-2025-07-28): Alibaba Cloud Model Studio Singapore region (dashscope-intl), International deployment scope, standard real-time list price; not China (Beijing), Hong Kong, Frankfurt, Virginia or Tokyo, not Global, US or EU deployment scope, not batch, not limited-time discounts, free quota not subtracted. Billing basis, not a provider alias. Applies to requests with more than 256,000 and at most 1,000,000 input tokens per request. The tier is chosen by the total input tokens of the request, including cache hits and cache creation, and every token of the request, including output, bills at that tier. cached_input_tokens is the implicit-cache hit rate (20% of the tier input price per https://www.alibabacloud.com/help/en/model-studio/context-cache); explicit-cache hits bill at 10%, so this overstates them. cache_write_input_tokens is explicit cache creation at 125% of the tier input price (5-minute TTL); implicit caching has no write charge. Reasoning (chain of thought) is output usage. laravel/ai\'s openai-compatible driver drops cache_creation_input_tokens, so through that driver explicit cache creation bills as uncached input, 25% short.',
            'rates' => [
                'input_tokens' => ['amount' => '0.25', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.05', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '0.3125', 'per' => '1000000'],
                'output_tokens' => ['amount' => '2', 'per' => '1000000'],
            ],
        ],
        'dashscope:qwen3-coder-plus-intl-32k' => [
            'source' => 'https://www.alibabacloud.com/help/en/model-studio/model-pricing',
            'notes' => 'Checked 2026-10-10. qwen3-coder-plus (currently qwen3-coder-plus-2025-09-23): Alibaba Cloud Model Studio Singapore region (dashscope-intl), International deployment scope, standard real-time list price; not China (Beijing), Hong Kong, Frankfurt, Virginia or Tokyo, not Global, US or EU deployment scope, not batch, not limited-time discounts, free quota not subtracted. Billing basis, not a provider alias. Applies to requests with at most 32,000 input tokens per request. The tier is chosen by the total input tokens of the request, including cache hits and cache creation, and every token of the request, including output, bills at that tier. cached_input_tokens is the implicit-cache hit rate (20% of the tier input price per https://www.alibabacloud.com/help/en/model-studio/context-cache); explicit-cache hits bill at 10%, so this overstates them. cache_write_input_tokens is explicit cache creation at 125% of the tier input price (5-minute TTL); implicit caching has no write charge. Reasoning (chain of thought) is output usage. laravel/ai\'s openai-compatible driver drops cache_creation_input_tokens, so through that driver explicit cache creation bills as uncached input, 25% short.',
            'rates' => [
                'input_tokens' => ['amount' => '1', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.2', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '1.25', 'per' => '1000000'],
                'output_tokens' => ['amount' => '5', 'per' => '1000000'],
            ],
        ],
        'dashscope:qwen3-coder-plus-intl-128k' => [
            'source' => 'https://www.alibabacloud.com/help/en/model-studio/model-pricing',
            'notes' => 'Checked 2026-10-10. qwen3-coder-plus (currently qwen3-coder-plus-2025-09-23): Alibaba Cloud Model Studio Singapore region (dashscope-intl), International deployment scope, standard real-time list price; not China (Beijing), Hong Kong, Frankfurt, Virginia or Tokyo, not Global, US or EU deployment scope, not batch, not limited-time discounts, free quota not subtracted. Billing basis, not a provider alias. Applies to requests with more than 32,000 and at most 128,000 input tokens per request. The tier is chosen by the total input tokens of the request, including cache hits and cache creation, and every token of the request, including output, bills at that tier. cached_input_tokens is the implicit-cache hit rate (20% of the tier input price per https://www.alibabacloud.com/help/en/model-studio/context-cache); explicit-cache hits bill at 10%, so this overstates them. cache_write_input_tokens is explicit cache creation at 125% of the tier input price (5-minute TTL); implicit caching has no write charge. Reasoning (chain of thought) is output usage. laravel/ai\'s openai-compatible driver drops cache_creation_input_tokens, so through that driver explicit cache creation bills as uncached input, 25% short.',
            'rates' => [
                'input_tokens' => ['amount' => '1.8', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.36', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '2.25', 'per' => '1000000'],
                'output_tokens' => ['amount' => '9', 'per' => '1000000'],
            ],
        ],
        'dashscope:qwen3-coder-plus-intl-256k' => [
            'source' => 'https://www.alibabacloud.com/help/en/model-studio/model-pricing',
            'notes' => 'Checked 2026-10-10. qwen3-coder-plus (currently qwen3-coder-plus-2025-09-23): Alibaba Cloud Model Studio Singapore region (dashscope-intl), International deployment scope, standard real-time list price; not China (Beijing), Hong Kong, Frankfurt, Virginia or Tokyo, not Global, US or EU deployment scope, not batch, not limited-time discounts, free quota not subtracted. Billing basis, not a provider alias. Applies to requests with more than 128,000 and at most 256,000 input tokens per request. The tier is chosen by the total input tokens of the request, including cache hits and cache creation, and every token of the request, including output, bills at that tier. cached_input_tokens is the implicit-cache hit rate (20% of the tier input price per https://www.alibabacloud.com/help/en/model-studio/context-cache); explicit-cache hits bill at 10%, so this overstates them. cache_write_input_tokens is explicit cache creation at 125% of the tier input price (5-minute TTL); implicit caching has no write charge. Reasoning (chain of thought) is output usage. laravel/ai\'s openai-compatible driver drops cache_creation_input_tokens, so through that driver explicit cache creation bills as uncached input, 25% short.',
            'rates' => [
                'input_tokens' => ['amount' => '3', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.6', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '3.75', 'per' => '1000000'],
                'output_tokens' => ['amount' => '15', 'per' => '1000000'],
            ],
        ],
        'dashscope:qwen3-coder-plus-intl-1m' => [
            'source' => 'https://www.alibabacloud.com/help/en/model-studio/model-pricing',
            'notes' => 'Checked 2026-10-10. qwen3-coder-plus (currently qwen3-coder-plus-2025-09-23): Alibaba Cloud Model Studio Singapore region (dashscope-intl), International deployment scope, standard real-time list price; not China (Beijing), Hong Kong, Frankfurt, Virginia or Tokyo, not Global, US or EU deployment scope, not batch, not limited-time discounts, free quota not subtracted. Billing basis, not a provider alias. Applies to requests with more than 256,000 and at most 1,000,000 input tokens per request. The tier is chosen by the total input tokens of the request, including cache hits and cache creation, and every token of the request, including output, bills at that tier. cached_input_tokens is the implicit-cache hit rate (20% of the tier input price per https://www.alibabacloud.com/help/en/model-studio/context-cache); explicit-cache hits bill at 10%, so this overstates them. cache_write_input_tokens is explicit cache creation at 125% of the tier input price (5-minute TTL); implicit caching has no write charge. Reasoning (chain of thought) is output usage. laravel/ai\'s openai-compatible driver drops cache_creation_input_tokens, so through that driver explicit cache creation bills as uncached input, 25% short.',
            'rates' => [
                'input_tokens' => ['amount' => '6', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '1.2', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '7.5', 'per' => '1000000'],
                'output_tokens' => ['amount' => '60', 'per' => '1000000'],
            ],
        ],
        'dashscope:qwen3-coder-flash-intl-32k' => [
            'source' => 'https://www.alibabacloud.com/help/en/model-studio/model-pricing',
            'notes' => 'Checked 2026-10-10. qwen3-coder-flash (currently qwen3-coder-flash-2025-07-28): Alibaba Cloud Model Studio Singapore region (dashscope-intl), International deployment scope, standard real-time list price; not China (Beijing), Hong Kong, Frankfurt, Virginia or Tokyo, not Global, US or EU deployment scope, not batch, not limited-time discounts, free quota not subtracted. Billing basis, not a provider alias. Applies to requests with at most 32,000 input tokens per request. The tier is chosen by the total input tokens of the request, including cache hits and cache creation, and every token of the request, including output, bills at that tier. cached_input_tokens is the implicit-cache hit rate (20% of the tier input price per https://www.alibabacloud.com/help/en/model-studio/context-cache); explicit-cache hits bill at 10%, so this overstates them. cache_write_input_tokens is explicit cache creation at 125% of the tier input price (5-minute TTL); implicit caching has no write charge. Reasoning (chain of thought) is output usage. laravel/ai\'s openai-compatible driver drops cache_creation_input_tokens, so through that driver explicit cache creation bills as uncached input, 25% short.',
            'rates' => [
                'input_tokens' => ['amount' => '0.3', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.06', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '0.375', 'per' => '1000000'],
                'output_tokens' => ['amount' => '1.5', 'per' => '1000000'],
            ],
        ],
        'dashscope:qwen3-coder-flash-intl-128k' => [
            'source' => 'https://www.alibabacloud.com/help/en/model-studio/model-pricing',
            'notes' => 'Checked 2026-10-10. qwen3-coder-flash (currently qwen3-coder-flash-2025-07-28): Alibaba Cloud Model Studio Singapore region (dashscope-intl), International deployment scope, standard real-time list price; not China (Beijing), Hong Kong, Frankfurt, Virginia or Tokyo, not Global, US or EU deployment scope, not batch, not limited-time discounts, free quota not subtracted. Billing basis, not a provider alias. Applies to requests with more than 32,000 and at most 128,000 input tokens per request. The tier is chosen by the total input tokens of the request, including cache hits and cache creation, and every token of the request, including output, bills at that tier. cached_input_tokens is the implicit-cache hit rate (20% of the tier input price per https://www.alibabacloud.com/help/en/model-studio/context-cache); explicit-cache hits bill at 10%, so this overstates them. cache_write_input_tokens is explicit cache creation at 125% of the tier input price (5-minute TTL); implicit caching has no write charge. Reasoning (chain of thought) is output usage. laravel/ai\'s openai-compatible driver drops cache_creation_input_tokens, so through that driver explicit cache creation bills as uncached input, 25% short.',
            'rates' => [
                'input_tokens' => ['amount' => '0.5', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.1', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '0.625', 'per' => '1000000'],
                'output_tokens' => ['amount' => '2.5', 'per' => '1000000'],
            ],
        ],
        'dashscope:qwen3-coder-flash-intl-256k' => [
            'source' => 'https://www.alibabacloud.com/help/en/model-studio/model-pricing',
            'notes' => 'Checked 2026-10-10. qwen3-coder-flash (currently qwen3-coder-flash-2025-07-28): Alibaba Cloud Model Studio Singapore region (dashscope-intl), International deployment scope, standard real-time list price; not China (Beijing), Hong Kong, Frankfurt, Virginia or Tokyo, not Global, US or EU deployment scope, not batch, not limited-time discounts, free quota not subtracted. Billing basis, not a provider alias. Applies to requests with more than 128,000 and at most 256,000 input tokens per request. The tier is chosen by the total input tokens of the request, including cache hits and cache creation, and every token of the request, including output, bills at that tier. cached_input_tokens is the implicit-cache hit rate (20% of the tier input price per https://www.alibabacloud.com/help/en/model-studio/context-cache); explicit-cache hits bill at 10%, so this overstates them. cache_write_input_tokens is explicit cache creation at 125% of the tier input price (5-minute TTL); implicit caching has no write charge. Reasoning (chain of thought) is output usage. laravel/ai\'s openai-compatible driver drops cache_creation_input_tokens, so through that driver explicit cache creation bills as uncached input, 25% short.',
            'rates' => [
                'input_tokens' => ['amount' => '0.8', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.16', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '1', 'per' => '1000000'],
                'output_tokens' => ['amount' => '4', 'per' => '1000000'],
            ],
        ],
        'dashscope:qwen3-coder-flash-intl-1m' => [
            'source' => 'https://www.alibabacloud.com/help/en/model-studio/model-pricing',
            'notes' => 'Checked 2026-10-10. qwen3-coder-flash (currently qwen3-coder-flash-2025-07-28): Alibaba Cloud Model Studio Singapore region (dashscope-intl), International deployment scope, standard real-time list price; not China (Beijing), Hong Kong, Frankfurt, Virginia or Tokyo, not Global, US or EU deployment scope, not batch, not limited-time discounts, free quota not subtracted. Billing basis, not a provider alias. Applies to requests with more than 256,000 and at most 1,000,000 input tokens per request. The tier is chosen by the total input tokens of the request, including cache hits and cache creation, and every token of the request, including output, bills at that tier. cached_input_tokens is the implicit-cache hit rate (20% of the tier input price per https://www.alibabacloud.com/help/en/model-studio/context-cache); explicit-cache hits bill at 10%, so this overstates them. cache_write_input_tokens is explicit cache creation at 125% of the tier input price (5-minute TTL); implicit caching has no write charge. Reasoning (chain of thought) is output usage. laravel/ai\'s openai-compatible driver drops cache_creation_input_tokens, so through that driver explicit cache creation bills as uncached input, 25% short.',
            'rates' => [
                'input_tokens' => ['amount' => '1.6', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.32', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '2', 'per' => '1000000'],
                'output_tokens' => ['amount' => '9.6', 'per' => '1000000'],
            ],
        ],
        'gemini:gemini-3.8-flash-standard-2026' => [
            'source' => 'https://ai.google.dev/gemini-api/docs/pricing',
            'notes' => 'Checked 2026-10-10. Billing basis for gemini-3.8-flash on the Gemini Developer API (generateContent or Interactions), paid tier, standard service tier (service_tier omitted or standard), for requests made through 2026-12-31; not a provider alias. Google publishes these introductory rates as valid through December 31, 2026 and doubles every rate from January 1, 2027: use gemini-3.8-flash-standard-2027 from then on. One input rate covers text, image, video and audio; documents bill at the image rate. No long-context tier. Output includes thinking tokens; no separate reasoning rate. cached_input_tokens covers implicit and explicit cache hits (input partitions are exclusive). Google publishes no per-token cache-write fee, so cache_write_input_tokens stays unpriced. Explicit-cache storage (USD 0.50 per million token-hours) is excluded and unpriced. web_searches counts individual Google Search grounding queries the model executes (USD 14 per 1,000); the monthly free allowance of 5,000 is not subtracted. Google Maps grounding is excluded. Excludes batch, flex, priority (a downgraded priority request bills at standard; see the x-gemini-service-tier response header), Live API and Vertex AI / Gemini Enterprise Agent Platform pricing. The package treats the price change as taking effect at 2027-01-01T00:00Z because Google states no timezone.',
            'rates' => [
                'input_tokens' => ['amount' => '0.75', 'per' => '1000000'],
                'output_tokens' => ['amount' => '3.75', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.075', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.014', 'per' => '1'],
            ],
        ],
        'gemini:gemini-3.8-flash-standard-2027' => [
            'source' => 'https://ai.google.dev/gemini-api/docs/pricing',
            'notes' => 'Checked 2026-10-10. Billing basis for gemini-3.8-flash on the Gemini Developer API, paid tier, standard service tier, for requests made on or after 2027-01-01, when Google publishes the post-introductory rates; not a provider alias. Use gemini-3.8-flash-standard-2026 before then. One input rate covers text, image, video and audio. No long-context tier. Output includes thinking tokens. Exclusive input/cache-hit partitions; cache writes unpriced (no published per-token fee). Explicit-cache storage (USD 1.00 per million token-hours) is excluded and unpriced. web_searches is per Google Search grounding query at USD 14 per 1,000, free monthly allowance not subtracted. Google Maps grounding is excluded. Excludes batch, flex, priority, Live API and Vertex AI pricing. Re-review before 2027-01-01 in case Google changes the announced rates. The package treats the price change as taking effect at 2027-01-01T00:00Z because Google states no timezone.',
            'rates' => [
                'input_tokens' => ['amount' => '1.50', 'per' => '1000000'],
                'output_tokens' => ['amount' => '7.50', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.15', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.014', 'per' => '1'],
            ],
        ],
        'gemini:gemini-3.6-flash-standard-2026' => [
            'source' => 'https://ai.google.dev/gemini-api/docs/pricing',
            'notes' => 'Checked 2026-10-10. Billing basis for gemini-3.6-flash on the Gemini Developer API, paid tier, standard service tier, for requests made through 2026-12-31; not a provider alias. Requests to the retired gemini-3.5-flash ID are routed to gemini-3.6-flash per https://ai.google.dev/gemini-api/docs/deprecations, but that ID is fallback-blocked, not aliased. Introductory rates valid through December 31, 2026; every rate doubles from January 1, 2027 (use gemini-3.6-flash-standard-2027). One input rate covers text, image, video and audio. No long-context tier. Output includes thinking tokens. Exclusive input/cache-hit partitions; cache writes unpriced. Explicit-cache storage (USD 0.50 per million token-hours) is excluded and unpriced. web_searches is per Google Search grounding query at USD 14 per 1,000, free monthly allowance not subtracted. Google Maps grounding is excluded. Excludes batch, flex, priority, Live API and Vertex AI pricing. The package treats the price change as taking effect at 2027-01-01T00:00Z because Google states no timezone.',
            'rates' => [
                'input_tokens' => ['amount' => '0.75', 'per' => '1000000'],
                'output_tokens' => ['amount' => '3.75', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.075', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.014', 'per' => '1'],
            ],
        ],
        'gemini:gemini-3.6-flash-standard-2027' => [
            'source' => 'https://ai.google.dev/gemini-api/docs/pricing',
            'notes' => 'Checked 2026-10-10. Billing basis for gemini-3.6-flash on the Gemini Developer API, paid tier, standard service tier, for requests made on or after 2027-01-01; not a provider alias. Use gemini-3.6-flash-standard-2026 before then. One input rate covers text, image, video and audio. No long-context tier. Output includes thinking tokens. Exclusive input/cache-hit partitions; cache writes unpriced. Explicit-cache storage (USD 1.00 per million token-hours) is excluded and unpriced. web_searches is per Google Search grounding query at USD 14 per 1,000, free monthly allowance not subtracted. Google Maps grounding is excluded. Excludes batch, flex, priority, Live API and Vertex AI pricing. Re-review before 2027-01-01. The package treats the price change as taking effect at 2027-01-01T00:00Z because Google states no timezone.',
            'rates' => [
                'input_tokens' => ['amount' => '1.50', 'per' => '1000000'],
                'output_tokens' => ['amount' => '7.50', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.15', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.014', 'per' => '1'],
            ],
        ],
        'gemini:gemini-3.5-flash-lite-standard' => [
            'source' => 'https://ai.google.dev/gemini-api/docs/pricing',
            'notes' => 'Checked 2026-10-10. Billing basis for gemini-3.5-flash-lite on the Gemini Developer API, paid tier, standard service tier; not a provider alias. One input rate covers text, image, video and audio. No long-context tier. Output includes thinking tokens. Exclusive input/cache-hit partitions; cache writes unpriced (no published per-token fee). Explicit-cache storage (USD 1.00 per million token-hours) is excluded and unpriced. web_searches is per Google Search grounding query at USD 14 per 1,000, free monthly allowance not subtracted. Google Maps grounding is excluded. Excludes batch, flex, priority, Live API and Vertex AI pricing.',
            'rates' => [
                'input_tokens' => ['amount' => '0.30', 'per' => '1000000'],
                'output_tokens' => ['amount' => '2.50', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.03', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.014', 'per' => '1'],
            ],
        ],
        'gemini:gemini-3.1-flash-lite-standard' => [
            'source' => 'https://ai.google.dev/gemini-api/docs/pricing',
            'notes' => 'Checked 2026-10-10. Billing basis for gemini-3.1-flash-lite on the Gemini Developer API, paid tier, standard service tier, text, image and video input only; not a provider alias. Audio input bills at USD 0.50/M (cache hits 0.05/M) and is excluded: the package has no audio input-token unit, so do not use this basis for requests with audio. Documents bill at the image rate. No long-context tier. Output includes thinking tokens. Exclusive input/cache-hit partitions; cache writes unpriced. Explicit-cache storage (USD 1.00 per million token-hours) is excluded and unpriced. web_searches is per Google Search grounding query at USD 14 per 1,000, free monthly allowance not subtracted. Google Maps grounding is excluded. Google lists a shutdown date of 2027-05-07 (replacement gemini-3.5-flash-lite). Excludes batch, flex, priority and Vertex AI pricing.',
            'rates' => [
                'input_tokens' => ['amount' => '0.25', 'per' => '1000000'],
                'output_tokens' => ['amount' => '1.50', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.025', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.014', 'per' => '1'],
            ],
        ],
        'gemini:gemini-3.1-pro-preview-standard-short' => [
            'source' => 'https://ai.google.dev/gemini-api/docs/pricing',
            'notes' => 'Checked 2026-10-10. Billing basis for gemini-3.1-pro-preview on the Gemini Developer API, paid tier, standard service tier, prompts of at most 200,000 tokens (counting cached tokens); not a provider alias. Above 200,000 prompt tokens use gemini-3.1-pro-preview-standard-long. One input rate for all modalities. Output includes thinking tokens. Exclusive input/cache-hit partitions; cache writes unpriced. Explicit-cache storage (USD 4.50 per million token-hours, not context-tiered) is excluded and unpriced. web_searches is per Google Search grounding query at USD 14 per 1,000, free monthly allowance not subtracted. Google Maps grounding is excluded. Preview model with no announced shutdown date. Excludes batch, flex, priority and Vertex AI pricing.',
            'rates' => [
                'input_tokens' => ['amount' => '2', 'per' => '1000000'],
                'output_tokens' => ['amount' => '12', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.20', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.014', 'per' => '1'],
            ],
        ],
        'gemini:gemini-3.1-pro-preview-standard-long' => [
            'source' => 'https://ai.google.dev/gemini-api/docs/pricing',
            'notes' => 'Checked 2026-10-10. Billing basis for gemini-3.1-pro-preview on the Gemini Developer API, paid tier, standard service tier, prompts of more than 200,000 tokens (counting cached tokens); the higher tier applies to the whole request. Not a provider alias. One input rate for all modalities. Output includes thinking tokens. Exclusive input/cache-hit partitions; cache writes unpriced. Explicit-cache storage (USD 4.50 per million token-hours) is excluded and unpriced. web_searches is per Google Search grounding query at USD 14 per 1,000, free monthly allowance not subtracted. Google Maps grounding is excluded. Excludes batch, flex, priority and Vertex AI pricing.',
            'rates' => [
                'input_tokens' => ['amount' => '4', 'per' => '1000000'],
                'output_tokens' => ['amount' => '18', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.40', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.014', 'per' => '1'],
            ],
        ],
        'gemini:gemini-3.1-pro-preview-customtools-standard-short' => [
            'source' => 'https://ai.google.dev/gemini-api/docs/pricing',
            'notes' => 'Checked 2026-10-10. Billing basis for the gemini-3.1-pro-preview-customtools endpoint, which Google prices the same as gemini-3.1-pro-preview: paid tier, standard service tier, prompts of at most 200,000 tokens (counting cached tokens). Not a provider alias. One input rate for all modalities. Output includes thinking tokens. Exclusive input/cache-hit partitions; cache writes unpriced. Explicit-cache storage (USD 4.50 per million token-hours) is excluded and unpriced. web_searches is per Google Search grounding query at USD 14 per 1,000, free monthly allowance not subtracted. Google Maps grounding is excluded. Excludes batch, flex, priority and Vertex AI pricing.',
            'rates' => [
                'input_tokens' => ['amount' => '2', 'per' => '1000000'],
                'output_tokens' => ['amount' => '12', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.20', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.014', 'per' => '1'],
            ],
        ],
        'gemini:gemini-3.1-pro-preview-customtools-standard-long' => [
            'source' => 'https://ai.google.dev/gemini-api/docs/pricing',
            'notes' => 'Checked 2026-10-10. Billing basis for the gemini-3.1-pro-preview-customtools endpoint, priced the same as gemini-3.1-pro-preview: paid tier, standard service tier, prompts of more than 200,000 tokens (counting cached tokens); the higher tier applies to the whole request. Not a provider alias. Output includes thinking tokens. Exclusive input/cache-hit partitions; cache writes unpriced. Explicit-cache storage (USD 4.50 per million token-hours) is excluded and unpriced. web_searches is per Google Search grounding query at USD 14 per 1,000, free monthly allowance not subtracted. Google Maps grounding is excluded. Excludes batch, flex, priority and Vertex AI pricing.',
            'rates' => [
                'input_tokens' => ['amount' => '4', 'per' => '1000000'],
                'output_tokens' => ['amount' => '18', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.40', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.014', 'per' => '1'],
            ],
        ],
        'gemini:gemini-3-flash-preview-standard' => [
            'source' => 'https://ai.google.dev/gemini-api/docs/pricing',
            'notes' => 'Checked 2026-10-10. Billing basis for gemini-3-flash-preview (legacy Flash preview, no announced shutdown; replacement gemini-3.6-flash) on the Gemini Developer API, paid tier, standard service tier, text, image and video input only; not a provider alias. Audio input bills at USD 1.00/M (cache hits 0.10/M) and is excluded. No long-context tier. Output includes thinking tokens. Exclusive input/cache-hit partitions; cache writes unpriced. Explicit-cache storage (USD 1.00 per million token-hours) is excluded and unpriced. web_searches is per Google Search grounding query at USD 14 per 1,000, free monthly allowance not subtracted. Google Maps grounding is excluded. Excludes batch, flex, priority and Vertex AI pricing.',
            'rates' => [
                'input_tokens' => ['amount' => '0.50', 'per' => '1000000'],
                'output_tokens' => ['amount' => '3', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.05', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.014', 'per' => '1'],
            ],
        ],
        'gemini:gemini-2.5-pro-standard-short' => [
            'source' => 'https://ai.google.dev/gemini-api/docs/pricing',
            'notes' => 'Checked 2026-10-10. Billing basis for gemini-2.5-pro on the Gemini Developer API, paid tier, standard service tier, prompts of at most 200,000 tokens (counting cached tokens); not a provider alias. Google limits 2.5 access to existing users; no shutdown date announced. One input rate for all modalities. Output includes thinking tokens. Exclusive input/cache-hit partitions; cache writes unpriced. Explicit-cache storage (USD 4.50 per million token-hours) is excluded and unpriced. Google Search and Google Maps grounding are excluded: Gemini 2.5 bills them per grounded prompt (USD 35 and 25 per 1,000), not per query, so do not pass web_searches for them. Excludes batch, flex, priority and Vertex AI pricing.',
            'rates' => [
                'input_tokens' => ['amount' => '1.25', 'per' => '1000000'],
                'output_tokens' => ['amount' => '10', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.125', 'per' => '1000000'],
            ],
        ],
        'gemini:gemini-2.5-pro-standard-long' => [
            'source' => 'https://ai.google.dev/gemini-api/docs/pricing',
            'notes' => 'Checked 2026-10-10. Billing basis for gemini-2.5-pro on the Gemini Developer API, paid tier, standard service tier, prompts of more than 200,000 tokens (counting cached tokens); the higher tier applies to the whole request. Not a provider alias. Output includes thinking tokens. Exclusive input/cache-hit partitions; cache writes unpriced. Explicit-cache storage (USD 4.50 per million token-hours) is excluded and unpriced. Google Search and Google Maps grounding are excluded: Gemini 2.5 bills them per grounded prompt (USD 35 and 25 per 1,000), not per query, so do not pass web_searches for them. Excludes batch, flex, priority and Vertex AI pricing.',
            'rates' => [
                'input_tokens' => ['amount' => '2.50', 'per' => '1000000'],
                'output_tokens' => ['amount' => '15', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.25', 'per' => '1000000'],
            ],
        ],
        'gemini:gemini-2.5-flash-standard' => [
            'source' => 'https://ai.google.dev/gemini-api/docs/pricing',
            'notes' => 'Checked 2026-10-10. Billing basis for gemini-2.5-flash on the Gemini Developer API, paid tier, standard service tier, text, image and video input only; not a provider alias. Google limits 2.5 access to existing users; no shutdown date announced. Audio input bills at USD 1.00/M (cache hits 0.10/M) and is excluded. No long-context tier. Output includes thinking tokens. Exclusive input/cache-hit partitions; cache writes unpriced. Explicit-cache storage (USD 1.00 per million token-hours) is excluded and unpriced. Google Search and Google Maps grounding are excluded: Gemini 2.5 bills them per grounded prompt (USD 35 and 25 per 1,000), not per query, so do not pass web_searches for them. Excludes batch, flex, priority and Vertex AI pricing.',
            'rates' => [
                'input_tokens' => ['amount' => '0.30', 'per' => '1000000'],
                'output_tokens' => ['amount' => '2.50', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.03', 'per' => '1000000'],
            ],
        ],
        'gemini:gemini-2.5-flash-lite-standard' => [
            'source' => 'https://ai.google.dev/gemini-api/docs/pricing',
            'notes' => 'Checked 2026-10-10. Billing basis for gemini-2.5-flash-lite on the Gemini Developer API, paid tier, standard service tier, text, image and video input only; not a provider alias. Google limits 2.5 access to existing users; no shutdown date announced. Audio input bills at USD 0.30/M (cache hits 0.03/M) and is excluded. No long-context tier. Output includes thinking tokens. Exclusive input/cache-hit partitions; cache writes unpriced. Explicit-cache storage (USD 1.00 per million token-hours) is excluded and unpriced. Google Search and Google Maps grounding are excluded: Gemini 2.5 bills them per grounded prompt (USD 35 and 25 per 1,000), not per query, so do not pass web_searches for them. Excludes batch, flex, priority and Vertex AI pricing.',
            'rates' => [
                'input_tokens' => ['amount' => '0.10', 'per' => '1000000'],
                'output_tokens' => ['amount' => '0.40', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.01', 'per' => '1000000'],
            ],
        ],
        'cloudflare:@cf/cloudflare/clef' => [
            'source' => 'https://developers.cloudflare.com/workers-ai/platform/pricing/',
            'notes' => 'Checked 2026-10-09. Workers AI input-only unit price confirmed at https://developers.cloudflare.com/workers-ai/models/clef/. USD 0.24/M input tokens, equivalent to neuron billing. No output rate is published; do not invent a free output-token unit. Retail paid usage before the shared daily free neuron allowance; do not subtract that allowance per request.',
            'rates' => [
                'input_tokens' => ['amount' => '0.24', 'per' => '1000000'],
            ],
        ],
        'cloudflare:@cf/cloudflare/clef-flash' => [
            'source' => 'https://developers.cloudflare.com/workers-ai/platform/pricing/',
            'notes' => 'Checked 2026-10-09. Workers AI input-only unit price confirmed at https://developers.cloudflare.com/workers-ai/models/clef-flash/. USD 0.09/M input tokens, equivalent to neuron billing. No output rate is published; do not invent a free output-token unit. Retail paid usage before the shared daily free neuron allowance; do not subtract that allowance per request.',
            'rates' => [
                'input_tokens' => ['amount' => '0.09', 'per' => '1000000'],
            ],
        ],
        'anthropic:claude-sonnet-5-global' => [
            'source' => 'https://platform.claude.com/docs/en/about-claude/pricing',
            'notes' => 'Checked 2026-10-10. Billing basis for claude-sonnet-5 (Claude Sonnet 5, legacy) with global geography (inference_geo global, the default), synchronous Messages API only; not a provider alias. The USD 2/M input and 10/M output launch price is now the standard price; the scheduled 2026-09-01 increase to 3/15 did not occur (pricing page footnote 3). All context lengths up to 1M tokens bill at one rate; no fast mode. Cache reads are 0.1x input (0.20/M), not the 0.05x of Sonnet 5.5. Excludes US geography (1.1x), batch, Priority Tier, Bedrock, Google Cloud and Foundry. Thinking is output usage. Cache input partitions are exclusive; write units distinguish 5-minute and 1-hour TTLs, and no generic cache-write rate is published. web_searches counts usage.server_tool_use.web_search_requests at USD 10 per 1,000 searches (failed searches are not billed; the adapters map it from non-streamed responses). Code execution, web fetch tokens and other server tools are not priced here.',
            'rates' => [
                'input_tokens' => ['amount' => '2', 'per' => '1000000'],
                'output_tokens' => ['amount' => '10', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.20', 'per' => '1000000'],
                'cache_write_input_tokens_5m' => ['amount' => '2.50', 'per' => '1000000'],
                'cache_write_input_tokens_1h' => ['amount' => '4', 'per' => '1000000'],
                'web_searches' => ['amount' => '10', 'per' => '1000'],
            ],
        ],
        'brave:answers' => [
            'source' => 'https://api-dashboard.search.brave.com/documentation/services/answers',
            'notes' => 'Checked 2026-09-03. Carried forward unchanged from snapshot v3.',
            'rates' => [
                'queries' => ['amount' => '4', 'per' => '1000'],
                'input_tokens' => ['amount' => '5', 'per' => '1000000'],
                'output_tokens' => ['amount' => '5', 'per' => '1000000'],
            ],
        ],
        'brave:search' => [
            'source' => 'https://api-dashboard.search.brave.com/documentation/pricing',
            'notes' => 'Checked 2026-09-10. Search API price per request.',
            'rates' => [
                'requests' => ['amount' => '5', 'per' => '1000'],
            ],
        ],
        'dataforseo:chatgpt-llm-scraper-standard' => [
            'source' => 'https://dataforseo.com/pricing/ai-optimization/llm-scraper',
            'notes' => 'Checked 2026-09-06. Base normal-priority Standard price per result page. One requests unit is one billable task/result-page submission; retrieval GETs are free. Excludes high-priority, bulk, HTML, rectangle, and surcharged options. Estimate only; actual provider cost wins and failures may be charged.',
            'rates' => [
                'requests' => ['amount' => '0.0012', 'per' => '1'],
            ],
        ],
        'dataforseo:chatgpt-llm-scraper-live' => [
            'source' => 'https://dataforseo.com/pricing/ai-optimization/llm-scraper',
            'notes' => 'Checked 2026-09-06. Base Live price per result page. One requests unit is one billable task/result-page submission. Excludes high-priority, bulk, HTML, rectangle, and surcharged options. Estimate only; actual provider cost wins and failures may be charged.',
            'rates' => [
                'requests' => ['amount' => '0.004', 'per' => '1'],
            ],
        ],
        'dataforseo:gemini-llm-scraper-standard' => [
            'source' => 'https://dataforseo.com/pricing/ai-optimization/llm-scraper',
            'notes' => 'Checked 2026-09-06. Base normal-priority Standard price per result page. One requests unit is one billable task/result-page submission; retrieval GETs are free. Excludes high-priority, bulk, HTML, rectangle, and surcharged options. Estimate only; actual provider cost wins and failures may be charged.',
            'rates' => [
                'requests' => ['amount' => '0.0012', 'per' => '1'],
            ],
        ],
        'dataforseo:gemini-llm-scraper-live' => [
            'source' => 'https://dataforseo.com/pricing/ai-optimization/llm-scraper',
            'notes' => 'Checked 2026-09-06. Base Live price per result page. One requests unit is one billable task/result-page submission. Excludes high-priority, bulk, HTML, rectangle, and surcharged options. Estimate only; actual provider cost wins and failures may be charged.',
            'rates' => [
                'requests' => ['amount' => '0.004', 'per' => '1'],
            ],
        ],
        'dataforseo:google-ai-mode-standard' => [
            'source' => 'https://dataforseo.com/pricing/serp/google-ai-mode-serp-api',
            'notes' => 'Checked 2026-09-06. Base normal-priority Standard price per SERP page. One requests unit is one billable task/result-page submission; retrieval GETs are free. Excludes high-priority, bulk, HTML, rectangle, and surcharged options. Estimate only; actual provider cost wins and failures may be charged.',
            'rates' => [
                'requests' => ['amount' => '0.0012', 'per' => '1'],
            ],
        ],
        'dataforseo:google-ai-mode-live' => [
            'source' => 'https://dataforseo.com/pricing/serp/google-ai-mode-serp-api',
            'notes' => 'Checked 2026-09-06. Base Live price per SERP page. One requests unit is one billable task/result-page submission. Excludes high-priority, bulk, HTML, rectangle, and surcharged options. Estimate only; actual provider cost wins and failures may be charged.',
            'rates' => [
                'requests' => ['amount' => '0.004', 'per' => '1'],
            ],
        ],
        'exa:search' => [
            'source' => 'https://exa.ai/docs/reference/pricing.md',
            'notes' => 'Checked 2026-09-03. Carried forward unchanged from snapshot v3.',
            'rates' => [
                'requests' => ['amount' => '7', 'per' => '1000'],
                'additional_results' => ['amount' => '1', 'per' => '1000'],
                'summary_pages' => ['amount' => '1', 'per' => '1000'],
            ],
        ],
        'exa:research' => [
            'source' => 'https://exa.ai/docs/reference/pricing',
            'notes' => 'Checked 2026-09-10. Auto-effort Agent API usage; fixed-effort runs and enrichment have different rates. Prefer costDollars.total when reported.',
            'rates' => [
                'agent_compute_units' => ['amount' => '0.1', 'per' => '1'],
                'searches' => ['amount' => '0.005', 'per' => '1'],
            ],
        ],
        'kagi:fastgpt' => [
            'source' => 'https://help.kagi.com/kagi/api/fastgpt.html',
            'notes' => 'Checked 2026-09-03. Carried forward unchanged from snapshot v3.',
            'rates' => [
                'uncached_queries' => ['amount' => '15', 'per' => '1000'],
            ],
        ],
        'openrouter:openai/gpt-5.6-terra-272k' => [
            'source' => 'https://openrouter.ai/api/v1/models/openai/gpt-5.6-terra-20260709/endpoints',
            'notes' => 'Checked 2026-09-18. Verified against the dated canonical endpoints catalog, the server-tool web-search guide (https://openrouter.ai/docs/guides/features/server-tools/web-search), and the prompt-caching guide (https://openrouter.ai/docs/guides/best-practices/prompt-caching). Synthetic worst-case billing basis for the bounded openai/gpt-5.6-terra grounded route: every completion billed at the >=272,000-prompt long-context override rates of the default-tier OpenAI endpoints (routing tag "openai"; provider order "openai" excludes opt-in service tiers such as openai/flex and openai/fast, which are the only other OpenAI-tagged endpoints today). The generic openrouter:openai/gpt-5.6-terra identity stays unpriced here so aggregate model rates never satisfy this basis. exa_search_requests is the Exa engine fee (auto mode, USD 0.007 per request, up to 10 results included) and is deliberately distinct from the native web_search passthrough: this identity intentionally declares no web_searches rate. Cache-write (1.25x) and cache-read multipliers apply to the applicable long-context input rate; the catalog override values already compound them (cache write 5/M = 1.25 x 4/M input). The model catalog publishes no separate internal_reasoning rate, so reasoning is billed as completion tokens and no reasoning rate is declared. Snapshot invalidation: any catalog change to these endpoints (rates, override thresholds, the default-tier OpenAI endpoint set), any new OpenAI default-tier endpoint with higher rates, or any Exa engine fee change invalidates this entry and requires re-review per .agents/skills/fetching-provider-pricing; a fresh catalog read is mandatory before refresh.',
            'rates' => [
                'input_tokens' => ['amount' => '4', 'per' => '1000000'],
                'output_tokens' => ['amount' => '18', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.4', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '5', 'per' => '1000000'],
                'exa_search_requests' => ['amount' => '0.007', 'per' => '1'],
            ],
        ],
        'openrouter:openai/gpt-6-luna-272k' => [
            'source' => 'https://openrouter.ai/api/v1/models/openai/gpt-6-luna-20260922/endpoints',
            'notes' => 'Checked 2026-09-23. Synthetic worst-case billing basis for regular GPT-6 Luna, not Luna Pro, using the dated canonical endpoints catalog and Exa pricing from https://openrouter.ai/docs/guides/features/server-tools/web-search. Restricted to default-tier OpenAI routing tag "openai", excluding flex, fast and other providers. Every completion uses the >=272,000-prompt override rates: input $0.2/M, output $0.75/M, cache read $0.02/M and cache write $0.25/M. Input partitions are exclusive; cache-write already includes the 1.25x premium and dominates the reservation. These conservative rates are not an assertion that a short request is billed at the long-context tier. Reasoning, including effort max, is part of completion usage; no additional reasoning unit is priced. exa_search_requests covers only Exa auto at $0.007/request with up to ten results, not the native web_search fee or other search modes. Generic and Pro identities are not aliases for this basis. Snapshot invalidation: changes to the canonical revision, default-tier endpoint set, context threshold, token/cache rates or Exa fee require fresh official-source review before use; this snapshot is not an upstream spend cap.',
            'rates' => [
                'input_tokens' => ['amount' => '0.2', 'per' => '1000000'],
                'output_tokens' => ['amount' => '0.75', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.02', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '0.25', 'per' => '1000000'],
                'exa_search_requests' => ['amount' => '0.007', 'per' => '1'],
            ],
        ],
        'openrouter:google/gemini-3.1-flash-lite-standard-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/google/gemini-3.1-flash-lite-20260507/endpoints',
            'notes' => 'Checked 2026-10-09. Synthetic worst-case billing basis for stable google/gemini-3.1-flash-lite (canonical google/gemini-3.1-flash-lite-20260507) under OpenRouter default routing at the standard service tier; not the preview, image or :batch variants. Default routing load-balances across google-ai-studio and google-vertex/global (USD 0.25/M input, 1.50/M output, 0.025/M cache read) and the regional google-vertex/us and google-vertex/eu endpoints (0.275, 1.65 and 0.0275, a 1.1x regional uplift), so every request is priced at the regional rates. Flex and priority endpoints are opt-in service tiers (service_tier, :floor, :nitro or tier-suffixed slugs) and are excluded; callers must not opt into them. Upstream standard paid rates confirmed at https://ai.google.dev/gemini-api/docs/pricing: 0.25/M text, image and video input, 0.50/M audio input, 1.50/M output including thinking, 0.025/M context caching plus 1.00/M tokens per hour of storage, and no long-context tier. Reasoning is output usage. Audio input is excluded. Cache writes are deliberately unpriced: the endpoint catalog lists input_cache_write at 0.0833/M (five minutes of storage) while https://openrouter.ai/docs/guides/best-practices/prompt-caching bills a Gemini cache write at the input price plus five minutes of storage, so a cache_write_input_tokens count stays missing. Implicit caching has no write cost. Cache reads use the 0.1x rate that the endpoint catalog and Google publish, not the guide\'s generic 0.25x Gemini constant. web_searches counts native Google Search grounding queries only (usage.server_tool_use.web_search_requests with the native engine), passed through at USD 14 per 1,000 per https://openrouter.ai/docs/guides/features/server-tools/web-search; Google\'s monthly free allowance is not subtracted. Exa and other engines, including auto with domain filters, are distinct units and stay unpriced. The generic openrouter:google/gemini-3.1-flash-lite identity is fallback-blocked and not priced by this snapshot. Snapshot invalidation: a change to the canonical revision, default-tier endpoint set, token rates or search pricing requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '0.275', 'per' => '1000000'],
                'output_tokens' => ['amount' => '1.65', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.0275', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.014', 'per' => '1'],
            ],
        ],
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
        'openrouter:google/gemini-3.6-flash-standard-max-2026' => [
            'source' => 'https://openrouter.ai/api/v1/models/google/gemini-3.6-flash-20260721/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for google/gemini-3.6-flash (canonical google/gemini-3.6-flash-20260721) under OpenRouter default routing at the standard service tier, for requests made through 2026-12-31; not the :batch variant. Default routing spans google-ai-studio and google-vertex/global (USD 0.75/M input, 3.75/M output, 0.075/M cache read) and the regional google-vertex/us endpoint (0.825, 4.125 and 0.0825, a 1.1x uplift), so every request is priced at the regional rates; this can overstate a global request by 10%. Flex and priority endpoints are opt-in service tiers and are excluded. Upstream standard rates confirmed at https://ai.google.dev/gemini-api/docs/pricing as introductory through December 31, 2026; Google doubles them from January 1, 2027 and OpenRouter has published no 2027 rate, so this basis is invalid from 2027-01-01 and no 2027 basis is proposed. Reasoning is output usage. Cache writes are deliberately unpriced: the catalog lists input_cache_write at 0.0417/M (five minutes of storage only) while https://openrouter.ai/docs/guides/best-practices/prompt-caching bills a Gemini cache write at the input price plus five minutes of storage. Cache reads use the 0.1x catalog rate, not the guide generic 0.25x constant. web_searches counts native Google Search grounding queries only, passed through at USD 14 per 1,000 per https://openrouter.ai/docs/guides/features/server-tools/web-search; Exa and other engines stay unpriced. The generic openrouter:google/gemini-3.6-flash identity is fallback-blocked. Snapshot invalidation: a change to the canonical revision, default-tier endpoint set, token rates or search pricing requires fresh review. The package treats the price change as taking effect at 2027-01-01T00:00Z because Google states no timezone.',
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
            'notes' => 'Checked 2026-10-10. Billing basis for google/gemini-3.1-pro-preview (canonical google/gemini-3.1-pro-preview-20260219) under OpenRouter default routing at the standard service tier, prompts of at most 200,000 tokens; not the :batch or customtools variants. Both default-tier endpoints (google-ai-studio, google-vertex/global) charge USD 2/M input, 12/M output and 0.20/M cache read, matching https://ai.google.dev/gemini-api/docs/pricing. The catalog applies its override (4/M, 18/M, 0.40/M) when total prompt tokens are strictly greater than 200,000; the package OpenRouter source cannot apply pricing overrides and leaves the generic identity unpriced, which is also fallback-blocked, and callers choose -standard-short or -standard-long from the actual prompt length. Flex and priority endpoints are excluded. Reasoning is output usage. Cache writes are deliberately unpriced (catalog storage-only 0.375/M conflicts with the OpenRouter prompt-caching guide). web_searches counts native Google Search grounding queries only at USD 14 per 1,000. Snapshot invalidation: a change to the canonical revision, endpoint set, override threshold, token rates or search pricing requires fresh review.',
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
            'notes' => 'Checked 2026-10-10. Billing basis for google/gemini-3.1-pro-preview-customtools (canonical google/gemini-3.1-pro-preview-customtools-20260219), whose only endpoint is google-ai-studio at the standard tier, prompts of at most 200,000 tokens: USD 2/M input, 12/M output, 0.20/M cache read, matching https://ai.google.dev/gemini-api/docs/pricing. The catalog override (4/M, 18/M, 0.40/M above 200,000 prompt tokens) cannot be applied by the package OpenRouter source, which leaves the generic identity unpriced; it is also fallback-blocked. Reasoning is output usage. Cache writes are deliberately unpriced. web_searches counts native Google Search grounding queries only at USD 14 per 1,000. Snapshot invalidation: a change to the canonical revision, endpoint set, override threshold, token rates or search pricing requires fresh review.',
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
        'openrouter:openai/gpt-6.1-sol-standard-max-short' => [
            'source' => 'https://openrouter.ai/api/v1/models/openai/gpt-6.1-sol-20260929/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for openai/gpt-6.1-sol (canonical openai/gpt-6.1-sol-20260929) under OpenRouter default routing at the standard service tier, prompts below 272,000 tokens; not an alias. Default routing spans azure, openai at USD 2/M input and 10/M output; amazon-bedrock/us-east-1, azure/eu, azure/us at USD 2.2/M input and 11/M output; every request is priced at the most expensive default-routed endpoint, so a request served at the base rate is overstated. Opt-in tiers (openai/flex, openai/fast, openai/ultrafast) are excluded; callers must not select them. Reasoning is output usage; no internal_reasoning rate is published. Cache writes bill at 1.25x the applicable input rate, matching OpenAI and https://openrouter.ai/docs/guides/best-practices/prompt-caching; input partitions are exclusive. web_searches counts native OpenAI web searches only, passed through at USD 0.01 per search per https://openrouter.ai/docs/guides/features/server-tools/web-search; Exa and other engines are distinct, unpriced units. The generic openrouter:openai/gpt-6.1-sol identity is fallback-blocked. Snapshot invalidation: a change to the canonical revision, the default-routed endpoint set, token or cache rates, override threshold or search pricing requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '2.2', 'per' => '1000000'],
                'output_tokens' => ['amount' => '11', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.11', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '2.75', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.01', 'per' => '1'],
            ],
        ],
        'openrouter:openai/gpt-6.1-sol-standard-max-long' => [
            'source' => 'https://openrouter.ai/api/v1/models/openai/gpt-6.1-sol-20260929/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for openai/gpt-6.1-sol (canonical openai/gpt-6.1-sol-20260929) under OpenRouter default routing at the standard service tier, prompts of at least 272,000 tokens (the catalog override threshold is inclusive at 272,000, unlike the direct API, and applies to the whole request); not an alias. Default routing spans azure, openai at USD 4/M input and 15/M output; amazon-bedrock/us-east-1, azure/eu, azure/us at USD 4.4/M input and 16.5/M output; every request is priced at the most expensive default-routed endpoint, so a request served at the base rate is overstated. Opt-in tiers (openai/flex, openai/fast, openai/ultrafast) are excluded; callers must not select them. Reasoning is output usage; no internal_reasoning rate is published. Cache writes bill at 1.25x the applicable input rate, matching OpenAI and https://openrouter.ai/docs/guides/best-practices/prompt-caching; input partitions are exclusive. web_searches counts native OpenAI web searches only, passed through at USD 0.01 per search per https://openrouter.ai/docs/guides/features/server-tools/web-search; Exa and other engines are distinct, unpriced units. The generic openrouter:openai/gpt-6.1-sol identity is fallback-blocked. Snapshot invalidation: a change to the canonical revision, the default-routed endpoint set, token or cache rates, override threshold or search pricing requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '4.4', 'per' => '1000000'],
                'output_tokens' => ['amount' => '16.5', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.22', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '5.5', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.01', 'per' => '1'],
            ],
        ],
        'openrouter:openai/gpt-6.1-sol-pro-standard-max-short' => [
            'source' => 'https://openrouter.ai/api/v1/models/openai/gpt-6.1-sol-pro-20260929/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for openai/gpt-6.1-sol-pro (canonical openai/gpt-6.1-sol-pro-20260929) under OpenRouter default routing at the standard service tier, prompts below 272,000 tokens; not an alias. Default routing spans azure, openai at USD 2/M input and 10/M output; azure/eu, azure/us at USD 2.2/M input and 11/M output; every request is priced at the most expensive default-routed endpoint, so a request served at the base rate is overstated. Opt-in tiers (openai/flex, openai/fast, openai/ultrafast) are excluded; callers must not select them. Reasoning is output usage; no internal_reasoning rate is published. Cache writes bill at 1.25x the applicable input rate, matching OpenAI and https://openrouter.ai/docs/guides/best-practices/prompt-caching; input partitions are exclusive. web_searches counts native OpenAI web searches only, passed through at USD 0.01 per search per https://openrouter.ai/docs/guides/features/server-tools/web-search; Exa and other engines are distinct, unpriced units. The generic openrouter:openai/gpt-6.1-sol-pro identity is fallback-blocked. Snapshot invalidation: a change to the canonical revision, the default-routed endpoint set, token or cache rates, override threshold or search pricing requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '2.2', 'per' => '1000000'],
                'output_tokens' => ['amount' => '11', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.11', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '2.75', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.01', 'per' => '1'],
            ],
        ],
        'openrouter:openai/gpt-6.1-sol-pro-standard-max-long' => [
            'source' => 'https://openrouter.ai/api/v1/models/openai/gpt-6.1-sol-pro-20260929/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for openai/gpt-6.1-sol-pro (canonical openai/gpt-6.1-sol-pro-20260929) under OpenRouter default routing at the standard service tier, prompts of at least 272,000 tokens (the catalog override threshold is inclusive at 272,000, unlike the direct API, and applies to the whole request); not an alias. Default routing spans azure, openai at USD 4/M input and 15/M output; azure/eu, azure/us at USD 4.4/M input and 16.5/M output; every request is priced at the most expensive default-routed endpoint, so a request served at the base rate is overstated. Opt-in tiers (openai/flex, openai/fast, openai/ultrafast) are excluded; callers must not select them. Reasoning is output usage; no internal_reasoning rate is published. Cache writes bill at 1.25x the applicable input rate, matching OpenAI and https://openrouter.ai/docs/guides/best-practices/prompt-caching; input partitions are exclusive. web_searches counts native OpenAI web searches only, passed through at USD 0.01 per search per https://openrouter.ai/docs/guides/features/server-tools/web-search; Exa and other engines are distinct, unpriced units. The generic openrouter:openai/gpt-6.1-sol-pro identity is fallback-blocked. Snapshot invalidation: a change to the canonical revision, the default-routed endpoint set, token or cache rates, override threshold or search pricing requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '4.4', 'per' => '1000000'],
                'output_tokens' => ['amount' => '16.5', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.22', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '5.5', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.01', 'per' => '1'],
            ],
        ],
        'openrouter:openai/gpt-6-astra-standard-max-short' => [
            'source' => 'https://openrouter.ai/api/v1/models/openai/gpt-6-astra-20260903/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for openai/gpt-6-astra (canonical openai/gpt-6-astra-20260903) under OpenRouter default routing at the standard service tier, prompts below 272,000 tokens; not an alias. Default routing spans azure, openai at USD 10/M input and 50/M output; amazon-bedrock/us-west-2, azure/us at USD 11/M input and 55/M output; every request is priced at the most expensive default-routed endpoint, so a request served at the base rate is overstated. Opt-in tiers (openai/flex, openai/fast, openai/ultrafast) are excluded; callers must not select them. Reasoning is output usage; no internal_reasoning rate is published. Cache writes bill at 1.25x the applicable input rate, matching OpenAI and https://openrouter.ai/docs/guides/best-practices/prompt-caching; input partitions are exclusive. web_searches counts native OpenAI web searches only, passed through at USD 0.01 per search per https://openrouter.ai/docs/guides/features/server-tools/web-search; Exa and other engines are distinct, unpriced units. The generic openrouter:openai/gpt-6-astra identity is fallback-blocked. Snapshot invalidation: a change to the canonical revision, the default-routed endpoint set, token or cache rates, override threshold or search pricing requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '11', 'per' => '1000000'],
                'output_tokens' => ['amount' => '55', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '1.1', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '13.75', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.01', 'per' => '1'],
            ],
        ],
        'openrouter:openai/gpt-6-astra-standard-max-long' => [
            'source' => 'https://openrouter.ai/api/v1/models/openai/gpt-6-astra-20260903/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for openai/gpt-6-astra (canonical openai/gpt-6-astra-20260903) under OpenRouter default routing at the standard service tier, prompts of at least 272,000 tokens (the catalog override threshold is inclusive at 272,000, unlike the direct API, and applies to the whole request); not an alias. Default routing spans azure, openai at USD 20/M input and 75/M output; amazon-bedrock/us-west-2, azure/us at USD 22/M input and 82.5/M output; every request is priced at the most expensive default-routed endpoint, so a request served at the base rate is overstated. Opt-in tiers (openai/flex, openai/fast, openai/ultrafast) are excluded; callers must not select them. Reasoning is output usage; no internal_reasoning rate is published. Cache writes bill at 1.25x the applicable input rate, matching OpenAI and https://openrouter.ai/docs/guides/best-practices/prompt-caching; input partitions are exclusive. web_searches counts native OpenAI web searches only, passed through at USD 0.01 per search per https://openrouter.ai/docs/guides/features/server-tools/web-search; Exa and other engines are distinct, unpriced units. The generic openrouter:openai/gpt-6-astra identity is fallback-blocked. Snapshot invalidation: a change to the canonical revision, the default-routed endpoint set, token or cache rates, override threshold or search pricing requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '22', 'per' => '1000000'],
                'output_tokens' => ['amount' => '82.5', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '2.2', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '27.5', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.01', 'per' => '1'],
            ],
        ],
        'openrouter:openai/gpt-6-astra-pro-standard-max-short' => [
            'source' => 'https://openrouter.ai/api/v1/models/openai/gpt-6-astra-pro-20260903/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for openai/gpt-6-astra-pro (canonical openai/gpt-6-astra-pro-20260903) under OpenRouter default routing at the standard service tier, prompts below 272,000 tokens; not an alias. Default routing spans azure, openai at USD 10/M input and 50/M output; azure/us at USD 11/M input and 55/M output; every request is priced at the most expensive default-routed endpoint, so a request served at the base rate is overstated. Opt-in tiers (openai/flex, openai/fast, openai/ultrafast) are excluded; callers must not select them. Reasoning is output usage; no internal_reasoning rate is published. Cache writes bill at 1.25x the applicable input rate, matching OpenAI and https://openrouter.ai/docs/guides/best-practices/prompt-caching; input partitions are exclusive. web_searches counts native OpenAI web searches only, passed through at USD 0.01 per search per https://openrouter.ai/docs/guides/features/server-tools/web-search; Exa and other engines are distinct, unpriced units. The generic openrouter:openai/gpt-6-astra-pro identity is fallback-blocked. Snapshot invalidation: a change to the canonical revision, the default-routed endpoint set, token or cache rates, override threshold or search pricing requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '11', 'per' => '1000000'],
                'output_tokens' => ['amount' => '55', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '1.1', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '13.75', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.01', 'per' => '1'],
            ],
        ],
        'openrouter:openai/gpt-6-astra-pro-standard-max-long' => [
            'source' => 'https://openrouter.ai/api/v1/models/openai/gpt-6-astra-pro-20260903/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for openai/gpt-6-astra-pro (canonical openai/gpt-6-astra-pro-20260903) under OpenRouter default routing at the standard service tier, prompts of at least 272,000 tokens (the catalog override threshold is inclusive at 272,000, unlike the direct API, and applies to the whole request); not an alias. Default routing spans azure, openai at USD 20/M input and 75/M output; azure/us at USD 22/M input and 82.5/M output; every request is priced at the most expensive default-routed endpoint, so a request served at the base rate is overstated. Opt-in tiers (openai/flex, openai/fast, openai/ultrafast) are excluded; callers must not select them. Reasoning is output usage; no internal_reasoning rate is published. Cache writes bill at 1.25x the applicable input rate, matching OpenAI and https://openrouter.ai/docs/guides/best-practices/prompt-caching; input partitions are exclusive. web_searches counts native OpenAI web searches only, passed through at USD 0.01 per search per https://openrouter.ai/docs/guides/features/server-tools/web-search; Exa and other engines are distinct, unpriced units. The generic openrouter:openai/gpt-6-astra-pro identity is fallback-blocked. Snapshot invalidation: a change to the canonical revision, the default-routed endpoint set, token or cache rates, override threshold or search pricing requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '22', 'per' => '1000000'],
                'output_tokens' => ['amount' => '82.5', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '2.2', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '27.5', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.01', 'per' => '1'],
            ],
        ],
        'openrouter:openai/gpt-6-sol-standard-max-short' => [
            'source' => 'https://openrouter.ai/api/v1/models/openai/gpt-6-sol-20260922/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for openai/gpt-6-sol (canonical openai/gpt-6-sol-20260922) under OpenRouter default routing at the standard service tier, prompts below 272,000 tokens; not an alias. Default routing spans openai, azure at USD 2/M input and 10/M output; azure/us, azure/eu, amazon-bedrock/us-east-1 at USD 2.2/M input and 11/M output; every request is priced at the most expensive default-routed endpoint, so a request served at the base rate is overstated. Opt-in tiers (openai/flex, openai/fast) are excluded; callers must not select them. Reasoning is output usage; no internal_reasoning rate is published. Cache writes bill at 1.25x the applicable input rate, matching OpenAI and https://openrouter.ai/docs/guides/best-practices/prompt-caching; input partitions are exclusive. web_searches counts native OpenAI web searches only, passed through at USD 0.01 per search per https://openrouter.ai/docs/guides/features/server-tools/web-search; Exa and other engines are distinct, unpriced units. The generic openrouter:openai/gpt-6-sol identity is fallback-blocked. Snapshot invalidation: a change to the canonical revision, the default-routed endpoint set, token or cache rates, override threshold or search pricing requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '2.2', 'per' => '1000000'],
                'output_tokens' => ['amount' => '11', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.22', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '2.75', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.01', 'per' => '1'],
            ],
        ],
        'openrouter:openai/gpt-6-sol-standard-max-long' => [
            'source' => 'https://openrouter.ai/api/v1/models/openai/gpt-6-sol-20260922/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for openai/gpt-6-sol (canonical openai/gpt-6-sol-20260922) under OpenRouter default routing at the standard service tier, prompts of at least 272,000 tokens (the catalog override threshold is inclusive at 272,000, unlike the direct API, and applies to the whole request); not an alias. Default routing spans openai, azure at USD 4/M input and 15/M output; azure/us, azure/eu, amazon-bedrock/us-east-1 at USD 4.4/M input and 16.5/M output; every request is priced at the most expensive default-routed endpoint, so a request served at the base rate is overstated. Opt-in tiers (openai/flex, openai/fast) are excluded; callers must not select them. Reasoning is output usage; no internal_reasoning rate is published. Cache writes bill at 1.25x the applicable input rate, matching OpenAI and https://openrouter.ai/docs/guides/best-practices/prompt-caching; input partitions are exclusive. web_searches counts native OpenAI web searches only, passed through at USD 0.01 per search per https://openrouter.ai/docs/guides/features/server-tools/web-search; Exa and other engines are distinct, unpriced units. The generic openrouter:openai/gpt-6-sol identity is fallback-blocked. Snapshot invalidation: a change to the canonical revision, the default-routed endpoint set, token or cache rates, override threshold or search pricing requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '4.4', 'per' => '1000000'],
                'output_tokens' => ['amount' => '16.5', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.44', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '5.5', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.01', 'per' => '1'],
            ],
        ],
        'openrouter:openai/gpt-6-sol-pro-standard-max-short' => [
            'source' => 'https://openrouter.ai/api/v1/models/openai/gpt-6-sol-pro-20260922/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for openai/gpt-6-sol-pro (canonical openai/gpt-6-sol-pro-20260922) under OpenRouter default routing at the standard service tier, prompts below 272,000 tokens; not an alias. Default routing spans openai, azure at USD 2/M input and 10/M output; azure/us, azure/eu at USD 2.2/M input and 11/M output; every request is priced at the most expensive default-routed endpoint, so a request served at the base rate is overstated. Opt-in tiers (openai/flex, openai/fast) are excluded; callers must not select them. Reasoning is output usage; no internal_reasoning rate is published. Cache writes bill at 1.25x the applicable input rate, matching OpenAI and https://openrouter.ai/docs/guides/best-practices/prompt-caching; input partitions are exclusive. web_searches counts native OpenAI web searches only, passed through at USD 0.01 per search per https://openrouter.ai/docs/guides/features/server-tools/web-search; Exa and other engines are distinct, unpriced units. The generic openrouter:openai/gpt-6-sol-pro identity is fallback-blocked. Snapshot invalidation: a change to the canonical revision, the default-routed endpoint set, token or cache rates, override threshold or search pricing requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '2.2', 'per' => '1000000'],
                'output_tokens' => ['amount' => '11', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.22', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '2.75', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.01', 'per' => '1'],
            ],
        ],
        'openrouter:openai/gpt-6-sol-pro-standard-max-long' => [
            'source' => 'https://openrouter.ai/api/v1/models/openai/gpt-6-sol-pro-20260922/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for openai/gpt-6-sol-pro (canonical openai/gpt-6-sol-pro-20260922) under OpenRouter default routing at the standard service tier, prompts of at least 272,000 tokens (the catalog override threshold is inclusive at 272,000, unlike the direct API, and applies to the whole request); not an alias. Default routing spans openai, azure at USD 4/M input and 15/M output; azure/us, azure/eu at USD 4.4/M input and 16.5/M output; every request is priced at the most expensive default-routed endpoint, so a request served at the base rate is overstated. Opt-in tiers (openai/flex, openai/fast) are excluded; callers must not select them. Reasoning is output usage; no internal_reasoning rate is published. Cache writes bill at 1.25x the applicable input rate, matching OpenAI and https://openrouter.ai/docs/guides/best-practices/prompt-caching; input partitions are exclusive. web_searches counts native OpenAI web searches only, passed through at USD 0.01 per search per https://openrouter.ai/docs/guides/features/server-tools/web-search; Exa and other engines are distinct, unpriced units. The generic openrouter:openai/gpt-6-sol-pro identity is fallback-blocked. Snapshot invalidation: a change to the canonical revision, the default-routed endpoint set, token or cache rates, override threshold or search pricing requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '4.4', 'per' => '1000000'],
                'output_tokens' => ['amount' => '16.5', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.44', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '5.5', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.01', 'per' => '1'],
            ],
        ],
        'openrouter:openai/gpt-6-luna-standard-max-short' => [
            'source' => 'https://openrouter.ai/api/v1/models/openai/gpt-6-luna-20260922/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for openai/gpt-6-luna (canonical openai/gpt-6-luna-20260922) under OpenRouter default routing at the standard service tier, prompts below 272,000 tokens; not an alias. Default routing spans openai, azure at USD 0.1/M input and 0.5/M output; azure/us, azure/eu, amazon-bedrock/us-east-1 at USD 0.11/M input and 0.55/M output; every request is priced at the most expensive default-routed endpoint, so a request served at the base rate is overstated. Opt-in tiers (openai/flex, openai/fast) are excluded; callers must not select them. Reasoning is output usage; no internal_reasoning rate is published. Cache writes bill at 1.25x the applicable input rate, matching OpenAI and https://openrouter.ai/docs/guides/best-practices/prompt-caching; input partitions are exclusive. web_searches counts native OpenAI web searches only, passed through at USD 0.01 per search per https://openrouter.ai/docs/guides/features/server-tools/web-search; Exa and other engines are distinct, unpriced units. The generic openrouter:openai/gpt-6-luna identity is fallback-blocked. Snapshot invalidation: a change to the canonical revision, the default-routed endpoint set, token or cache rates, override threshold or search pricing requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '0.11', 'per' => '1000000'],
                'output_tokens' => ['amount' => '0.55', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.011', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '0.1375', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.01', 'per' => '1'],
            ],
        ],
        'openrouter:openai/gpt-6-luna-standard-max-long' => [
            'source' => 'https://openrouter.ai/api/v1/models/openai/gpt-6-luna-20260922/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for openai/gpt-6-luna (canonical openai/gpt-6-luna-20260922) under OpenRouter default routing at the standard service tier, prompts of at least 272,000 tokens (the catalog override threshold is inclusive at 272,000, unlike the direct API, and applies to the whole request); not an alias. Default routing spans openai, azure at USD 0.2/M input and 0.75/M output; azure/us, azure/eu, amazon-bedrock/us-east-1 at USD 0.22/M input and 0.825/M output; every request is priced at the most expensive default-routed endpoint, so a request served at the base rate is overstated. Opt-in tiers (openai/flex, openai/fast) are excluded; callers must not select them. Reasoning is output usage; no internal_reasoning rate is published. Cache writes bill at 1.25x the applicable input rate, matching OpenAI and https://openrouter.ai/docs/guides/best-practices/prompt-caching; input partitions are exclusive. web_searches counts native OpenAI web searches only, passed through at USD 0.01 per search per https://openrouter.ai/docs/guides/features/server-tools/web-search; Exa and other engines are distinct, unpriced units. The generic openrouter:openai/gpt-6-luna identity is fallback-blocked. Snapshot invalidation: a change to the canonical revision, the default-routed endpoint set, token or cache rates, override threshold or search pricing requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '0.22', 'per' => '1000000'],
                'output_tokens' => ['amount' => '0.825', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.022', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '0.275', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.01', 'per' => '1'],
            ],
        ],
        'openrouter:openai/gpt-6-luna-pro-standard-max-short' => [
            'source' => 'https://openrouter.ai/api/v1/models/openai/gpt-6-luna-pro-20260922/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for openai/gpt-6-luna-pro (canonical openai/gpt-6-luna-pro-20260922) under OpenRouter default routing at the standard service tier, prompts below 272,000 tokens; not an alias. Default routing spans openai, azure at USD 0.1/M input and 0.5/M output; azure/us, azure/eu at USD 0.11/M input and 0.55/M output; every request is priced at the most expensive default-routed endpoint, so a request served at the base rate is overstated. Opt-in tiers (openai/flex, openai/fast) are excluded; callers must not select them. Reasoning is output usage; no internal_reasoning rate is published. Cache writes bill at 1.25x the applicable input rate, matching OpenAI and https://openrouter.ai/docs/guides/best-practices/prompt-caching; input partitions are exclusive. web_searches counts native OpenAI web searches only, passed through at USD 0.01 per search per https://openrouter.ai/docs/guides/features/server-tools/web-search; Exa and other engines are distinct, unpriced units. The generic openrouter:openai/gpt-6-luna-pro identity is fallback-blocked. Snapshot invalidation: a change to the canonical revision, the default-routed endpoint set, token or cache rates, override threshold or search pricing requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '0.11', 'per' => '1000000'],
                'output_tokens' => ['amount' => '0.55', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.011', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '0.1375', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.01', 'per' => '1'],
            ],
        ],
        'openrouter:openai/gpt-6-luna-pro-standard-max-long' => [
            'source' => 'https://openrouter.ai/api/v1/models/openai/gpt-6-luna-pro-20260922/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for openai/gpt-6-luna-pro (canonical openai/gpt-6-luna-pro-20260922) under OpenRouter default routing at the standard service tier, prompts of at least 272,000 tokens (the catalog override threshold is inclusive at 272,000, unlike the direct API, and applies to the whole request); not an alias. Default routing spans openai, azure at USD 0.2/M input and 0.75/M output; azure/us, azure/eu at USD 0.22/M input and 0.825/M output; every request is priced at the most expensive default-routed endpoint, so a request served at the base rate is overstated. Opt-in tiers (openai/flex, openai/fast) are excluded; callers must not select them. Reasoning is output usage; no internal_reasoning rate is published. Cache writes bill at 1.25x the applicable input rate, matching OpenAI and https://openrouter.ai/docs/guides/best-practices/prompt-caching; input partitions are exclusive. web_searches counts native OpenAI web searches only, passed through at USD 0.01 per search per https://openrouter.ai/docs/guides/features/server-tools/web-search; Exa and other engines are distinct, unpriced units. The generic openrouter:openai/gpt-6-luna-pro identity is fallback-blocked. Snapshot invalidation: a change to the canonical revision, the default-routed endpoint set, token or cache rates, override threshold or search pricing requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '0.22', 'per' => '1000000'],
                'output_tokens' => ['amount' => '0.825', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.022', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '0.275', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.01', 'per' => '1'],
            ],
        ],
        'openrouter:openai/gpt-5.6-sol-standard-max-short' => [
            'source' => 'https://openrouter.ai/api/v1/models/openai/gpt-5.6-sol-20260709/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for openai/gpt-5.6-sol (canonical openai/gpt-5.6-sol-20260709) under OpenRouter default routing at the standard service tier, prompts below 272,000 tokens; not an alias. Default routing spans openai (catalog discount 0.5) at USD 2/M input and 10/M output; azure at USD 4/M input and 20/M output; azure/us, amazon-bedrock/us-east-1, azure/eu at USD 4.4/M input and 22/M output; every request is priced at the most expensive default-routed endpoint, so a request served at the base rate is overstated. Opt-in tiers (openai/flex, openai/fast) are excluded; callers must not select them. Reasoning is output usage; no internal_reasoning rate is published. The openai endpoint carries a 50% OpenRouter discount (USD 2/M input, 10/M output) below OpenAI\'s direct USD 4/M and 20/M promotional rates, which the azure endpoint charges; the live model-level catalog shows only the discounted rate. Cache writes bill at 1.25x the applicable input rate, matching OpenAI and https://openrouter.ai/docs/guides/best-practices/prompt-caching; input partitions are exclusive. web_searches counts native OpenAI web searches only, passed through at USD 0.01 per search per https://openrouter.ai/docs/guides/features/server-tools/web-search; Exa and other engines are distinct, unpriced units. The generic openrouter:openai/gpt-5.6-sol identity is fallback-blocked. Snapshot invalidation: a change to the canonical revision, the default-routed endpoint set, token or cache rates, override threshold or search pricing requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '4.4', 'per' => '1000000'],
                'output_tokens' => ['amount' => '22', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.44', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '5.5', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.01', 'per' => '1'],
            ],
        ],
        'openrouter:openai/gpt-5.6-sol-standard-max-long' => [
            'source' => 'https://openrouter.ai/api/v1/models/openai/gpt-5.6-sol-20260709/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for openai/gpt-5.6-sol (canonical openai/gpt-5.6-sol-20260709) under OpenRouter default routing at the standard service tier, prompts of at least 272,000 tokens (the catalog override threshold is inclusive at 272,000, unlike the direct API, and applies to the whole request); not an alias. Default routing spans openai (catalog discount 0.5) at USD 4/M input and 15/M output; azure at USD 8/M input and 30/M output; azure/us, amazon-bedrock/us-east-1, azure/eu at USD 8.8/M input and 33/M output; every request is priced at the most expensive default-routed endpoint, so a request served at the base rate is overstated. Opt-in tiers (openai/flex, openai/fast) are excluded; callers must not select them. Reasoning is output usage; no internal_reasoning rate is published. The openai endpoint carries a 50% OpenRouter discount (USD 2/M input, 10/M output) below OpenAI\'s direct USD 4/M and 20/M promotional rates, which the azure endpoint charges; the live model-level catalog shows only the discounted rate. Cache writes bill at 1.25x the applicable input rate, matching OpenAI and https://openrouter.ai/docs/guides/best-practices/prompt-caching; input partitions are exclusive. web_searches counts native OpenAI web searches only, passed through at USD 0.01 per search per https://openrouter.ai/docs/guides/features/server-tools/web-search; Exa and other engines are distinct, unpriced units. The generic openrouter:openai/gpt-5.6-sol identity is fallback-blocked. Snapshot invalidation: a change to the canonical revision, the default-routed endpoint set, token or cache rates, override threshold or search pricing requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '8.8', 'per' => '1000000'],
                'output_tokens' => ['amount' => '33', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.88', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '11', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.01', 'per' => '1'],
            ],
        ],
        'openrouter:openai/gpt-5.6-sol-pro-standard-max-short' => [
            'source' => 'https://openrouter.ai/api/v1/models/openai/gpt-5.6-sol-pro-20260709/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for openai/gpt-5.6-sol-pro (canonical openai/gpt-5.6-sol-pro-20260709) under OpenRouter default routing at the standard service tier, prompts below 272,000 tokens; not an alias. Default routing spans openai (catalog discount 0.5) at USD 2/M input and 10/M output; azure at USD 4/M input and 20/M output; azure/eu at USD 4.4/M input and 22/M output; every request is priced at the most expensive default-routed endpoint, so a request served at the base rate is overstated. Opt-in tiers (openai/flex, openai/fast) are excluded; callers must not select them. Reasoning is output usage; no internal_reasoning rate is published. The openai endpoint carries a 50% OpenRouter discount (USD 2/M input, 10/M output) below OpenAI\'s direct USD 4/M and 20/M promotional rates, which the azure endpoint charges; the live model-level catalog shows only the discounted rate. Cache writes bill at 1.25x the applicable input rate, matching OpenAI and https://openrouter.ai/docs/guides/best-practices/prompt-caching; input partitions are exclusive. web_searches counts native OpenAI web searches only, passed through at USD 0.01 per search per https://openrouter.ai/docs/guides/features/server-tools/web-search; Exa and other engines are distinct, unpriced units. The generic openrouter:openai/gpt-5.6-sol-pro identity is fallback-blocked. Snapshot invalidation: a change to the canonical revision, the default-routed endpoint set, token or cache rates, override threshold or search pricing requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '4.4', 'per' => '1000000'],
                'output_tokens' => ['amount' => '22', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.44', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '5.5', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.01', 'per' => '1'],
            ],
        ],
        'openrouter:openai/gpt-5.6-sol-pro-standard-max-long' => [
            'source' => 'https://openrouter.ai/api/v1/models/openai/gpt-5.6-sol-pro-20260709/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for openai/gpt-5.6-sol-pro (canonical openai/gpt-5.6-sol-pro-20260709) under OpenRouter default routing at the standard service tier, prompts of at least 272,000 tokens (the catalog override threshold is inclusive at 272,000, unlike the direct API, and applies to the whole request); not an alias. Default routing spans openai (catalog discount 0.5) at USD 4/M input and 15/M output; azure at USD 8/M input and 30/M output; azure/eu at USD 8.8/M input and 33/M output; every request is priced at the most expensive default-routed endpoint, so a request served at the base rate is overstated. Opt-in tiers (openai/flex, openai/fast) are excluded; callers must not select them. Reasoning is output usage; no internal_reasoning rate is published. The openai endpoint carries a 50% OpenRouter discount (USD 2/M input, 10/M output) below OpenAI\'s direct USD 4/M and 20/M promotional rates, which the azure endpoint charges; the live model-level catalog shows only the discounted rate. Cache writes bill at 1.25x the applicable input rate, matching OpenAI and https://openrouter.ai/docs/guides/best-practices/prompt-caching; input partitions are exclusive. web_searches counts native OpenAI web searches only, passed through at USD 0.01 per search per https://openrouter.ai/docs/guides/features/server-tools/web-search; Exa and other engines are distinct, unpriced units. The generic openrouter:openai/gpt-5.6-sol-pro identity is fallback-blocked. Snapshot invalidation: a change to the canonical revision, the default-routed endpoint set, token or cache rates, override threshold or search pricing requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '8.8', 'per' => '1000000'],
                'output_tokens' => ['amount' => '33', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.88', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '11', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.01', 'per' => '1'],
            ],
        ],
        'openrouter:openai/gpt-5.6-terra-standard-max-short' => [
            'source' => 'https://openrouter.ai/api/v1/models/openai/gpt-5.6-terra-20260709/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for openai/gpt-5.6-terra (canonical openai/gpt-5.6-terra-20260709) under OpenRouter default routing at the standard service tier, prompts below 272,000 tokens; not an alias. Default routing spans azure, openai at USD 2/M input and 12/M output; azure/us, amazon-bedrock/us-east-1, azure/eu at USD 2.2/M input and 13.2/M output; every request is priced at the most expensive default-routed endpoint, so a request served at the base rate is overstated. Opt-in tiers (openai/flex, openai/fast) are excluded; callers must not select them. Reasoning is output usage; no internal_reasoning rate is published. Cache writes bill at 1.25x the applicable input rate, matching OpenAI and https://openrouter.ai/docs/guides/best-practices/prompt-caching; input partitions are exclusive. web_searches counts native OpenAI web searches only, passed through at USD 0.01 per search per https://openrouter.ai/docs/guides/features/server-tools/web-search; Exa and other engines are distinct, unpriced units. The generic openrouter:openai/gpt-5.6-terra identity is fallback-blocked. Snapshot invalidation: a change to the canonical revision, the default-routed endpoint set, token or cache rates, override threshold or search pricing requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '2.2', 'per' => '1000000'],
                'output_tokens' => ['amount' => '13.2', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.22', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '2.75', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.01', 'per' => '1'],
            ],
        ],
        'openrouter:openai/gpt-5.6-terra-standard-max-long' => [
            'source' => 'https://openrouter.ai/api/v1/models/openai/gpt-5.6-terra-20260709/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for openai/gpt-5.6-terra (canonical openai/gpt-5.6-terra-20260709) under OpenRouter default routing at the standard service tier, prompts of at least 272,000 tokens (the catalog override threshold is inclusive at 272,000, unlike the direct API, and applies to the whole request); not an alias. Default routing spans azure, openai at USD 4/M input and 18/M output; azure/us, amazon-bedrock/us-east-1, azure/eu at USD 4.4/M input and 19.8/M output; every request is priced at the most expensive default-routed endpoint, so a request served at the base rate is overstated. Opt-in tiers (openai/flex, openai/fast) are excluded; callers must not select them. Reasoning is output usage; no internal_reasoning rate is published. Cache writes bill at 1.25x the applicable input rate, matching OpenAI and https://openrouter.ai/docs/guides/best-practices/prompt-caching; input partitions are exclusive. web_searches counts native OpenAI web searches only, passed through at USD 0.01 per search per https://openrouter.ai/docs/guides/features/server-tools/web-search; Exa and other engines are distinct, unpriced units. The generic openrouter:openai/gpt-5.6-terra identity is fallback-blocked. Snapshot invalidation: a change to the canonical revision, the default-routed endpoint set, token or cache rates, override threshold or search pricing requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '4.4', 'per' => '1000000'],
                'output_tokens' => ['amount' => '19.8', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.44', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '5.5', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.01', 'per' => '1'],
            ],
        ],
        'openrouter:openai/gpt-5.6-terra-pro-standard-max-short' => [
            'source' => 'https://openrouter.ai/api/v1/models/openai/gpt-5.6-terra-pro-20260709/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for openai/gpt-5.6-terra-pro (canonical openai/gpt-5.6-terra-pro-20260709) under OpenRouter default routing at the standard service tier, prompts below 272,000 tokens; not an alias. Default routing spans azure, openai at USD 2/M input and 12/M output; azure/eu at USD 2.2/M input and 13.2/M output; every request is priced at the most expensive default-routed endpoint, so a request served at the base rate is overstated. Opt-in tiers (openai/flex, openai/fast) are excluded; callers must not select them. Reasoning is output usage; no internal_reasoning rate is published. Cache writes bill at 1.25x the applicable input rate, matching OpenAI and https://openrouter.ai/docs/guides/best-practices/prompt-caching; input partitions are exclusive. web_searches counts native OpenAI web searches only, passed through at USD 0.01 per search per https://openrouter.ai/docs/guides/features/server-tools/web-search; Exa and other engines are distinct, unpriced units. The generic openrouter:openai/gpt-5.6-terra-pro identity is fallback-blocked. Snapshot invalidation: a change to the canonical revision, the default-routed endpoint set, token or cache rates, override threshold or search pricing requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '2.2', 'per' => '1000000'],
                'output_tokens' => ['amount' => '13.2', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.22', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '2.75', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.01', 'per' => '1'],
            ],
        ],
        'openrouter:openai/gpt-5.6-terra-pro-standard-max-long' => [
            'source' => 'https://openrouter.ai/api/v1/models/openai/gpt-5.6-terra-pro-20260709/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for openai/gpt-5.6-terra-pro (canonical openai/gpt-5.6-terra-pro-20260709) under OpenRouter default routing at the standard service tier, prompts of at least 272,000 tokens (the catalog override threshold is inclusive at 272,000, unlike the direct API, and applies to the whole request); not an alias. Default routing spans azure, openai at USD 4/M input and 18/M output; azure/eu at USD 4.4/M input and 19.8/M output; every request is priced at the most expensive default-routed endpoint, so a request served at the base rate is overstated. Opt-in tiers (openai/flex, openai/fast) are excluded; callers must not select them. Reasoning is output usage; no internal_reasoning rate is published. Cache writes bill at 1.25x the applicable input rate, matching OpenAI and https://openrouter.ai/docs/guides/best-practices/prompt-caching; input partitions are exclusive. web_searches counts native OpenAI web searches only, passed through at USD 0.01 per search per https://openrouter.ai/docs/guides/features/server-tools/web-search; Exa and other engines are distinct, unpriced units. The generic openrouter:openai/gpt-5.6-terra-pro identity is fallback-blocked. Snapshot invalidation: a change to the canonical revision, the default-routed endpoint set, token or cache rates, override threshold or search pricing requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '4.4', 'per' => '1000000'],
                'output_tokens' => ['amount' => '19.8', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.44', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '5.5', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.01', 'per' => '1'],
            ],
        ],
        'openrouter:openai/gpt-5.6-luna-standard-max-short' => [
            'source' => 'https://openrouter.ai/api/v1/models/openai/gpt-5.6-luna-20260709/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for openai/gpt-5.6-luna (canonical openai/gpt-5.6-luna-20260709) under OpenRouter default routing at the standard service tier, prompts below 272,000 tokens; not an alias. Default routing spans azure, openai at USD 0.2/M input and 1.2/M output; azure/us, amazon-bedrock/us-east-1, azure/eu at USD 0.22/M input and 1.32/M output; every request is priced at the most expensive default-routed endpoint, so a request served at the base rate is overstated. Opt-in tiers (openai/flex, openai/fast) are excluded; callers must not select them. Reasoning is output usage; no internal_reasoning rate is published. Cache writes bill at 1.25x the applicable input rate, matching OpenAI and https://openrouter.ai/docs/guides/best-practices/prompt-caching; input partitions are exclusive. web_searches counts native OpenAI web searches only, passed through at USD 0.01 per search per https://openrouter.ai/docs/guides/features/server-tools/web-search; Exa and other engines are distinct, unpriced units. The generic openrouter:openai/gpt-5.6-luna identity is fallback-blocked. Snapshot invalidation: a change to the canonical revision, the default-routed endpoint set, token or cache rates, override threshold or search pricing requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '0.22', 'per' => '1000000'],
                'output_tokens' => ['amount' => '1.32', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.022', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '0.275', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.01', 'per' => '1'],
            ],
        ],
        'openrouter:openai/gpt-5.6-luna-standard-max-long' => [
            'source' => 'https://openrouter.ai/api/v1/models/openai/gpt-5.6-luna-20260709/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for openai/gpt-5.6-luna (canonical openai/gpt-5.6-luna-20260709) under OpenRouter default routing at the standard service tier, prompts of at least 272,000 tokens (the catalog override threshold is inclusive at 272,000, unlike the direct API, and applies to the whole request); not an alias. Default routing spans azure, openai at USD 0.4/M input and 1.8/M output; azure/us, amazon-bedrock/us-east-1, azure/eu at USD 0.44/M input and 1.98/M output; every request is priced at the most expensive default-routed endpoint, so a request served at the base rate is overstated. Opt-in tiers (openai/flex, openai/fast) are excluded; callers must not select them. Reasoning is output usage; no internal_reasoning rate is published. Cache writes bill at 1.25x the applicable input rate, matching OpenAI and https://openrouter.ai/docs/guides/best-practices/prompt-caching; input partitions are exclusive. web_searches counts native OpenAI web searches only, passed through at USD 0.01 per search per https://openrouter.ai/docs/guides/features/server-tools/web-search; Exa and other engines are distinct, unpriced units. The generic openrouter:openai/gpt-5.6-luna identity is fallback-blocked. Snapshot invalidation: a change to the canonical revision, the default-routed endpoint set, token or cache rates, override threshold or search pricing requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '0.44', 'per' => '1000000'],
                'output_tokens' => ['amount' => '1.98', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.044', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '0.55', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.01', 'per' => '1'],
            ],
        ],
        'openrouter:openai/gpt-5.6-luna-pro-standard-max-short' => [
            'source' => 'https://openrouter.ai/api/v1/models/openai/gpt-5.6-luna-pro-20260709/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for openai/gpt-5.6-luna-pro (canonical openai/gpt-5.6-luna-pro-20260709) under OpenRouter default routing at the standard service tier, prompts below 272,000 tokens; not an alias. Default routing spans azure, openai at USD 0.2/M input and 1.2/M output; azure/eu at USD 0.22/M input and 1.32/M output; every request is priced at the most expensive default-routed endpoint, so a request served at the base rate is overstated. Opt-in tiers (openai/flex, openai/fast) are excluded; callers must not select them. Reasoning is output usage; no internal_reasoning rate is published. Cache writes bill at 1.25x the applicable input rate, matching OpenAI and https://openrouter.ai/docs/guides/best-practices/prompt-caching; input partitions are exclusive. web_searches counts native OpenAI web searches only, passed through at USD 0.01 per search per https://openrouter.ai/docs/guides/features/server-tools/web-search; Exa and other engines are distinct, unpriced units. The generic openrouter:openai/gpt-5.6-luna-pro identity is fallback-blocked. Snapshot invalidation: a change to the canonical revision, the default-routed endpoint set, token or cache rates, override threshold or search pricing requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '0.22', 'per' => '1000000'],
                'output_tokens' => ['amount' => '1.32', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.022', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '0.275', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.01', 'per' => '1'],
            ],
        ],
        'openrouter:openai/gpt-5.6-luna-pro-standard-max-long' => [
            'source' => 'https://openrouter.ai/api/v1/models/openai/gpt-5.6-luna-pro-20260709/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for openai/gpt-5.6-luna-pro (canonical openai/gpt-5.6-luna-pro-20260709) under OpenRouter default routing at the standard service tier, prompts of at least 272,000 tokens (the catalog override threshold is inclusive at 272,000, unlike the direct API, and applies to the whole request); not an alias. Default routing spans azure, openai at USD 0.4/M input and 1.8/M output; azure/eu at USD 0.44/M input and 1.98/M output; every request is priced at the most expensive default-routed endpoint, so a request served at the base rate is overstated. Opt-in tiers (openai/flex, openai/fast) are excluded; callers must not select them. Reasoning is output usage; no internal_reasoning rate is published. Cache writes bill at 1.25x the applicable input rate, matching OpenAI and https://openrouter.ai/docs/guides/best-practices/prompt-caching; input partitions are exclusive. web_searches counts native OpenAI web searches only, passed through at USD 0.01 per search per https://openrouter.ai/docs/guides/features/server-tools/web-search; Exa and other engines are distinct, unpriced units. The generic openrouter:openai/gpt-5.6-luna-pro identity is fallback-blocked. Snapshot invalidation: a change to the canonical revision, the default-routed endpoint set, token or cache rates, override threshold or search pricing requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '0.44', 'per' => '1000000'],
                'output_tokens' => ['amount' => '1.98', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.044', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '0.55', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.01', 'per' => '1'],
            ],
        ],
        'openrouter:openai/gpt-5.5-standard-max-short' => [
            'source' => 'https://openrouter.ai/api/v1/models/openai/gpt-5.5-20260423/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for openai/gpt-5.5 (canonical openai/gpt-5.5-20260423) under OpenRouter default routing at the standard service tier, prompts below 272,000 tokens; not an alias. Default routing spans azure, openai at USD 5/M input and 30/M output; azure/us, azure/eu, amazon-bedrock/us-east-1 at USD 5.5/M input and 33/M output; every request is priced at the most expensive default-routed endpoint, so a request served at the base rate is overstated. Opt-in tiers (openai/flex, openai/fast) are excluded; callers must not select them. Reasoning is output usage; no internal_reasoning rate is published. Cache writes are deliberately unpriced: OpenAI bills pre-GPT-5.6 cache writes at the uncached input rate, while https://openrouter.ai/docs/guides/best-practices/prompt-caching says they have no cost, so a cache_write_input_tokens count stays missing. web_searches counts native OpenAI web searches only, passed through at USD 0.01 per search per https://openrouter.ai/docs/guides/features/server-tools/web-search; Exa and other engines are distinct, unpriced units. The generic openrouter:openai/gpt-5.5 identity is fallback-blocked. Snapshot invalidation: a change to the canonical revision, the default-routed endpoint set, token or cache rates, override threshold or search pricing requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '5.5', 'per' => '1000000'],
                'output_tokens' => ['amount' => '33', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.55', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.01', 'per' => '1'],
            ],
        ],
        'openrouter:openai/gpt-5.5-standard-max-long' => [
            'source' => 'https://openrouter.ai/api/v1/models/openai/gpt-5.5-20260423/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for openai/gpt-5.5 (canonical openai/gpt-5.5-20260423) under OpenRouter default routing at the standard service tier, prompts of at least 272,000 tokens (the catalog override threshold is inclusive at 272,000, unlike the direct API, and applies to the whole request); not an alias. Default routing spans azure, openai at USD 10/M input and 45/M output; azure/us, azure/eu, amazon-bedrock/us-east-1 at USD 11/M input and 49.5/M output; every request is priced at the most expensive default-routed endpoint, so a request served at the base rate is overstated. Opt-in tiers (openai/flex, openai/fast) are excluded; callers must not select them. Reasoning is output usage; no internal_reasoning rate is published. Cache writes are deliberately unpriced: OpenAI bills pre-GPT-5.6 cache writes at the uncached input rate, while https://openrouter.ai/docs/guides/best-practices/prompt-caching says they have no cost, so a cache_write_input_tokens count stays missing. web_searches counts native OpenAI web searches only, passed through at USD 0.01 per search per https://openrouter.ai/docs/guides/features/server-tools/web-search; Exa and other engines are distinct, unpriced units. The generic openrouter:openai/gpt-5.5 identity is fallback-blocked. Snapshot invalidation: a change to the canonical revision, the default-routed endpoint set, token or cache rates, override threshold or search pricing requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '11', 'per' => '1000000'],
                'output_tokens' => ['amount' => '49.5', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '1.1', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.01', 'per' => '1'],
            ],
        ],
        'openrouter:openai/gpt-5.4-standard-max-short' => [
            'source' => 'https://openrouter.ai/api/v1/models/openai/gpt-5.4-20260305/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for openai/gpt-5.4 (canonical openai/gpt-5.4-20260305) under OpenRouter default routing at the standard service tier, prompts below 272,000 tokens; not an alias. Default routing spans azure, openai at USD 2.5/M input and 15/M output; azure/us, azure/eu, amazon-bedrock/us-east-1 at USD 2.75/M input and 16.5/M output; every request is priced at the most expensive default-routed endpoint, so a request served at the base rate is overstated. Opt-in tiers (openai/flex, openai/fast) are excluded; callers must not select them. Reasoning is output usage; no internal_reasoning rate is published. Cache writes are deliberately unpriced: OpenAI bills pre-GPT-5.6 cache writes at the uncached input rate, while https://openrouter.ai/docs/guides/best-practices/prompt-caching says they have no cost, so a cache_write_input_tokens count stays missing. web_searches counts native OpenAI web searches only, passed through at USD 0.01 per search per https://openrouter.ai/docs/guides/features/server-tools/web-search; Exa and other engines are distinct, unpriced units. The generic openrouter:openai/gpt-5.4 identity is fallback-blocked. Snapshot invalidation: a change to the canonical revision, the default-routed endpoint set, token or cache rates, override threshold or search pricing requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '2.75', 'per' => '1000000'],
                'output_tokens' => ['amount' => '16.5', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.275', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.01', 'per' => '1'],
            ],
        ],
        'openrouter:openai/gpt-5.4-standard-max-long' => [
            'source' => 'https://openrouter.ai/api/v1/models/openai/gpt-5.4-20260305/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for openai/gpt-5.4 (canonical openai/gpt-5.4-20260305) under OpenRouter default routing at the standard service tier, prompts of at least 272,000 tokens (the catalog override threshold is inclusive at 272,000, unlike the direct API, and applies to the whole request); not an alias. Default routing spans azure, openai at USD 5/M input and 22.5/M output; azure/us, azure/eu, amazon-bedrock/us-east-1 at USD 5.5/M input and 24.75/M output; every request is priced at the most expensive default-routed endpoint, so a request served at the base rate is overstated. Opt-in tiers (openai/flex, openai/fast) are excluded; callers must not select them. Reasoning is output usage; no internal_reasoning rate is published. Cache writes are deliberately unpriced: OpenAI bills pre-GPT-5.6 cache writes at the uncached input rate, while https://openrouter.ai/docs/guides/best-practices/prompt-caching says they have no cost, so a cache_write_input_tokens count stays missing. web_searches counts native OpenAI web searches only, passed through at USD 0.01 per search per https://openrouter.ai/docs/guides/features/server-tools/web-search; Exa and other engines are distinct, unpriced units. The generic openrouter:openai/gpt-5.4 identity is fallback-blocked. Snapshot invalidation: a change to the canonical revision, the default-routed endpoint set, token or cache rates, override threshold or search pricing requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '5.5', 'per' => '1000000'],
                'output_tokens' => ['amount' => '24.75', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.55', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.01', 'per' => '1'],
            ],
        ],
        'openrouter:openai/gpt-5.4-mini-standard-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/openai/gpt-5.4-mini-20260317/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for openai/gpt-5.4-mini (canonical openai/gpt-5.4-mini-20260317) under OpenRouter default routing at the standard service tier, all prompt lengths (no long-context override); not an alias. Default routing spans azure, openai at USD 0.75/M input and 4.5/M output; azure/us at USD 0.825/M input and 4.95/M output; every request is priced at the most expensive default-routed endpoint, so a request served at the base rate is overstated. Opt-in tiers (openai/flex, openai/fast) are excluded; callers must not select them. Reasoning is output usage; no internal_reasoning rate is published. Cache writes are deliberately unpriced: OpenAI bills pre-GPT-5.6 cache writes at the uncached input rate, while https://openrouter.ai/docs/guides/best-practices/prompt-caching says they have no cost, so a cache_write_input_tokens count stays missing. web_searches counts native OpenAI web searches only, passed through at USD 0.01 per search per https://openrouter.ai/docs/guides/features/server-tools/web-search; Exa and other engines are distinct, unpriced units. The generic openrouter:openai/gpt-5.4-mini identity is fallback-blocked. Snapshot invalidation: a change to the canonical revision, the default-routed endpoint set, token or cache rates, override threshold or search pricing requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '0.825', 'per' => '1000000'],
                'output_tokens' => ['amount' => '4.95', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.0825', 'per' => '1000000'],
                'web_searches' => ['amount' => '0.01', 'per' => '1'],
            ],
        ],
        'openrouter:openai/gpt-4.1-standard-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/openai/gpt-4.1-2025-04-14/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for openai/gpt-4.1 (canonical openai/gpt-4.1-2025-04-14) under OpenRouter default routing at the standard service tier, all prompt lengths (no long-context override); not an alias. Default routing spans azure, openai at USD 2/M input and 8/M output; azure/swedencentral at USD 2.2/M input and 8.8/M output; every request is priced at the most expensive default-routed endpoint, so a request served at the base rate is overstated. No opt-in tier endpoints are listed. Reasoning is output usage; no internal_reasoning rate is published. Cache writes are deliberately unpriced: OpenAI bills pre-GPT-5.6 cache writes at the uncached input rate, while https://openrouter.ai/docs/guides/best-practices/prompt-caching says they have no cost, so a cache_write_input_tokens count stays missing. Web search is unpriced: OpenAI bills non-reasoning web_search at USD 10 per 1,000 but web_search_preview at USD 25 per 1,000, and OpenRouter does not say which it uses. The generic openrouter:openai/gpt-4.1 identity is fallback-blocked. Snapshot invalidation: a change to the canonical revision, the default-routed endpoint set, token or cache rates, override threshold or search pricing requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '2.2', 'per' => '1000000'],
                'output_tokens' => ['amount' => '8.8', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.55', 'per' => '1000000'],
            ],
        ],
        'openrouter:openai/gpt-4.1-mini-standard-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/openai/gpt-4.1-mini-2025-04-14/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for openai/gpt-4.1-mini (canonical openai/gpt-4.1-mini-2025-04-14) under OpenRouter default routing at the standard service tier, all prompt lengths (no long-context override); not an alias. Default routing spans azure, openai at USD 0.4/M input and 1.6/M output; azure/swedencentral at USD 0.44/M input and 1.76/M output; every request is priced at the most expensive default-routed endpoint, so a request served at the base rate is overstated. No opt-in tier endpoints are listed. Reasoning is output usage; no internal_reasoning rate is published. Cache writes are deliberately unpriced: OpenAI bills pre-GPT-5.6 cache writes at the uncached input rate, while https://openrouter.ai/docs/guides/best-practices/prompt-caching says they have no cost, so a cache_write_input_tokens count stays missing. Web search is unpriced: OpenAI bills non-reasoning web_search at USD 10 per 1,000 but web_search_preview at USD 25 per 1,000, and OpenRouter does not say which it uses. The generic openrouter:openai/gpt-4.1-mini identity is fallback-blocked. Snapshot invalidation: a change to the canonical revision, the default-routed endpoint set, token or cache rates, override threshold or search pricing requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '0.44', 'per' => '1000000'],
                'output_tokens' => ['amount' => '1.76', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.11', 'per' => '1000000'],
            ],
        ],
        'openrouter:openai/gpt-4o-mini-standard-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/openai/gpt-4o-mini/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for openai/gpt-4o-mini (canonical openai/gpt-4o-mini) under OpenRouter default routing at the standard service tier, all prompt lengths (no long-context override); not an alias. Default routing spans azure, openai at USD 0.15/M input and 0.6/M output; azure/swedencentral at USD 0.165/M input and 0.66/M output; every request is priced at the most expensive default-routed endpoint, so a request served at the base rate is overstated. No opt-in tier endpoints are listed. Reasoning is output usage; no internal_reasoning rate is published. Cache writes are deliberately unpriced: OpenAI bills pre-GPT-5.6 cache writes at the uncached input rate, while https://openrouter.ai/docs/guides/best-practices/prompt-caching says they have no cost, so a cache_write_input_tokens count stays missing. Web search is unpriced: OpenRouter falls back to Exa for this model. The generic openrouter:openai/gpt-4o-mini identity is fallback-blocked. Snapshot invalidation: a change to the canonical revision, the default-routed endpoint set, token or cache rates, override threshold or search pricing requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '0.165', 'per' => '1000000'],
                'output_tokens' => ['amount' => '0.66', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.0825', 'per' => '1000000'],
            ],
        ],
        'openrouter:deepseek/deepseek-v4.1-flash-standard-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/deepseek/deepseek-v4.1-flash/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for deepseek/deepseek-v4.1-flash (canonical deepseek/deepseek-v4.1-flash-20260910) under OpenRouter default routing at the standard service tier; not :batch. Default routing spans 24 distinct price sets across 31 default-routed endpoints, quantizations fp4/fp8/unknown, context 1,000,000-1,048,576, time-of-day overrides, so the live catalog headline (input 0.3, output 1.2 per M) can understate a request. Each rate is the maximum over all default-routed endpoints, including every quantization and override: input_tokens 0.45 from fireworks/us; cached_input_tokens 0.049 from wafer; output_tokens 1.8 from fireworks/us. Maxima can come from different endpoints, so the basis can exceed any single endpoint. Callers must not opt into service tiers (service_tier, :nitro, :floor or tier slugs). Includes endpoints currently reporting a non-zero status (open-inference/fp4, fireworks). Reasoning is output usage; OpenRouter web search and other server tools are distinct, unpriced units. Prefer provider-reported usage.cost for completed requests. Worst-case default-routed endpoint, re-review on endpoint churn: any change to the endpoint set or rates requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '0.45', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.049', 'per' => '1000000'],
                'output_tokens' => ['amount' => '1.8', 'per' => '1000000'],
            ],
        ],
        'openrouter:deepseek/deepseek-v4-pro-0813-standard-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/deepseek/deepseek-v4-pro-0813/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for deepseek/deepseek-v4-pro-0813 (canonical deepseek/deepseek-v4-pro-20260813) under OpenRouter default routing at the standard service tier; not :batch. Default routing spans 15 distinct price sets across 21 default-routed endpoints, quantizations fp4/fp8/unknown, context 1,000,000-1,048,576, time-of-day overrides, so the live catalog headline (input 0.66, output 1.98 per M) can understate a request. Each rate is the maximum over all default-routed endpoints, including every quantization and override: input_tokens 1.65 from venice; cached_input_tokens 0.219 from wafer; output_tokens 5 from wafer. Maxima can come from different endpoints, so the basis can exceed any single endpoint. Callers must not opt into service tiers (service_tier, :nitro, :floor or tier slugs). Reasoning is output usage; OpenRouter web search and other server tools are distinct, unpriced units. Prefer provider-reported usage.cost for completed requests. Worst-case default-routed endpoint, re-review on endpoint churn: any change to the endpoint set or rates requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '1.65', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.219', 'per' => '1000000'],
                'output_tokens' => ['amount' => '5', 'per' => '1000000'],
            ],
        ],
        'openrouter:deepseek/deepseek-v4-pro-standard-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/deepseek/deepseek-v4-pro/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for deepseek/deepseek-v4-pro (canonical deepseek/deepseek-v4-pro-20260423) under OpenRouter default routing at the standard service tier; not :batch. Default routing spans 16 distinct price sets across 16 default-routed endpoints, quantizations fp4/fp8/unknown, context 1,000,000-1,048,576, so the live catalog headline (input 0.2088, output 0.4176 per M) can understate a request. Each rate is the maximum over all default-routed endpoints, including every quantization and override: input_tokens 1.91 from azure/us; cached_input_tokens 0.33 from venice; output_tokens 10.5 from reka. Maxima can come from different endpoints, so the basis can exceed any single endpoint. Callers must not opt into service tiers (service_tier, :nitro, :floor or tier slugs). Reasoning is output usage; OpenRouter web search and other server tools are distinct, unpriced units. Prefer provider-reported usage.cost for completed requests. Worst-case default-routed endpoint, re-review on endpoint churn: any change to the endpoint set or rates requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '1.91', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.33', 'per' => '1000000'],
                'output_tokens' => ['amount' => '10.5', 'per' => '1000000'],
            ],
        ],
        'openrouter:deepseek/deepseek-v4-flash-standard-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/deepseek/deepseek-v4-flash/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for deepseek/deepseek-v4-flash (canonical deepseek/deepseek-v4-flash-20260423) under OpenRouter default routing at the standard service tier; not :batch. Default routing spans 15 distinct price sets across 16 default-routed endpoints, quantizations fp4/fp8/unknown, context 384,000-1,048,576, so the live catalog headline (input 0.0228, output 1.28 per M) can understate a request. Each rate is the maximum over all default-routed endpoints, including every quantization and override: input_tokens 0.44 from cloudflare; cached_input_tokens 0.07 from parasail/fp8; output_tokens 1.536 from open-inference/fp4. Maxima can come from different endpoints, so the basis can exceed any single endpoint. Callers must not opt into service tiers (service_tier, :nitro, :floor or tier slugs). Includes endpoints currently reporting a non-zero status (venice). Reasoning is output usage; OpenRouter web search and other server tools are distinct, unpriced units. Prefer provider-reported usage.cost for completed requests. Worst-case default-routed endpoint, re-review on endpoint churn: any change to the endpoint set or rates requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '0.44', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.07', 'per' => '1000000'],
                'output_tokens' => ['amount' => '1.536', 'per' => '1000000'],
            ],
        ],
        'openrouter:deepseek/deepseek-v4-flash-0731-standard-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/deepseek/deepseek-v4-flash-0731/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for deepseek/deepseek-v4-flash-0731 (canonical deepseek/deepseek-v4-flash-20260731) under OpenRouter default routing at the standard service tier; not :batch. Default routing spans 22 distinct price sets across 26 default-routed endpoints, quantizations fp4/fp8/unknown, context 262,144-1,048,576, time-of-day overrides, so the live catalog headline (input 0.018, output 1.28 per M) can understate a request. Each rate is the maximum over all default-routed endpoints, including every quantization and override: input_tokens 0.44 from phala; cached_input_tokens 0.07 from coreweave/fp8; output_tokens 1.536 from open-inference/fp4. Maxima can come from different endpoints, so the basis can exceed any single endpoint. Opt-in service-tier endpoints (wafer/fast) are excluded; callers must not use service_tier, :nitro, :floor or tier slugs. Reasoning is output usage; OpenRouter web search and other server tools are distinct, unpriced units. Prefer provider-reported usage.cost for completed requests. Worst-case default-routed endpoint, re-review on endpoint churn: any change to the endpoint set or rates requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '0.44', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.07', 'per' => '1000000'],
                'output_tokens' => ['amount' => '1.536', 'per' => '1000000'],
            ],
        ],
        'openrouter:deepseek/deepseek-v3.2-standard-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/deepseek/deepseek-v3.2/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for deepseek/deepseek-v3.2 (canonical deepseek/deepseek-v3.2-20251201) under OpenRouter default routing at the standard service tier; not :batch. Default routing spans 10 distinct price sets across 12 default-routed endpoints, quantizations fp4/fp8/unknown, context 32,768-163,840, so the live catalog headline (input 0.259, output 0.42 per M) can understate a request. Each rate is the maximum over all default-routed endpoints, including every quantization and override: input_tokens 3 from mara; cached_input_tokens 0.5 from phala; output_tokens 4.5 from mara. Maxima can come from different endpoints, so the basis can exceed any single endpoint. Callers must not opt into service tiers (service_tier, :nitro, :floor or tier slugs). Includes endpoints currently reporting a non-zero status (gmicloud/fp8, atlas-cloud/fp8). Reasoning is output usage; OpenRouter web search and other server tools are distinct, unpriced units. Prefer provider-reported usage.cost for completed requests. Worst-case default-routed endpoint, re-review on endpoint churn: any change to the endpoint set or rates requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '3', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.5', 'per' => '1000000'],
                'output_tokens' => ['amount' => '4.5', 'per' => '1000000'],
            ],
        ],
        'openrouter:qwen/qwen3.7-plus-standard-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/qwen/qwen3.7-plus/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for qwen/qwen3.7-plus (canonical qwen/qwen3.7-plus-20260602) under OpenRouter default routing at the standard service tier; not :batch. Default routing spans prompt-length overrides, so the live catalog headline (input 0.32, output 1.28 per M) can understate a request. Each rate is the maximum over all default-routed endpoints, including every quantization and override: input_tokens 0.96 from alibaba/us-east-1; cached_input_tokens 0.192 from alibaba/us-east-1; cache_write_input_tokens 1.2 from alibaba/us-east-1; output_tokens 3.84 from alibaba/us-east-1. Maxima can come from different endpoints, so the basis can exceed any single endpoint. Callers must not opt into service tiers (service_tier, :nitro, :floor or tier slugs). Reasoning is output usage; OpenRouter web search and other server tools are distinct, unpriced units. Prefer provider-reported usage.cost for completed requests. Worst-case default-routed endpoint, re-review on endpoint churn: any change to the endpoint set or rates requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '0.96', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.192', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '1.2', 'per' => '1000000'],
                'output_tokens' => ['amount' => '3.84', 'per' => '1000000'],
            ],
        ],
        'openrouter:qwen/qwen3.7-max-standard-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/qwen/qwen3.7-max/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for qwen/qwen3.7-max (canonical qwen/qwen3.7-max-20260520) under OpenRouter default routing at the standard service tier; not :batch. Default routing spans 2 distinct price sets across 2 default-routed endpoints, so the live catalog headline (input 1.475, output 4.425 per M) can understate a request. Each rate is the maximum over all default-routed endpoints, including every quantization and override: input_tokens 2 from alibaba/us-east-1; cached_input_tokens 0.4 from alibaba/us-east-1; cache_write_input_tokens 1.84375 from alibaba; output_tokens 6 from alibaba/us-east-1. Maxima can come from different endpoints, so the basis can exceed any single endpoint. Callers must not opt into service tiers (service_tier, :nitro, :floor or tier slugs). Reasoning is output usage; OpenRouter web search and other server tools are distinct, unpriced units. Prefer provider-reported usage.cost for completed requests. Worst-case default-routed endpoint, re-review on endpoint churn: any change to the endpoint set or rates requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '2', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.4', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '1.84375', 'per' => '1000000'],
                'output_tokens' => ['amount' => '6', 'per' => '1000000'],
            ],
        ],
        'openrouter:qwen/qwen3.7-flash-standard-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/qwen/qwen3.7-flash/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for qwen/qwen3.7-flash (canonical qwen/qwen3.7-flash-20260727) under OpenRouter default routing at the standard service tier; not :batch. Default routing spans 2 distinct price sets across 2 default-routed endpoints, prompt-length overrides, so the live catalog headline (input 0.03, output 0.13 per M) can understate a request. Each rate is the maximum over all default-routed endpoints, including every quantization and override: input_tokens 0.23 from alibaba/us-east-1; cached_input_tokens 0.046 from alibaba/us-east-1; cache_write_input_tokens 0.25 from alibaba; output_tokens 0.92 from alibaba/us-east-1. Maxima can come from different endpoints, so the basis can exceed any single endpoint. Callers must not opt into service tiers (service_tier, :nitro, :floor or tier slugs). Reasoning is output usage; OpenRouter web search and other server tools are distinct, unpriced units. Prefer provider-reported usage.cost for completed requests. Worst-case default-routed endpoint, re-review on endpoint churn: any change to the endpoint set or rates requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '0.23', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.046', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '0.25', 'per' => '1000000'],
                'output_tokens' => ['amount' => '0.92', 'per' => '1000000'],
            ],
        ],
        'openrouter:qwen/qwen3.8-2.4t-a95b-standard-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/qwen/qwen3.8-2.4t-a95b/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for qwen/qwen3.8-2.4t-a95b (canonical qwen/qwen3.8-2.4t-a95b-20260812) under OpenRouter default routing at the standard service tier; not :batch. Default routing spans 3 distinct price sets across 7 default-routed endpoints, quantizations fp4/fp8/unknown, context 262,144-1,048,576, so the live catalog headline (input 2, output 6 per M) can understate a request. Each rate is the maximum over all default-routed endpoints, including every quantization and override: input_tokens 2 from novita; cached_input_tokens 0.25 from novita; cache_write_input_tokens 2.5 from alibaba; output_tokens 6 from novita. Maxima can come from different endpoints, so the basis can exceed any single endpoint. Callers must not opt into service tiers (service_tier, :nitro, :floor or tier slugs). Reasoning is output usage; OpenRouter web search and other server tools are distinct, unpriced units. Prefer provider-reported usage.cost for completed requests. Worst-case default-routed endpoint, re-review on endpoint churn: any change to the endpoint set or rates requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '2', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.25', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '2.5', 'per' => '1000000'],
                'output_tokens' => ['amount' => '6', 'per' => '1000000'],
            ],
        ],
        'openrouter:qwen/qwen3.8-27b-standard-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/qwen/qwen3.8-27b/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for qwen/qwen3.8-27b (canonical qwen/qwen3.8-27b-20260814) under OpenRouter default routing at the standard service tier; not :batch. Default routing spans 18 distinct price sets across 19 default-routed endpoints, quantizations bf16/fp16/fp4/fp8/unknown, context 65,536-1,000,000, so the live catalog headline (input 0.425, output 2.55 per M) can understate a request. Each rate is the maximum over all default-routed endpoints, including every quantization and override: input_tokens 0.99 from cerebras/fp16; cached_input_tokens 0.99 from cerebras/fp16; cache_write_input_tokens 0.53125 from alibaba; output_tokens 4.7 from modelrun/fp4. Maxima can come from different endpoints, so the basis can exceed any single endpoint. Callers must not opt into service tiers (service_tier, :nitro, :floor or tier slugs). Includes endpoints currently reporting a non-zero status (alibaba, cloudflare). Reasoning is output usage; OpenRouter web search and other server tools are distinct, unpriced units. Prefer provider-reported usage.cost for completed requests. Worst-case default-routed endpoint, re-review on endpoint churn: any change to the endpoint set or rates requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '0.99', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.99', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '0.53125', 'per' => '1000000'],
                'output_tokens' => ['amount' => '4.7', 'per' => '1000000'],
            ],
        ],
        'openrouter:qwen/qwen3-coder-flash-standard-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/qwen/qwen3-coder-flash/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for qwen/qwen3-coder-flash (canonical qwen/qwen3-coder-flash) under OpenRouter default routing at the standard service tier; not :batch. Default routing spans prompt-length overrides, so the live catalog headline (input 0.195, output 0.975 per M) can understate a request. Each rate is the maximum over all default-routed endpoints, including every quantization and override: input_tokens 0.52 from alibaba; cached_input_tokens 0.104 from alibaba; cache_write_input_tokens 0.65 from alibaba; output_tokens 2.6 from alibaba. Maxima can come from different endpoints, so the basis can exceed any single endpoint. Callers must not opt into service tiers (service_tier, :nitro, :floor or tier slugs). Reasoning is output usage; OpenRouter web search and other server tools are distinct, unpriced units. Prefer provider-reported usage.cost for completed requests. Worst-case default-routed endpoint, re-review on endpoint churn: any change to the endpoint set or rates requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '0.52', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.104', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '0.65', 'per' => '1000000'],
                'output_tokens' => ['amount' => '2.6', 'per' => '1000000'],
            ],
        ],
        'openrouter:qwen/qwen3-coder-standard-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/qwen/qwen3-coder/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for qwen/qwen3-coder (canonical qwen/qwen3-coder-480b-a35b-07-25) under OpenRouter default routing at the standard service tier; not :batch. Default routing spans 3 distinct price sets across 3 default-routed endpoints, quantizations fp4/fp8/unknown, context 256,000-262,144, so the live catalog headline (input 0.3, output 1 per M) can understate a request. Each rate is the maximum over all default-routed endpoints, including every quantization and override: input_tokens 0.35 from venice/fp8; cached_input_tokens 0.1 from deepinfra/turbo; output_tokens 1.8 from google-vertex/us-south1. Maxima can come from different endpoints, so the basis can exceed any single endpoint. Callers must not opt into service tiers (service_tier, :nitro, :floor or tier slugs). Includes endpoints currently reporting a non-zero status (deepinfra/turbo, venice/fp8). Reasoning is output usage; OpenRouter web search and other server tools are distinct, unpriced units. Prefer provider-reported usage.cost for completed requests. Worst-case default-routed endpoint, re-review on endpoint churn: any change to the endpoint set or rates requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '0.35', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.1', 'per' => '1000000'],
                'output_tokens' => ['amount' => '1.8', 'per' => '1000000'],
            ],
        ],
        'openrouter:qwen/qwen-plus-standard-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/qwen/qwen-plus/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for qwen/qwen-plus (canonical qwen/qwen-plus-2025-01-25) under OpenRouter default routing at the standard service tier; not :batch. Default routing spans prompt-length overrides, so the live catalog headline (input 0.26, output 0.78 per M) can understate a request. Each rate is the maximum over all default-routed endpoints, including every quantization and override: input_tokens 0.78 from alibaba; cached_input_tokens 0.156 from alibaba; cache_write_input_tokens 0.975 from alibaba; output_tokens 2.34 from alibaba. Maxima can come from different endpoints, so the basis can exceed any single endpoint. Callers must not opt into service tiers (service_tier, :nitro, :floor or tier slugs). Reasoning is output usage; OpenRouter web search and other server tools are distinct, unpriced units. Prefer provider-reported usage.cost for completed requests. Worst-case default-routed endpoint, re-review on endpoint churn: any change to the endpoint set or rates requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '0.78', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.156', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '0.975', 'per' => '1000000'],
                'output_tokens' => ['amount' => '2.34', 'per' => '1000000'],
            ],
        ],
        'openrouter:moonshotai/kimi-k3-standard-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/moonshotai/kimi-k3/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for moonshotai/kimi-k3 (canonical moonshotai/kimi-k3-20260715) under OpenRouter default routing at the standard service tier; not :batch. Default routing spans 17 distinct price sets across 22 default-routed endpoints, quantizations fp4/fp8/mxfp4/unknown, so the live catalog headline (input 0.8, output 13.5 per M) can understate a request. Each rate is the maximum over all default-routed endpoints, including every quantization and override: input_tokens 4.5 from fireworks/us; cached_input_tokens 1.2 from akashml/fp4; cache_write_input_tokens 3.75 from amazon-bedrock/us-east-2; output_tokens 22.5 from fireworks/us. Maxima can come from different endpoints, so the basis can exceed any single endpoint. Opt-in service-tier endpoints (fireworks/fast, inference-net/fast, parasail/fast) are excluded; callers must not use service_tier, :nitro, :floor or tier slugs. Reasoning is output usage; OpenRouter web search and other server tools are distinct, unpriced units. Prefer provider-reported usage.cost for completed requests. Worst-case default-routed endpoint, re-review on endpoint churn: any change to the endpoint set or rates requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '4.5', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '1.2', 'per' => '1000000'],
                'cache_write_input_tokens' => ['amount' => '3.75', 'per' => '1000000'],
                'output_tokens' => ['amount' => '22.5', 'per' => '1000000'],
            ],
        ],
        'openrouter:moonshotai/kimi-k2.7-code-standard-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/moonshotai/kimi-k2.7-code/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for moonshotai/kimi-k2.7-code (canonical moonshotai/kimi-k2.7-code-20260612) under OpenRouter default routing at the standard service tier; not :batch. Default routing spans 9 distinct price sets across 12 default-routed endpoints, quantizations fp4/fp8/int4/unknown, context 256,000-262,144, so the live catalog headline (input 0.6712, output 3.35 per M) can understate a request. Each rate is the maximum over all default-routed endpoints, including every quantization and override: input_tokens 1.9 from moonshotai/highspeed; cached_input_tokens 0.38 from moonshotai/highspeed; output_tokens 8 from moonshotai/highspeed. Maxima can come from different endpoints, so the basis can exceed any single endpoint. Callers must not opt into service tiers (service_tier, :nitro, :floor or tier slugs). Reasoning is output usage; OpenRouter web search and other server tools are distinct, unpriced units. Prefer provider-reported usage.cost for completed requests. Worst-case default-routed endpoint, re-review on endpoint churn: any change to the endpoint set or rates requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '1.9', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.38', 'per' => '1000000'],
                'output_tokens' => ['amount' => '8', 'per' => '1000000'],
            ],
        ],
        'openrouter:moonshotai/kimi-k2.6-standard-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/moonshotai/kimi-k2.6/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for moonshotai/kimi-k2.6 (canonical moonshotai/kimi-k2.6-20260420) under OpenRouter default routing at the standard service tier; not :batch. Default routing spans 13 distinct price sets across 17 default-routed endpoints, quantizations bf16/fp4/fp8/int4/unknown, context 256,000-262,144, so the live catalog headline (input 0.465, output 2.45 per M) can understate a request. Each rate is the maximum over all default-routed endpoints, including every quantization and override: input_tokens 1.09 from phala; cached_input_tokens 0.37 from phala; output_tokens 4.6 from phala. Maxima can come from different endpoints, so the basis can exceed any single endpoint. Callers must not opt into service tiers (service_tier, :nitro, :floor or tier slugs). Reasoning is output usage; OpenRouter web search and other server tools are distinct, unpriced units. Prefer provider-reported usage.cost for completed requests. Worst-case default-routed endpoint, re-review on endpoint churn: any change to the endpoint set or rates requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '1.09', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.37', 'per' => '1000000'],
                'output_tokens' => ['amount' => '4.6', 'per' => '1000000'],
            ],
        ],
        'openrouter:z-ai/glm-5.3-standard-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/z-ai/glm-5.3/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for z-ai/glm-5.3 (canonical z-ai/glm-5.3-20260816) under OpenRouter default routing at the standard service tier; not :batch. Default routing spans 23 distinct price sets across 37 default-routed endpoints, quantizations fp4/fp8/nvfp4/unknown, context 262,124-1,048,576, so the live catalog headline (input 0.039, output 6 per M) can understate a request. Each rate is the maximum over all default-routed endpoints, including every quantization and override: input_tokens 1.54 from mistral/nvfp4; cached_input_tokens 0.26 from io-net/fp8; output_tokens 6 from wafer/us. Maxima can come from different endpoints, so the basis can exceed any single endpoint. Opt-in service-tier endpoints (alibaba/fast, baseten/fast, fireworks/fast) are excluded; callers must not use service_tier, :nitro, :floor or tier slugs. Reasoning is output usage; OpenRouter web search and other server tools are distinct, unpriced units. Prefer provider-reported usage.cost for completed requests. Worst-case default-routed endpoint, re-review on endpoint churn: any change to the endpoint set or rates requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '1.54', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.26', 'per' => '1000000'],
                'output_tokens' => ['amount' => '6', 'per' => '1000000'],
            ],
        ],
        'openrouter:z-ai/glm-5.3-flash-standard-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/z-ai/glm-5.3-flash/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for z-ai/glm-5.3-flash (canonical z-ai/glm-5.3-flash-20260826) under OpenRouter default routing at the standard service tier; not :batch. Default routing spans 19 distinct price sets across 32 default-routed endpoints, quantizations fp4/fp8/nvfp4/unknown, context 262,144-1,048,576, so the live catalog headline (input 0.15, output 0.5 per M) can understate a request. Each rate is the maximum over all default-routed endpoints, including every quantization and override: input_tokens 0.225 from fireworks/us; cached_input_tokens 0.099 from inceptron/fp8; output_tokens 1.6 from reka. Maxima can come from different endpoints, so the basis can exceed any single endpoint. Opt-in service-tier endpoints (parasail/fast) are excluded; callers must not use service_tier, :nitro, :floor or tier slugs. Reasoning is output usage; OpenRouter web search and other server tools are distinct, unpriced units. Prefer provider-reported usage.cost for completed requests. Worst-case default-routed endpoint, re-review on endpoint churn: any change to the endpoint set or rates requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '0.225', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.099', 'per' => '1000000'],
                'output_tokens' => ['amount' => '1.6', 'per' => '1000000'],
            ],
        ],
        'openrouter:z-ai/glm-5.2-standard-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/z-ai/glm-5.2/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for z-ai/glm-5.2 (canonical z-ai/glm-5.2-20260616) under OpenRouter default routing at the standard service tier; not :batch. Default routing spans 21 distinct price sets across 29 default-routed endpoints, quantizations fp4/fp8/mxfp4/nvfp4/unknown, context 262,144-1,048,576, so the live catalog headline (input 0.06, output 7 per M) can understate a request. Each rate is the maximum over all default-routed endpoints, including every quantization and override: input_tokens 1.54 from mistral/eu; cached_input_tokens 0.26 from cloudflare; output_tokens 10 from wafer. Maxima can come from different endpoints, so the basis can exceed any single endpoint. Opt-in service-tier endpoints (alibaba/fast, baidu/fast, baseten/fast, decart/fast) are excluded; callers must not use service_tier, :nitro, :floor or tier slugs. Includes endpoints currently reporting a non-zero status (siliconflow/fp8). Reasoning is output usage; OpenRouter web search and other server tools are distinct, unpriced units. Prefer provider-reported usage.cost for completed requests. Worst-case default-routed endpoint, re-review on endpoint churn: any change to the endpoint set or rates requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '1.54', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.26', 'per' => '1000000'],
                'output_tokens' => ['amount' => '10', 'per' => '1000000'],
            ],
        ],
        'openrouter:z-ai/glm-5.1-standard-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/z-ai/glm-5.1/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for z-ai/glm-5.1 (canonical z-ai/glm-5.1-20260406) under OpenRouter default routing at the standard service tier; not :batch. Default routing spans 10 distinct price sets across 13 default-routed endpoints, quantizations fp8/unknown, context 200,000-204,800, so the live catalog headline (input 0.966, output 3.036 per M) can understate a request. Each rate is the maximum over all default-routed endpoints, including every quantization and override: input_tokens 1.4014 from venice/fp8; cached_input_tokens 0.6 from siliconflow/fp8; output_tokens 4.4044 from venice/fp8. Maxima can come from different endpoints, so the basis can exceed any single endpoint. Callers must not opt into service tiers (service_tier, :nitro, :floor or tier slugs). Reasoning is output usage; OpenRouter web search and other server tools are distinct, unpriced units. Prefer provider-reported usage.cost for completed requests. Worst-case default-routed endpoint, re-review on endpoint churn: any change to the endpoint set or rates requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '1.4014', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.6', 'per' => '1000000'],
                'output_tokens' => ['amount' => '4.4044', 'per' => '1000000'],
            ],
        ],
        'openrouter:z-ai/glm-5-standard-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/z-ai/glm-5/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for z-ai/glm-5 (canonical z-ai/glm-5-20260211) under OpenRouter default routing at the standard service tier; not :batch. Default routing spans 5 distinct price sets across 8 default-routed endpoints, quantizations fp8/unknown, context 198,000-204,800, so the live catalog headline (input 0.6, output 1.92 per M) can understate a request. Each rate is the maximum over all default-routed endpoints, including every quantization and override: input_tokens 1 from amazon-bedrock; cached_input_tokens 0.2 from siliconflow/fp8; output_tokens 3.2 from amazon-bedrock. Maxima can come from different endpoints, so the basis can exceed any single endpoint. Callers must not opt into service tiers (service_tier, :nitro, :floor or tier slugs). Reasoning is output usage; OpenRouter web search and other server tools are distinct, unpriced units. Prefer provider-reported usage.cost for completed requests. Worst-case default-routed endpoint, re-review on endpoint churn: any change to the endpoint set or rates requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '1', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.2', 'per' => '1000000'],
                'output_tokens' => ['amount' => '3.2', 'per' => '1000000'],
            ],
        ],
        'openrouter:z-ai/glm-4.7-standard-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/z-ai/glm-4.7/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for z-ai/glm-4.7 (canonical z-ai/glm-4.7-20251222) under OpenRouter default routing at the standard service tier; not :batch. Default routing spans 6 distinct price sets across 6 default-routed endpoints, quantizations fp4/fp8/unknown, context 131,072-204,800, so the live catalog headline (input 0.6, output 2.2 per M) can understate a request. Each rate is the maximum over all default-routed endpoints, including every quantization and override: input_tokens 0.7 from mancer/fp4; cached_input_tokens 0.11 from z-ai/fp4; output_tokens 2.5 from mancer/fp4. Maxima can come from different endpoints, so the basis can exceed any single endpoint. Callers must not opt into service tiers (service_tier, :nitro, :floor or tier slugs). Includes endpoints currently reporting a non-zero status (deepinfra/fp4, venice/fp4). Reasoning is output usage; OpenRouter web search and other server tools are distinct, unpriced units. Prefer provider-reported usage.cost for completed requests. Worst-case default-routed endpoint, re-review on endpoint churn: any change to the endpoint set or rates requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '0.7', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.11', 'per' => '1000000'],
                'output_tokens' => ['amount' => '2.5', 'per' => '1000000'],
            ],
        ],
        'openrouter:z-ai/glm-4.6-standard-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/z-ai/glm-4.6/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for z-ai/glm-4.6 (canonical z-ai/glm-4.6) under OpenRouter default routing at the standard service tier; not :batch. Default routing spans 4 distinct price sets across 4 default-routed endpoints, quantizations bf16/fp4, context 198,000-204,800, so the live catalog headline (input 0.5, output 2 per M) can understate a request. Each rate is the maximum over all default-routed endpoints, including every quantization and override: input_tokens 0.6 from z-ai/fp4; cached_input_tokens 0.11 from novita/bf16; output_tokens 2.2 from novita/bf16. Maxima can come from different endpoints, so the basis can exceed any single endpoint. Callers must not opt into service tiers (service_tier, :nitro, :floor or tier slugs). Reasoning is output usage; OpenRouter web search and other server tools are distinct, unpriced units. Prefer provider-reported usage.cost for completed requests. Worst-case default-routed endpoint, re-review on endpoint churn: any change to the endpoint set or rates requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '0.6', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.11', 'per' => '1000000'],
                'output_tokens' => ['amount' => '2.2', 'per' => '1000000'],
            ],
        ],
        'openrouter:z-ai/glm-4.5-air-standard-max' => [
            'source' => 'https://openrouter.ai/api/v1/models/z-ai/glm-4.5-air/endpoints',
            'notes' => 'Checked 2026-10-10. Synthetic worst-case billing basis for z-ai/glm-4.5-air (canonical z-ai/glm-4.5-air) under OpenRouter default routing at the standard service tier; not :batch. Default routing spans 3 distinct price sets across 3 default-routed endpoints, quantizations bf16/fp8, so the live catalog headline (input 0.13, output 0.85 per M) can understate a request. Each rate is the maximum over all default-routed endpoints, including every quantization and override: input_tokens 0.2 from z-ai/fp8; cached_input_tokens 0.03 from z-ai/fp8; output_tokens 1.1 from z-ai/fp8. Maxima can come from different endpoints, so the basis can exceed any single endpoint. Callers must not opt into service tiers (service_tier, :nitro, :floor or tier slugs). Reasoning is output usage; OpenRouter web search and other server tools are distinct, unpriced units. Prefer provider-reported usage.cost for completed requests. Worst-case default-routed endpoint, re-review on endpoint churn: any change to the endpoint set or rates requires fresh review.',
            'rates' => [
                'input_tokens' => ['amount' => '0.2', 'per' => '1000000'],
                'cached_input_tokens' => ['amount' => '0.03', 'per' => '1000000'],
                'output_tokens' => ['amount' => '1.1', 'per' => '1000000'],
            ],
        ],
        'you:answer' => [
            'source' => 'https://you.com/pricing',
            'notes' => 'Checked 2026-09-03. Carried forward unchanged from snapshot v3.',
            'rates' => [
                'requests' => ['amount' => '5', 'per' => '1000'],
            ],
        ],
        'you:research-lite' => [
            'source' => 'https://you.com/resources/research-api-by-you-com#pricing',
            'notes' => 'Checked 2026-09-03. Carried forward unchanged from snapshot v3.',
            'rates' => [
                'requests' => ['amount' => '12', 'per' => '1000'],
            ],
        ],
        'you:research-standard' => [
            'source' => 'https://you.com/resources/research-api-by-you-com#pricing',
            'notes' => 'Checked 2026-09-03. Carried forward unchanged from snapshot v3.',
            'rates' => [
                'requests' => ['amount' => '50', 'per' => '1000'],
            ],
        ],
        'you:research-deep' => [
            'source' => 'https://you.com/resources/research-api-by-you-com#pricing',
            'notes' => 'Checked 2026-09-03. Carried forward unchanged from snapshot v3.',
            'rates' => [
                'requests' => ['amount' => '100', 'per' => '1000'],
            ],
        ],
        'you:research-exhaustive' => [
            'source' => 'https://you.com/resources/research-api-by-you-com#pricing',
            'notes' => 'Checked 2026-09-03. Carried forward unchanged from snapshot v3.',
            'rates' => [
                'requests' => ['amount' => '450', 'per' => '1000'],
            ],
        ],
        'perplexity:agent-low' => [
            'source' => 'https://docs.perplexity.ai/docs/getting-started/pricing',
            'notes' => 'Checked 2026-09-03. Carried forward unchanged from snapshot v3.',
            'rates' => [
                'web_searches' => ['amount' => '0.0025', 'per' => '1'],
                'fetch_url_requests' => ['amount' => '0.0005', 'per' => '1'],
                'people_searches' => ['amount' => '0.005', 'per' => '1'],
                'finance_searches' => ['amount' => '0.005', 'per' => '1'],
                'sandbox_sessions' => ['amount' => '0.03', 'per' => '1'],
                'sandbox_searches' => ['amount' => '0.0025', 'per' => '1'],
            ],
        ],
        'perplexity:agent-medium' => [
            'source' => 'https://docs.perplexity.ai/docs/getting-started/pricing',
            'notes' => 'Checked 2026-09-10. Medium is a dynamic preset; only stable tool rates are included. Routed-model units remain unpriced and provider-reported actual cost wins.',
            'rates' => [
                'web_searches' => ['amount' => '0.0025', 'per' => '1'],
                'fetch_url_requests' => ['amount' => '0.0005', 'per' => '1'],
                'people_searches' => ['amount' => '0.005', 'per' => '1'],
                'finance_searches' => ['amount' => '0.005', 'per' => '1'],
                'sandbox_sessions' => ['amount' => '0.03', 'per' => '1'],
                'sandbox_searches' => ['amount' => '0.0025', 'per' => '1'],
            ],
        ],
        'perplexity:agent-high' => [
            'source' => 'https://docs.perplexity.ai/docs/getting-started/pricing',
            'notes' => 'Checked 2026-09-03. Carried forward unchanged from snapshot v3.',
            'rates' => [
                'web_searches' => ['amount' => '0.0025', 'per' => '1'],
                'fetch_url_requests' => ['amount' => '0.0005', 'per' => '1'],
                'people_searches' => ['amount' => '0.005', 'per' => '1'],
                'finance_searches' => ['amount' => '0.005', 'per' => '1'],
                'sandbox_sessions' => ['amount' => '0.03', 'per' => '1'],
                'sandbox_searches' => ['amount' => '0.0025', 'per' => '1'],
            ],
        ],
        'perplexity:search' => [
            'source' => 'https://docs.perplexity.ai/docs/getting-started/pricing',
            'notes' => 'Checked 2026-09-10. Price per successful Search API POST request.',
            'rates' => [
                'requests' => ['amount' => '5', 'per' => '1000'],
            ],
        ],
        'parallel:turbo' => [
            'source' => 'https://docs.parallel.ai/getting-started/pricing',
            'notes' => 'Checked 2026-09-10. Turbo Search includes up to ten results; additional_results counts only results above ten.',
            'rates' => [
                'requests' => ['amount' => '1', 'per' => '1000'],
                'additional_results' => ['amount' => '1', 'per' => '1000'],
            ],
        ],
        'parallel:research-pro' => [
            'source' => 'https://docs.parallel.ai/getting-started/pricing',
            'notes' => 'Checked 2026-09-10. Price per successful Task API run using the pro processor.',
            'rates' => [
                'processor_requests' => ['amount' => '100', 'per' => '1000'],
            ],
        ],
        'valyu:research-standard' => [
            'source' => 'https://docs.valyu.ai/pricing',
            'notes' => 'Checked 2026-09-10. Base Standard DeepResearch task only. Optional screenshots, code execution, and extra deliverables are separate units and must be included when used. Prefer the response cost when reported.',
            'rates' => [
                'research_requests' => ['amount' => '0.5', 'per' => '1'],
                'screenshot_urls' => ['amount' => '0.05', 'per' => '1'],
                'code_executions' => ['amount' => '0.1', 'per' => '1'],
                'additional_deliverables' => ['amount' => '0.1', 'per' => '1'],
            ],
        ],
        'typesafe:jev-1.13.0' => [
            'source' => 'https://docs.typesafe.ai/models',
            'notes' => 'Checked 2026-09-28. Jev 1.13 System One classification over POST /v1/systemone: USD 42 per billion (0.042 per million) input tokens. TypeSafe documents output tokens as free, so output_tokens carries an explicit published zero rate rather than being omitted; responses still report a non-zero output count. No cache, reasoning, or per-request units are billed. The jev-latest and jev-preview aliases point to jev-1.13.0 per the same page and are mapped in aliases; a completed laravel/ai ClassificationResponse reports the resolved versioned model in meta->model. Any other version stays unpriced until reviewed.',
            'rates' => [
                'input_tokens' => ['amount' => '0.042', 'per' => '1000000'],
                'output_tokens' => ['amount' => '0', 'per' => '1000000'],
            ],
        ],
        'xai:grok-4.6' => [
            'source' => 'https://docs.x.ai/developers/pricing',
            'notes' => 'Checked 2026-09-26. Web Search bills $5 per 1k calls (searches). X Search bills per item fetched since the 2026-09-21 change: every post returned by a search or thread fetch, including parent and quoted posts, counts toward x_search_posts at $5 per 1k, and every profile returned by a user search counts toward x_search_profiles at $10 per 1k. Map usage.server_side_tool_usage_details web_search_calls to searches, x_posts_fetched to x_search_posts, and x_users_fetched to x_search_profiles; never map x_search_calls onto the per-item units. Token rates are omitted because grok-4.6 has a context-length tier once a prompt reaches 200k tokens plus a 1.1x US regional multiplier that this snapshot cannot select safely.',
            'rates' => [
                'searches' => ['amount' => '5', 'per' => '1000'],
                'x_search_posts' => ['amount' => '5', 'per' => '1000'],
                'x_search_profiles' => ['amount' => '10', 'per' => '1000'],
            ],
        ],
    ],
];
