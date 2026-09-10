# Provider pricing sources

Use these primary-source starting points, then verify that the page still describes the product and billing semantics being changed.

| Provider/product | Primary source | Review focus |
| --- | --- | --- |
| Brave Answers | <https://api-dashboard.search.brave.com/documentation/services/answers> | Query/search and token components; response component and total costs |
| Brave Search | <https://api-dashboard.search.brave.com/documentation/pricing> | Fixed request rate and plan limits |
| DataForSEO LLM Scraper | <https://dataforseo.com/pricing/ai-optimization/llm-scraper> | Normal Standard and Live base result-page prices; free Standard retrieval GETs; priority and option surcharges |
| DataForSEO Google AI Mode | <https://dataforseo.com/pricing/serp/google-ai-mode-serp-api> | Normal Standard and Live base SERP-page prices; free Standard retrieval GETs; priority and option surcharges |
| Exa Search | <https://exa.ai/docs/reference/pricing.md> and <https://exa.ai/docs/reference/search> | Included results, additions, summaries, and `costDollars` |
| Exa Research | <https://exa.ai/docs/reference/pricing> | Effort-specific Agent rates, ACUs, searches, enrichment, and provider-reported cost |
| Firecrawl Search | <https://www.firecrawl.dev/pricing> | Account-plan USD per credit and Search credit semantics |
| Gemini Deep Research | <https://ai.google.dev/gemini-api/docs/pricing> and <https://ai.google.dev/gemini-api/docs/deep-research> | Agent/model/tool variability and whether a conservative preflight quantity exists |
| Jina Search | <https://jina.ai/reader/#pricing> | Fixed token consumption and availability of a stable USD conversion |
| Kagi FastGPT | <https://help.kagi.com/kagi/api/fastgpt.html> | Web-enabled query price and free cached responses |
| You.com APIs | <https://you.com/pricing> and <https://you.com/resources/research-api-by-you-com#pricing> | Fixed Answer calls and exact named Research tiers |
| Perplexity Agent | <https://docs.perplexity.ai/docs/getting-started/pricing> and <https://docs.perplexity.ai/docs/agent-api/presets> | Actual response total, routed model rates, tool units, and non-normative preset examples |
| Perplexity Search | <https://docs.perplexity.ai/docs/getting-started/pricing> | Successful Search request rate |
| Parallel Search and Task | <https://docs.parallel.ai/getting-started/pricing> | Search modes, included/additional results, processor identity, and successful-run billing |
| SerpAPI Search | <https://serpapi.com/pricing> | Account plan, successful searches, and speed multipliers |
| SearchAPI | <https://www.searchapi.io/pricing> | Successful HTTP 200 searches and account-plan-specific rates |
| Tavily Search | <https://www.tavily.com/pricing> | Account-plan USD per credit and Basic/Advanced credit usage |
| Valyu Search and Research | <https://docs.valyu.ai/pricing> | Per-source search rates, research mode, optional tools, and provider-reported cost |
| xAI Grok tools | <https://docs.x.ai/developers/pricing> | Context-tiered model rates and separately changing Web/X Search tool rates |
| Portkey | `https://configs.portkey.ai/pricing/{provider}.json` | Exact model-key match, cents encoding, aliases, and cache namespace |

## Snapshot review

For each changed entry in `resources/pricing/provider-skus.php`:

1. Capture the retrieval timestamp in UTC and an official effective date only when explicitly published.
2. Keep rates as decimal strings and currency explicit at snapshot level.
3. Explain conditional, account-specific, lower-bound, or informational semantics in `notes`.
4. Confirm the SKU is stable and distinct from request options whose pricing differs.
5. Compare the resulting diff to the previous snapshot version and call out removed or unavailable rates.
6. Exercise the source through mocked or local fixtures only; never use an API key to “verify” billing.
