<?php

declare(strict_types=1);

use Jkudish\LaravelAiPricing\CostCalculator;
use Jkudish\LaravelAiPricing\Enums\CostCompleteness;
use Jkudish\LaravelAiPricing\Enums\PricingSource;
use Jkudish\LaravelAiPricing\Sources\PackagePricingSource;
use Jkudish\LaravelAiPricing\ValueObjects\ModelIdentity;
use Jkudish\LaravelAiPricing\ValueObjects\Usage;

function packagePricingSource(): PackagePricingSource
{
    /** @var array{version: int, retrieved_at: string, effective_at: string|null, currency: string, fallback_blocked?: list<string>, prices: array<string, array{source: string, notes?: string, rates: array<string, array{amount: string|int, per: string|int}>}>} $snapshot */
    $snapshot = require __DIR__.'/../../resources/pricing/provider-skus.php';

    return new PackagePricingSource($snapshot);
}

/** @return array{version: int, retrieved_at: string, effective_at: string|null, currency: string, fallback_blocked?: list<string>, prices: array<string, array{source: string, notes?: string, rates: array<string, array{amount: string|int, per: string|int}>}>} */
function packagePricingSnapshot(): array
{
    return require __DIR__.'/../../resources/pricing/provider-skus.php';
}

it('provides reviewed package pricing for stable provider SKUs', function (string $provider, string $sku, string $unit, string $amount, string $per): void {
    $definition = packagePricingSource()->find(new ModelIdentity($provider, $sku));

    expect($definition)->not->toBeNull()
        ->and($definition?->source)->toBe(PricingSource::ProviderNative)
        ->and($definition?->identity->toArray())->toBe(['provider' => $provider, 'model' => $sku])
        ->and((string) $definition?->rates[$unit]->amount)->toBe($amount)
        ->and((string) $definition?->rates[$unit]->per)->toBe($per)
        ->and($definition?->retrievedAt?->format('Y-m-d'))->toBe('2026-09-26')
        ->and($definition?->effectiveAt)->toBeNull()
        ->and($definition?->sourceReference)->toStartWith('https://');
})->with([
    'Claude Sonnet 5 global routing' => ['anthropic', 'claude-sonnet-5-global', 'input_tokens', '2', '1000000'],
    'Brave Answers' => ['brave', 'answers', 'queries', '4', '1000'],
    'Brave Search' => ['brave', 'search', 'requests', '5', '1000'],
    'Exa Search' => ['exa', 'search', 'additional_results', '1', '1000'],
    'Exa Research' => ['exa', 'research', 'agent_compute_units', '0.1', '1'],
    'Kagi FastGPT' => ['kagi', 'fastgpt', 'uncached_queries', '15', '1000'],
    'OpenRouter GPT-5.6 Terra long-context bound basis' => ['openrouter', 'openai/gpt-5.6-terra-272k', 'input_tokens', '4', '1000000'],
    'You Answer' => ['you', 'answer', 'requests', '5', '1000'],
    'You Research Lite' => ['you', 'research-lite', 'requests', '12', '1000'],
    'You Research Standard' => ['you', 'research-standard', 'requests', '50', '1000'],
    'You Research Deep' => ['you', 'research-deep', 'requests', '100', '1000'],
    'You Research Exhaustive' => ['you', 'research-exhaustive', 'requests', '450', '1000'],
    'Perplexity Agent Low' => ['perplexity', 'agent-low', 'web_searches', '0.0025', '1'],
    'Perplexity Agent Medium' => ['perplexity', 'agent-medium', 'web_searches', '0.0025', '1'],
    'Perplexity Agent High' => ['perplexity', 'agent-high', 'sandbox_sessions', '0.03', '1'],
    'Perplexity Search' => ['perplexity', 'search', 'requests', '5', '1000'],
    'Parallel Turbo' => ['parallel', 'turbo', 'additional_results', '1', '1000'],
    'Parallel Research Pro' => ['parallel', 'research-pro', 'processor_requests', '100', '1000'],
    'Valyu Research Standard' => ['valyu', 'research-standard', 'research_requests', '0.5', '1'],
    'xAI Grok 4.6' => ['xai', 'grok-4.6', 'searches', '5', '1000'],
    'xAI Grok 4.6 X Search posts' => ['xai', 'grok-4.6', 'x_search_posts', '5', '1000'],
    'xAI Grok 4.6 X Search profiles' => ['xai', 'grok-4.6', 'x_search_profiles', '10', '1000'],
]);

