<?php

declare(strict_types=1);

/*
 * Reviewed package-owned snapshot for providers without a suitable stable,
 * machine-readable runtime catalog. See .agents/skills/fetching-provider-pricing
 * before changing rates, units, identities, or provenance.
 */
return [
    'version' => 4,
    'retrieved_at' => '2026-09-10T00:00:00+00:00',
    'effective_at' => null,
    'currency' => 'USD',
    'fallback_blocked' => [
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
    'prices' => [
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
        'xai:grok-4.6' => [
            'source' => 'https://docs.x.ai/developers/pricing',
            'notes' => 'Checked 2026-09-10. Current Web Search and X Search tool-call rate only. Token rates are omitted because grok-4.6 has context-length tiers that this snapshot cannot select safely. X Search pricing is scheduled to change on 2026-09-21.',
            'rates' => [
                'searches' => ['amount' => '5', 'per' => '1000'],
            ],
        ],
    ],
];
