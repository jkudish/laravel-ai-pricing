<?php

declare(strict_types=1);

/*
 * Reviewed package-owned snapshot for providers without a suitable stable,
 * machine-readable runtime catalog. See .agents/skills/fetching-provider-pricing
 * before changing rates, units, identities, or provenance.
 */
return [
    'version' => 9,
    'retrieved_at' => '2026-09-28T03:42:00+00:00',
    'effective_at' => null,
    'currency' => 'USD',
    'fallback_blocked' => [
        'anthropic:claude-sonnet-5',
        'anthropic:claude-sonnet-5-us',
        'openrouter:openai/gpt-5.6-terra-272k',
        'openrouter:openai/gpt-6-luna-272k',
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
    ],
    'prices' => [
        'anthropic:claude-sonnet-5-global' => [
            'source' => 'https://platform.claude.com/docs/en/about-claude/pricing',
            'notes' => 'Checked 2026-09-17. Claude API global routing (the default) for Claude Sonnet 5 base input and output tokens only. The generic model identity is intentionally not priced because inference_geo=us costs 1.1x. Prompt-cache writes and reads require their exact usage units and TTL-specific rates; web search is a separate billed unit.',
            'rates' => [
                'input_tokens' => ['amount' => '2', 'per' => '1000000'],
                'output_tokens' => ['amount' => '10', 'per' => '1000000'],
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