it('quotes Claude Sonnet 5 global routing exactly and leaves undeclared billed units missing', function (): void {
    $definition = packagePricingSource()->find(new ModelIdentity('anthropic', 'claude-sonnet-5-global'));
    $complete = (new CostCalculator)->calculate(
        new Usage(['input_tokens' => 1_000_000, 'output_tokens' => 64_000]),
        $definition,
    );
    $partial = (new CostCalculator)->calculate(
        new Usage(['input_tokens' => 1_000_000, 'output_tokens' => 64_000, 'web_searches' => 1]),
        $definition,
    );

    expect((string) $complete->cost?->amount)->toBe('2.64')
        ->and($complete->completeness)->toBe(CostCompleteness::Complete)
        ->and($complete->source)->toBe(PricingSource::ProviderNative)
        ->and($complete->provenance?->reference)->toBe('https://platform.claude.com/docs/en/about-claude/pricing')
        ->and((string) $partial->cost?->amount)->toBe('2.64')
        ->and($partial->completeness)->toBe(CostCompleteness::Partial)
        ->and($partial->missingUnits)->toBe(['web_searches']);
});

it('quotes the OpenRouter bounded basis exactly and keeps generic or native-search units fail-closed', function (): void {
    $identity = new ModelIdentity('openrouter', 'openai/gpt-5.6-terra-272k');
    $definition = packagePricingSource()->find($identity);

    $budget = 30;
    $completions = $budget + 1;
    $bound = (new CostCalculator)->calculate(new Usage([
        'input_tokens' => $completions * 1_050_000,
        'output_tokens' => $completions * 128_000,
        'exa_search_requests' => $budget,
    ]), $definition);

    // 31 x 1_050_000 x 0.000004 = 130.2; 31 x 128_000 x 0.000018 = 71.424; 30 x 0.007 = 0.21.
    expect((string) $bound->cost?->amount)->toBe('201.834')
        ->and($bound->completeness)->toBe(CostCompleteness::Complete)
        ->and($bound->source)->toBe(PricingSource::ProviderNative)
        ->and($bound->provenance?->reference)->toBe('https://openrouter.ai/api/v1/models/openai/gpt-5.6-terra-20260709/endpoints');

    $nativeSearch = (new CostCalculator)->calculate(new Usage([
        'input_tokens' => 1_000_000,
        'output_tokens' => 1_000,
        'exa_search_requests' => 1,
        'web_searches' => 1,
    ]), $definition);
    $reasoning = (new CostCalculator)->calculate(new Usage([
        'input_tokens' => 1_000_000,
        'output_tokens' => 1_000,
        'exa_search_requests' => 1,
        'reasoning_tokens' => 500,
    ]), $definition);
    $cacheWrite = (new CostCalculator)->calculate(new Usage([
        'cache_write_input_tokens' => 1_000_000,
        'cached_input_tokens' => 1_000_000,
    ]), $definition);

    expect($nativeSearch->completeness)->toBe(CostCompleteness::Partial)
        ->and($nativeSearch->missingUnits)->toBe(['web_searches'])
        ->and($reasoning->completeness)->toBe(CostCompleteness::Complete)
        ->and($reasoning->missingUnits)->toBe([])
        ->and((string) $cacheWrite->cost?->amount)->toBe('5.4')
        ->and($cacheWrite->completeness)->toBe(CostCompleteness::Complete)
        ->and(packagePricingSource()->find(new ModelIdentity('openrouter', 'openai/gpt-5.6-terra')))->toBeNull()
        ->and(packagePricingSource()->allowsFallback($identity))->toBeFalse();
});

it('quotes the Luna max canary using its own long-context basis without repricing Terra', function (): void {
    $identity = new ModelIdentity('openrouter', 'openai/gpt-6-luna-272k');
    $definition = packagePricingSource()->find($identity);
    $usage = new Usage([
        'cache_write_input_tokens' => 6_300_000,
        'output_tokens' => 768_000,
        'exa_search_requests' => 5,
    ]);
    $quote = (new CostCalculator)->calculate($usage, $definition);
    $terra = (new CostCalculator)->calculate(
        $usage,
        packagePricingSource()->find(new ModelIdentity('openrouter', 'openai/gpt-5.6-terra-272k')),
    );

    // 6.3M cache writes x $0.25/M + 0.768M output x $0.75/M + 5 x $0.007.
    expect((string) $quote->cost?->amount)->toBe('2.186')
        ->and($quote->completeness)->toBe(CostCompleteness::Complete)
        ->and($quote->provenance?->reference)->toBe('https://openrouter.ai/api/v1/models/openai/gpt-6-luna-20260922/endpoints')
        ->and((string) $terra->cost?->amount)->toBe('45.359')
        ->and(packagePricingSource()->allowsFallback($identity))->toBeFalse()
        ->and(packagePricingSource()->find(new ModelIdentity('openrouter', 'openai/gpt-6-luna')))->toBeNull()
        ->and(packagePricingSource()->find(new ModelIdentity('openrouter', 'openai/gpt-6-luna-pro-272k')))->toBeNull();
});

it('prices exclusive Luna token partitions and refuses to invent separate reasoning or native search fees', function (): void {
    $definition = packagePricingSource()->find(new ModelIdentity('openrouter', 'openai/gpt-6-luna-272k'));
    $usage = [
        'input_tokens' => 2_000_000,
        'cached_input_tokens' => 3_000_000,
        'cache_write_input_tokens' => 1_000_000,
        'output_tokens' => 4_000_000,
        'exa_search_requests' => 2,
    ];
    $complete = (new CostCalculator)->calculate(new Usage($usage), $definition);
    $partial = (new CostCalculator)->calculate(new Usage([
        ...$usage,
        'reasoning_tokens' => 17,
        'web_searches' => 1,
    ]), $definition);

    // $0.4 uncached + $0.06 cache read + $0.25 cache write + $3 output + $0.014 Exa.
    // The 17 reasoning tokens ride inside the inclusive 4M output count at the
    // output rate because the Luna catalog publishes no separate reasoning rate,
    // so they add no line and no missing unit of their own.
    expect((string) $complete->cost?->amount)->toBe('3.724')
        ->and($complete->completeness)->toBe(CostCompleteness::Complete)
        ->and((string) $partial->cost?->amount)->toBe('3.724')
        ->and($partial->completeness)->toBe(CostCompleteness::Partial)
        ->and($partial->missingUnits)->toBe(['web_searches']);
});

it('does not apply the global Claude rate to generic or US-only identities', function (string $sku): void {
    expect(packagePricingSource()->find(new ModelIdentity('anthropic', $sku)))->toBeNull()
        ->and(packagePricingSource()->allowsFallback(new ModelIdentity('anthropic', $sku)))->toBeFalse();
})->with([
    'generic model identity' => 'claude-sonnet-5',
    'US-only routing identity' => 'claude-sonnet-5-us',
]);

it('calculates compound rates exactly and reports unknown required units', function (): void {
    $definition = packagePricingSource()->find(new ModelIdentity('brave', 'answers'));
    $quote = (new CostCalculator)->calculate(
        new Usage(['queries' => 2, 'input_tokens' => 1234, 'output_tokens' => 300, 'unknown_component' => 1]),
        $definition,
    );

    expect((string) $quote->cost?->amount)->toBe('0.01567')
        ->and($quote->completeness)->toBe(CostCompleteness::Partial)
        ->and($quote->missingUnits)->toBe(['unknown_component']);
});

it('does not invent a Perplexity Agent total when only routed model usage is known', function (): void {
    $definition = packagePricingSource()->find(new ModelIdentity('perplexity', 'agent-low'));
    $unavailable = (new CostCalculator)->calculate(Usage::tokens(2000, 1000), $definition);
    $partial = (new CostCalculator)->calculate(
        new Usage(['input_tokens' => 2000, 'output_tokens' => 1000, 'web_searches' => 1, 'fetch_url_requests' => 1]),
        $definition,
    );

    expect($unavailable->cost)->toBeNull()
        ->and($unavailable->completeness)->toBe(CostCompleteness::Unavailable)
        ->and($partial->cost?->amount->isEqualTo('0.003'))->toBeTrue()
        ->and($partial->completeness)->toBe(CostCompleteness::Partial)
        ->and($partial->missingUnits)->toBe(['input_tokens', 'output_tokens']);
});

it('quotes every DataForSEO task SKU with exact decimal pricing and provenance', function (string $sku, string $amount, string $multipleAmount, string $source): void {
    $identity = new ModelIdentity('dataforseo', $sku);
    $definition = packagePricingSource()->find($identity);
    $single = (new CostCalculator)->calculate(new Usage(['requests' => 1]), $definition);
    $multiple = (new CostCalculator)->calculate(new Usage(['requests' => 7]), $definition);

    expect($definition)->not->toBeNull()
        ->and($definition?->identity->toArray())->toBe(['provider' => 'dataforseo', 'model' => $sku])
        ->and((string) $definition?->rates['requests']->amount)->toBe($amount)
        ->and((string) $definition?->rates['requests']->per)->toBe('1')
        ->and($definition?->sourceReference)->toBe($source)
        ->and($definition?->retrievedAt?->format(DATE_ATOM))->toBe('2026-09-26T18:50:00+00:00')
        ->and($definition?->effectiveAt)->toBeNull()
        ->and((string) $single->cost?->amount)->toBe($amount)
        ->and($single->completeness)->toBe(CostCompleteness::Complete)
        ->and($single->source)->toBe(PricingSource::ProviderNative)
        ->and($single->provenance?->reference)->toBe($source)
        ->and($single->provenance?->retrievedAt?->format(DATE_ATOM))->toBe('2026-09-26T18:50:00+00:00')
        ->and((string) $multiple->cost?->amount)->toBe($multipleAmount)
        ->and($multiple->completeness)->toBe(CostCompleteness::Complete);
})->with([
    'ChatGPT LLM Scraper Standard' => ['chatgpt-llm-scraper-standard', '0.0012', '0.0084', 'https://dataforseo.com/pricing/ai-optimization/llm-scraper'],
    'ChatGPT LLM Scraper Live' => ['chatgpt-llm-scraper-live', '0.004', '0.028', 'https://dataforseo.com/pricing/ai-optimization/llm-scraper'],
    'Gemini LLM Scraper Standard' => ['gemini-llm-scraper-standard', '0.0012', '0.0084', 'https://dataforseo.com/pricing/ai-optimization/llm-scraper'],
    'Gemini LLM Scraper Live' => ['gemini-llm-scraper-live', '0.004', '0.028', 'https://dataforseo.com/pricing/ai-optimization/llm-scraper'],
    'Google AI Mode Standard' => ['google-ai-mode-standard', '0.0012', '0.0084', 'https://dataforseo.com/pricing/serp/google-ai-mode-serp-api'],
    'Google AI Mode Live' => ['google-ai-mode-live', '0.004', '0.028', 'https://dataforseo.com/pricing/serp/google-ai-mode-serp-api'],
]);

it('keeps the six DataForSEO identities distinct and records their actual review date', function (): void {
    $snapshot = packagePricingSnapshot();
    $prices = array_filter(
        $snapshot['prices'],
        static fn (string $key): bool => str_starts_with($key, 'dataforseo:'),
        ARRAY_FILTER_USE_KEY,
    );

    expect($snapshot['version'])->toBe(8)
        ->and($snapshot['retrieved_at'])->toBe('2026-09-26T18:50:00+00:00')
        ->and(array_keys($prices))->toBe([
            'dataforseo:chatgpt-llm-scraper-standard',
            'dataforseo:chatgpt-llm-scraper-live',
            'dataforseo:gemini-llm-scraper-standard',
            'dataforseo:gemini-llm-scraper-live',
            'dataforseo:google-ai-mode-standard',
            'dataforseo:google-ai-mode-live',
        ]);

    foreach ($prices as $price) {
        expect($price['notes'] ?? null)->toContain('Checked 2026-09-06');
    }
});

it('retains a per-SKU source review date when assembling a newer snapshot', function (): void {
    $snapshot = packagePricingSnapshot();

    foreach ($snapshot['prices'] as $price) {
        expect($price['notes'] ?? null)->toMatch('/Checked 2026-09-(?:03|06|10|17|18|23|26)\./');
    }
});

it('does not turn missing or unknown DataForSEO usage into a complete or zero quote', function (string $sku): void {
    $definition = packagePricingSource()->find(new ModelIdentity('dataforseo', $sku));
    $missing = (new CostCalculator)->calculate(new Usage([]), $definition);
    $zero = (new CostCalculator)->calculate(new Usage(['requests' => 0]), $definition);
    $unknown = (new CostCalculator)->calculate(new Usage(['retrieval_gets' => 1]), $definition);

    expect($missing->cost)->toBeNull()
        ->and($missing->completeness)->toBe(CostCompleteness::Unavailable)
        ->and($zero->cost)->toBeNull()
        ->and($zero->completeness)->toBe(CostCompleteness::Unavailable)
        ->and($unknown->cost)->toBeNull()
        ->and($unknown->completeness)->toBe(CostCompleteness::Unavailable)
        ->and($unknown->missingUnits)->toBe(['retrieval_gets']);
})->with([
    'chatgpt-llm-scraper-standard',
    'chatgpt-llm-scraper-live',
    'gemini-llm-scraper-standard',
    'gemini-llm-scraper-live',
    'google-ai-mode-standard',
    'google-ai-mode-live',
]);

it('keeps frontier research and unknown SKUs unavailable', function (string $provider, string $sku): void {
    expect(packagePricingSource()->find(new ModelIdentity($provider, $sku)))->toBeNull();
})->with([
    ['you', 'research-frontier'],
    ['perplexity', 'agent-auto'],
    ['searchapi', 'search'],
    ['searchapi', 'google'],
    ['tavily', 'search'],
    ['jina', 'search'],
    ['serpapi', 'search'],
    ['serpbase', 'search'],
    ['serpbase', 'news'],
    ['gemini', 'deep-research'],
    ['parallel', 'search'],
    ['valyu', 'search'],
    ['firecrawl', 'search'],
    ['dataforseo', 'llm-responses'],
    ['dataforseo', 'google-ai-mode-high-priority'],
]);

it('blocks remote fallback only for identities explicitly declared unavailable', function (string $provider, string $sku): void {
    expect(packagePricingSource()->allowsFallback(new ModelIdentity($provider, $sku)))->toBeFalse();
})->with([
    ['you', 'research-frontier'],
    ['searchapi', 'search'],
    ['tavily', 'search'],
    ['jina', 'search'],
    ['serpapi', 'search'],
    ['serpbase', 'search'],
    ['serpbase', 'news'],
    ['gemini', 'deep-research'],
    ['parallel', 'search'],
    ['valyu', 'search'],
    ['firecrawl', 'search'],
]);

it('allows remote fallback for an undeclared identity', function (): void {
    expect(packagePricingSource()->allowsFallback(new ModelIdentity('other', 'search')))->toBeTrue();
});

it('covers every PHP parity profile with its pricing identity and disposition', function (string $provider, string $sku, bool $hasBuiltInRate): void {
    $definition = packagePricingSource()->find(new ModelIdentity($provider, $sku));

    expect($definition !== null)->toBe($hasBuiltInRate);
})->with([
    'brave-search/search' => ['brave', 'search', true],
    'tavily/search' => ['tavily', 'search', false],
    'perplexity-search/search' => ['perplexity', 'search', true],
    'perplexity-deep-research/research medium' => ['perplexity', 'agent-medium', true],
    'jina-search/search' => ['jina', 'search', false],
    'serpapi/search' => ['serpapi', 'search', false],
    'serpbase/search' => ['serpbase', 'search', false],
    'serpbase/news' => ['serpbase', 'news', false],
    'exa/research' => ['exa', 'research', true],
    'gemini-deep/research' => ['gemini', 'deep-research', false],
    'grok-x-only/x' => ['xai', 'grok-4.6', true],
    'grok-combined/combined' => ['xai', 'grok-4.6', true],
    'parallel/search' => ['parallel', 'search', false],
    'parallel/turbo' => ['parallel', 'turbo', true],
    'parallel/research pro' => ['parallel', 'research-pro', true],
    'valyu/search' => ['valyu', 'search', false],
    'valyu/research standard' => ['valyu', 'research-standard', true],
    'firecrawl-search/search' => ['firecrawl', 'search', false],
]);

it('keeps variable components partial and missing or zero usage unavailable', function (): void {
    $xai = packagePricingSource()->find(new ModelIdentity('xai', 'grok-4.6'));
    $partial = (new CostCalculator)->calculate(
        new Usage(['input_tokens' => 250_000, 'output_tokens' => 1_000, 'searches' => 2]),
        $xai,
    );
    $missing = (new CostCalculator)->calculate(new Usage([]), $xai);
    $zero = (new CostCalculator)->calculate(new Usage(['searches' => 0]), $xai);

    expect((string) $partial->cost?->amount)->toBe('0.01')
        ->and($partial->completeness)->toBe(CostCompleteness::Partial)
        ->and($partial->missingUnits)->toBe(['input_tokens', 'output_tokens'])
        ->and($missing->cost)->toBeNull()
        ->and($missing->completeness)->toBe(CostCompleteness::Unavailable)
        ->and($zero->cost)->toBeNull()
        ->and($zero->completeness)->toBe(CostCompleteness::Unavailable);
});

it('bills X Search per fetched post and profile after the 2026-09-21 xAI change', function (): void {
    $xai = packagePricingSource()->find(new ModelIdentity('xai', 'grok-4.6'));

    $mixed = (new CostCalculator)->calculate(
        new Usage([
            'input_tokens' => 250_000,
            'output_tokens' => 1_000,
            'searches' => 4,
            'x_search_posts' => 3_000,
            'x_search_profiles' => 500,
        ]),
        $xai,
    );

    // Hand-computed: 4 x 5/1000 = 0.02 web search calls, 3_000 x 5/1000 = 15
    // fetched posts, 500 x 10/1000 = 5 fetched profiles; 0.02 + 15 + 5 = 20.02.
    // The token counts stay unpriced because grok-4.6 has context tiers.
    expect((string) $mixed->cost?->amount)->toBe('20.02')
        ->and($mixed->completeness)->toBe(CostCompleteness::Partial)
        ->and($mixed->missingUnits)->toBe(['input_tokens', 'output_tokens'])
        ->and((string) $mixed->provenance?->reference)->toBe('https://docs.x.ai/developers/pricing');

    $toolsOnly = (new CostCalculator)->calculate(
        new Usage(['x_search_posts' => 1_000, 'x_search_profiles' => 250]),
        $xai,
    );

    // 1_000 x 5/1000 = 5 posts plus 250 x 10/1000 = 2.5 profiles = 7.5.
    expect((string) $toolsOnly->cost?->amount)->toBe('7.5')
        ->and($toolsOnly->completeness)->toBe(CostCompleteness::Complete)
        ->and($toolsOnly->missingUnits)->toBe([]);
});
