<?php

declare(strict_types=1);

use Brick\Math\BigDecimal;
use Jkudish\LaravelAiPricing\CostCalculator;
use Jkudish\LaravelAiPricing\Enums\CostCompleteness;
use Jkudish\LaravelAiPricing\Enums\PricingSource;
use Jkudish\LaravelAiPricing\Facades\AiPricing;
use Jkudish\LaravelAiPricing\Sources\PackagePricingSource;
use Jkudish\LaravelAiPricing\ValueObjects\ModelIdentity;
use Jkudish\LaravelAiPricing\ValueObjects\Usage;

function packagePricingSource(): PackagePricingSource
{
    /** @var array{version: int, retrieved_at: string, effective_at: string|null, currency: string, fallback_blocked?: list<string>, aliases?: array<string, string>, prices: array<string, array{source: string, notes?: string, rates: array<string, array{amount: string|int, per: string|int}>}>} $snapshot */
    $snapshot = require __DIR__.'/../../resources/pricing/provider-skus.php';

    return new PackagePricingSource($snapshot);
}

/** @return array{version: int, retrieved_at: string, effective_at: string|null, currency: string, fallback_blocked?: list<string>, aliases?: array<string, string>, prices: array<string, array{source: string, notes?: string, rates: array<string, array{amount: string|int, per: string|int}>}>} */
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
        ->and($definition?->retrievedAt?->format('Y-m-d'))->toBe('2026-10-10')
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
    'OpenRouter Gemini 3.1 Flash Lite worst-case routing basis' => ['openrouter', 'google/gemini-3.1-flash-lite-standard-max', 'input_tokens', '0.275', '1000000'],
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
    'TypeSafe Jev 1.13 input' => ['typesafe', 'jev-1.13.0', 'input_tokens', '0.042', '1000000'],
    'TypeSafe Jev 1.13 free output' => ['typesafe', 'jev-1.13.0', 'output_tokens', '0', '1000000'],
    'xAI Grok 4.6' => ['xai', 'grok-4.6', 'searches', '5', '1000'],
    'xAI Grok 4.6 X Search posts' => ['xai', 'grok-4.6', 'x_search_posts', '5', '1000'],
    'xAI Grok 4.6 X Search profiles' => ['xai', 'grok-4.6', 'x_search_profiles', '10', '1000'],
]);

it('quotes Claude Sonnet 5 global routing exactly and leaves undeclared billed units missing', function (): void {
    $definition = packagePricingSource()->find(new ModelIdentity('anthropic', 'claude-sonnet-5-global'));
    $complete = (new CostCalculator)->calculate(
        new Usage([
            'input_tokens' => 1_000_000,
            'output_tokens' => 64_000,
            'cached_input_tokens' => 500_000,
            'cache_write_input_tokens' => 300_000,
            'cache_write_input_tokens_5m' => 200_000,
            'cache_write_input_tokens_1h' => 100_000,
            'web_searches' => 1,
        ]),
        $definition,
    );
    $partial = (new CostCalculator)->calculate(
        new Usage(['input_tokens' => 1_000_000, 'output_tokens' => 64_000, 'cache_write_input_tokens' => 300_000]),
        $definition,
    );

    // Sonnet 5 standard price: 2/M input, 10/M output, cache reads at 0.1x
    // input (0.20/M), 5-minute writes at 1.25x (2.50/M), 1-hour writes at 2x
    // (4/M) and USD 10 per 1,000 searches:
    // 2 + 0.64 + 0.1 + 0.5 + 0.4 + 0.01 = 3.65.
    expect((string) $complete->cost?->amount)->toBe('3.65')
        ->and($complete->completeness)->toBe(CostCompleteness::Complete)
        ->and($complete->source)->toBe(PricingSource::ProviderNative)
        ->and($complete->provenance?->reference)->toBe('https://platform.claude.com/docs/en/about-claude/pricing')
        // A cache-write aggregate with no TTL split cannot choose a rate:
        // 2 + 0.64 = 2.64 with the write missing.
        ->and((string) $partial->cost?->amount)->toBe('2.64')
        ->and($partial->completeness)->toBe(CostCompleteness::Partial)
        ->and($partial->missingUnits)->toBe(['cache_write_input_tokens']);
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
        ->and($definition?->retrievedAt?->format(DATE_ATOM))->toBe('2026-10-10T01:47:24+00:00')
        ->and($definition?->effectiveAt)->toBeNull()
        ->and((string) $single->cost?->amount)->toBe($amount)
        ->and($single->completeness)->toBe(CostCompleteness::Complete)
        ->and($single->source)->toBe(PricingSource::ProviderNative)
        ->and($single->provenance?->reference)->toBe($source)
        ->and($single->provenance?->retrievedAt?->format(DATE_ATOM))->toBe('2026-10-10T01:47:24+00:00')
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

    expect($snapshot['version'])->toBe(12)
        ->and($snapshot['retrieved_at'])->toBe('2026-10-10T01:47:24+00:00')
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
        expect($price['notes'] ?? null)->toMatch('/Checked 2026-(?:09-(?:03|06|10|17|18|23|26|28)|10-(?:09|10))\./');
    }
});

it('quotes reviewed eval billing bases with exact decimals and provenance without hiding unknown units', function (string $key, string $source, array $amounts, string $expectedCost): void {
    [$provider, $model] = explode(':', $key, 2);
    $definition = packagePricingSource()->find(new ModelIdentity($provider, $model));
    expect($definition)->not->toBeNull();
    assert($definition !== null);

    expect(array_keys($definition->rates))->toBe(array_keys($amounts));
    foreach ($amounts as $unit => $amount) {
        expect(packagePricingSnapshot()['prices'][$key]['rates'][$unit]['amount'])->toBeString()
            ->and(packagePricingSnapshot()['prices'][$key]['rates'][$unit]['per'])->toBeString()
            ->and((string) $definition->rates[$unit]->amount)->toBe($amount)
            ->and((string) $definition->rates[$unit]->per)->toBe('1000000')
            ->and($definition->rates[$unit]->currency)->toBe('USD');
    }

    $usage = ['input_tokens' => 2000];
    if (array_key_exists('output_tokens', $amounts)) {
        $usage['output_tokens'] = 3000;
    }
    $complete = (new CostCalculator)->calculate(new Usage($usage), $definition);
    $partial = (new CostCalculator)->calculate(new Usage([...$usage, 'unknown_billed_unit' => 1]), $definition);
    $unknown = (new CostCalculator)->calculate(new Usage(['unknown_billed_unit' => 1]), $definition);

    expect($complete->cost?->amount->isEqualTo($expectedCost))->toBeTrue()
        ->and($complete->completeness)->toBe(CostCompleteness::Complete)
        ->and($complete->source)->toBe(PricingSource::ProviderNative)
        ->and($complete->provenance?->reference)->toBe($source)
        ->and($definition->retrievedAt?->format(DATE_ATOM))->toBe('2026-10-10T01:47:24+00:00')
        ->and($definition->effectiveAt)->toBeNull()
        ->and(packagePricingSnapshot()['prices'][$key]['notes'] ?? '')->toContain('Checked 2026-10-09.')
        ->and($partial->cost?->amount->isEqualTo($expectedCost))->toBeTrue()
        ->and($partial->completeness)->toBe(CostCompleteness::Partial)
        ->and($partial->missingUnits)->toBe(['unknown_billed_unit'])
        ->and($unknown->cost)->toBeNull()
        ->and($unknown->completeness)->toBe(CostCompleteness::Unavailable);
})->with([
    ['anthropic:claude-opus-5-5-global-standard', 'https://platform.claude.com/docs/en/about-claude/pricing', ['input_tokens' => '4', 'output_tokens' => '20', 'cached_input_tokens' => '0.20', 'cache_write_input_tokens_5m' => '5', 'cache_write_input_tokens_1h' => '8'], '0.068'],
    ['anthropic:claude-sonnet-5-5-global-standard', 'https://platform.claude.com/docs/en/about-claude/pricing', ['input_tokens' => '2', 'output_tokens' => '10', 'cached_input_tokens' => '0.10', 'cache_write_input_tokens_5m' => '2.50', 'cache_write_input_tokens_1h' => '4'], '0.034'],
    ['anthropic:claude-haiku-5-5-global-short', 'https://platform.claude.com/docs/en/about-claude/pricing', ['input_tokens' => '0.10', 'output_tokens' => '0.50', 'cached_input_tokens' => '0.01', 'cache_write_input_tokens_5m' => '0.125', 'cache_write_input_tokens_1h' => '0.20'], '0.0017'],
    ['anthropic:claude-haiku-5-5-global-long', 'https://platform.claude.com/docs/en/about-claude/pricing', ['input_tokens' => '0.50', 'output_tokens' => '2.50', 'cached_input_tokens' => '0.05', 'cache_write_input_tokens_5m' => '0.625', 'cache_write_input_tokens_1h' => '1'], '0.0085'],
    ['openai:gpt-6.1-sol-standard-short', 'https://developers.openai.com/api/docs/pricing', ['input_tokens' => '2', 'output_tokens' => '10', 'cached_input_tokens' => '0.10', 'cache_write_input_tokens' => '2.50'], '0.034'],
    ['openai:gpt-6.1-sol-standard-long', 'https://developers.openai.com/api/docs/pricing', ['input_tokens' => '4', 'output_tokens' => '15', 'cached_input_tokens' => '0.20', 'cache_write_input_tokens' => '5'], '0.053'],
    ['openai:gpt-6-luna-standard-short', 'https://developers.openai.com/api/docs/pricing', ['input_tokens' => '0.10', 'output_tokens' => '0.50', 'cached_input_tokens' => '0.01', 'cache_write_input_tokens' => '0.125'], '0.0017'],
    ['openai:gpt-6-luna-standard-long', 'https://developers.openai.com/api/docs/pricing', ['input_tokens' => '0.20', 'output_tokens' => '0.75', 'cached_input_tokens' => '0.02', 'cache_write_input_tokens' => '0.25'], '0.00265'],
    ['openai:decisions-gpt-6-luna-short', 'https://developers.openai.com/api/docs/guides/decisions', ['input_tokens' => '0.10', 'output_tokens' => '0', 'cached_input_tokens' => '0', 'cache_write_input_tokens' => '0'], '0.0002'],
    ['openai:decisions-gpt-6-luna-long', 'https://developers.openai.com/api/docs/guides/decisions', ['input_tokens' => '0.20', 'output_tokens' => '0', 'cached_input_tokens' => '0', 'cache_write_input_tokens' => '0'], '0.0004'],
    ['zai:glm-5.3', 'https://docs.z.ai/guides/overview/pricing', ['input_tokens' => '1.4', 'cached_input_tokens' => '0.26', 'output_tokens' => '4.4'], '0.016'],
    ['zai:glm-5.3-flash', 'https://docs.z.ai/guides/overview/pricing', ['input_tokens' => '0.15', 'cached_input_tokens' => '0.03', 'output_tokens' => '0.50'], '0.0018'],
    ['deepseek:deepseek-v4-pro-peak', 'https://api-docs.deepseek.com/quick_start/pricing', ['input_tokens' => '1.32', 'cached_input_tokens' => '0.044', 'output_tokens' => '3.96'], '0.01452'],
    ['cloudflare:@cf/cloudflare/clef', 'https://developers.cloudflare.com/workers-ai/platform/pricing/', ['input_tokens' => '0.24'], '0.00048'],
    ['cloudflare:@cf/cloudflare/clef-flash', 'https://developers.cloudflare.com/workers-ai/platform/pricing/', ['input_tokens' => '0.09'], '0.00018'],
]);

it('keeps unspecified routing and retired eval identities unavailable instead of choosing a cheap tier', function (string $provider, string $sku): void {
    $identity = new ModelIdentity($provider, $sku);

    expect(packagePricingSource()->find($identity))->toBeNull()
        ->and(packagePricingSource()->allowsFallback($identity))->toBeFalse();
})->with([
    ['anthropic', 'claude-opus-5-5'],
    ['anthropic', 'claude-sonnet-5-5'],
    ['anthropic', 'claude-haiku-5-5'],
    ['openai', 'gpt-6.1-sol'],
    ['openai', 'gpt-6-luna'],
    ['openai', 'decisions'],
    ['deepseek', 'deepseek-v4'],
    ['deepseek', 'deepseek-v4-pro'],
    ['deepseek', 'deepseek-v4-flash'],
    ['deepseek', 'deepseek-v4-flash-vision-exp'],
    ['openrouter', 'google/gemini-3.1-flash-lite'],
    ['anthropic', 'claude-fable-5-1'],
    ['anthropic', 'claude-opus-4-8'],
    ['gemini', 'gemini-3.8-flash'],
    ['gemini', 'gemini-2.5-pro'],
    ['gemini', 'gemini-flash-latest'],
    ['openai', 'gpt-4o'],
    ['openai', 'gpt-4.1'],
    ['openai', 'gpt-4o-2024-08-06'],
    ['openai', 'gpt-5.6-cyber'],
    ['openai', 'gpt-5-search-api'],
    ['deepseek', 'deepseek-flash'],
    ['dashscope', 'qwen3.7-plus'],
    ['dashscope', 'qwen-plus-2025-12-01'],
    ['openrouter', 'anthropic/claude-opus-5.5'],
    ['openrouter', 'google/gemini-3.1-pro-preview'],
    ['openrouter', 'google/gemini-2.5-pro'],
    ['openrouter', 'openai/gpt-5.6-terra'],
    ['openrouter', 'openai/gpt-6-luna'],
    ['openrouter', 'openai/gpt-oss-120b'],
    ['openrouter', 'deepseek/deepseek-v4-pro-0813'],
    ['openrouter', 'qwen/qwen3.7-plus'],
    ['openrouter', 'moonshotai/kimi-k3'],
    ['openrouter', 'z-ai/glm-5.3'],
]);

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

it('bills TypeSafe Jev input tokens only and keeps its published free output complete', function (): void {
    $jev = packagePricingSource()->find(new ModelIdentity('typesafe', 'jev-1.13.0'));

    // The laravel/ai TypeSafe fixture reports 312 input and 48 output tokens.
    // Hand-computed: 312 x 0.042 / 1_000_000 = 0.000013104; output is free.
    $quote = (new CostCalculator)->calculate(new Usage(['input_tokens' => 312, 'output_tokens' => 48]), $jev);

    expect((string) $quote->cost?->amount)->toBe('0.000013104')
        ->and($quote->cost?->currency)->toBe('USD')
        ->and($quote->completeness)->toBe(CostCompleteness::Complete)
        ->and($quote->missingUnits)->toBe([])
        ->and($quote->source)->toBe(PricingSource::ProviderNative)
        ->and((string) $quote->provenance?->reference)->toBe('https://docs.typesafe.ai/models');

    // A billion input tokens is the provider's headline USD 42 rate.
    $headline = (new CostCalculator)->calculate(new Usage(['input_tokens' => 1_000_000_000]), $jev);

    expect((string) $headline->cost?->amount)->toBe('42');
});

it('prices documented Jev aliases at their target rate without rewriting the requested identity', function (string $alias): void {
    $definition = packagePricingSource()->find(new ModelIdentity('typesafe', $alias));
    $target = packagePricingSource()->find(new ModelIdentity('typesafe', 'jev-1.13.0'));

    expect($definition)->not->toBeNull()
        ->and($definition?->identity->toArray())->toBe(['provider' => 'typesafe', 'model' => $alias])
        ->and(array_map(static fn ($rate): array => $rate->toArray(), $definition?->rates ?? []))
        ->toBe(array_map(static fn ($rate): array => $rate->toArray(), $target?->rates ?? []))
        ->and($definition?->sourceReference)->toBe('https://docs.typesafe.ai/models');
})->with(['jev-latest', 'jev-preview', 'JEV-Latest']);

it('points every snapshot alias at a priced identity and never at another alias', function (): void {
    $snapshot = packagePricingSnapshot();

    expect($snapshot['aliases'] ?? [])->not->toBeEmpty();

    foreach ($snapshot['aliases'] ?? [] as $alias => $target) {
        expect($snapshot['prices'])->not->toHaveKey($alias)
            ->and($snapshot['prices'])->toHaveKey($target)
            ->and($snapshot['aliases'] ?? [])->not->toHaveKey($target);
    }
});

it('quotes the Gemini 3.1 Flash Lite routing basis at regional worst-case rates and keeps unreviewed units missing', function (): void {
    $identity = new ModelIdentity('openrouter', 'google/gemini-3.1-flash-lite-standard-max');
    $definition = packagePricingSource()->find($identity);
    assert($definition !== null);

    $quote = (new CostCalculator)->calculate(new Usage([
        'input_tokens' => 1_000_000,
        'cached_input_tokens' => 200_000,
        'output_tokens' => 100_000,
        'reasoning_tokens' => 40_000,
        'web_searches' => 3,
    ]), $definition);

    // 1M x 0.275/M + 0.2M x 0.0275/M + 0.1M x 1.65/M (reasoning is inside the
    // output count and has no separate rate) + 3 x 0.014 = 0.275 + 0.0055 +
    // 0.165 + 0.042 = 0.4875.
    expect((string) $quote->cost?->amount)->toBe('0.4875')
        ->and($quote->completeness)->toBe(CostCompleteness::Complete)
        ->and($quote->missingUnits)->toBe([])
        ->and($quote->source)->toBe(PricingSource::ProviderNative)
        ->and($quote->provenance?->reference)->toBe('https://openrouter.ai/api/v1/models/google/gemini-3.1-flash-lite-20260507/endpoints')
        ->and(packagePricingSnapshot()['prices'][$identity->key()]['notes'] ?? '')->toContain('Checked 2026-10-09.');

    $cacheWrite = (new CostCalculator)->calculate(new Usage(['input_tokens' => 1_000, 'cache_write_input_tokens' => 1_000]), $definition);
    $exa = (new CostCalculator)->calculate(new Usage(['input_tokens' => 1_000, 'exa_search_requests' => 1]), $definition);

    expect($cacheWrite->completeness)->toBe(CostCompleteness::Partial)
        ->and($cacheWrite->missingUnits)->toBe(['cache_write_input_tokens'])
        ->and($exa->completeness)->toBe(CostCompleteness::Partial)
        ->and($exa->missingUnits)->toBe(['exa_search_requests'])
        ->and(packagePricingSource()->allowsFallback($identity))->toBeFalse();
});

it('leaves the generic and variant Gemini 3.1 Flash Lite identities unpriced by the snapshot', function (string $model): void {
    expect(packagePricingSource()->find(new ModelIdentity('openrouter', $model)))->toBeNull();
})->with([
    'google/gemini-3.1-flash-lite',
    'google/gemini-3.1-flash-lite-preview',
    'google/gemini-3.1-flash-lite:batch',
    'google/gemini-3.1-flash-lite-image',
]);

it('keeps every fallback-blocked identity unpriced unless it is an allowlisted bound basis, and never aliased', function (): void {
    $snapshot = packagePricingSnapshot();
    $blocked = $snapshot['fallback_blocked'] ?? [];

    // Rule: a fallback-blocked identity is either unpriced (a generic identity
    // that cannot select a billing tier, or a configured-only/unavailable SKU)
    // or a deliberately bound basis named on this allowlist. The bounded
    // OpenRouter -272k bases (since 0.2.0), the -standard-max worst-case
    // routing bases and the Gemini 3.1 Pro -standard-short/-long bases are
    // priced and fallback-blocked so that only this reviewed snapshot or an
    // explicit configured override can price them. The overlap must equal
    // this list exactly, in fallback_blocked order, so any new
    // priced-and-blocked identity fails until it is added here on purpose.
    $boundBasesAllowlist = [
        'openrouter:openai/gpt-5.6-terra-272k',
        'openrouter:openai/gpt-6-luna-272k',
        'openrouter:google/gemini-3.1-flash-lite-standard-max',
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
        'openrouter:google/gemini-3.6-flash-standard-max-2026',
        'openrouter:google/gemini-3.5-flash-lite-standard-max',
        'openrouter:google/gemini-3.1-pro-preview-standard-short',
        'openrouter:google/gemini-3.1-pro-preview-standard-long',
        'openrouter:google/gemini-3.1-pro-preview-customtools-standard-short',
        'openrouter:google/gemini-3.1-pro-preview-customtools-standard-long',
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
    ];

    expect($blocked)->not->toBeEmpty()
        ->and(array_values(array_intersect($blocked, array_keys($snapshot['prices']))))->toBe($boundBasesAllowlist);

    foreach ($snapshot['aliases'] ?? [] as $alias => $target) {
        expect(in_array($target, $blocked, true))->toBeFalse("Alias [{$alias}] targets the fallback-blocked identity [{$target}].");
    }
});

it('keeps unreviewed Jev versions and bare model names unpriced', function (string $model): void {
    expect(packagePricingSource()->find(new ModelIdentity('typesafe', $model)))->toBeNull();
})->with(['jev-1.14.0', 'jev-2.0.0', 'jev', 'jev-1.13']);

it('resolves an alias whose target is not priced to nothing instead of a guess', function (): void {
    $snapshot = packagePricingSnapshot();
    $snapshot['aliases'] = ['typesafe:jev-latest' => 'typesafe:jev-9.0.0'];

    expect((new PackagePricingSource($snapshot))->find(new ModelIdentity('typesafe', 'jev-latest')))->toBeNull();
});

/**
 * Read a snapshot basis as USD per million tokens, or USD per search for web_searches.
 *
 * @return array<string, BigDecimal>
 */
function basisPrices(string $key): array
{
    [$provider, $model] = explode(':', $key, 2);
    $definition = packagePricingSource()->find(new ModelIdentity($provider, $model));
    expect($definition)->not->toBeNull("[{$key}] is not priced.");
    assert($definition !== null);

    $prices = [];
    foreach ($definition->rates as $unit => $rate) {
        $prices[$unit] = $rate->cost(BigDecimal::of($unit === 'web_searches' ? 1 : 1_000_000))->amount;
    }

    return $prices;
}

/** @param array<string, string|BigDecimal> $expected */
function expectBasisPrices(string $key, array $expected): void
{
    $actual = basisPrices($key);
    $rates = packagePricingSnapshot()['prices'][$key]['rates'];

    expect(array_keys($actual))->toEqualCanonicalizing(array_keys($expected), "[{$key}] publishes other units.")
        ->and(packagePricingSnapshot()['prices'][$key]['notes'] ?? '')->toStartWith('Checked 2026-10-10.')
        ->and(packagePricingSnapshot()['prices'][$key]['source'])->toStartWith('https://');

    foreach ($expected as $unit => $price) {
        expect($rates[$unit]['amount'])->toBeString()
            ->and($rates[$unit]['per'])->toBeString()
            ->and($actual[$unit]->isEqualTo($price))->toBeTrue("[{$key}] {$unit}: expected {$price}, got {$actual[$unit]}.");
    }
}

/**
 * Scale every token rate of a basis, keeping per-search prices as published.
 *
 * @return array<string, BigDecimal>
 */
function scaledTokenPrices(string $key, string $factor): array
{
    $prices = basisPrices($key);
    unset($prices['web_searches']);

    return array_map(static fn (BigDecimal $price): BigDecimal => $price->multipliedBy($factor), $prices);
}

it('prices each native Claude basis at its list rate with 1.25x and 2x TTL cache writes', function (string $key, string $input, string $output, string $cacheRead): void {
    expectBasisPrices($key, [
        'input_tokens' => $input,
        'output_tokens' => $output,
        'cached_input_tokens' => $cacheRead,
        'cache_write_input_tokens_5m' => BigDecimal::of($input)->multipliedBy('1.25'),
        'cache_write_input_tokens_1h' => BigDecimal::of($input)->multipliedBy(2),
        'web_searches' => '0.01',
    ]);
})->with([
    ['anthropic:claude-fable-5-1-global', '10', '50', '0.25'],
    ['anthropic:claude-mythos-5-1-global', '10', '50', '0.25'],
    ['anthropic:claude-fable-5-global', '10', '50', '1'],
    ['anthropic:claude-mythos-5-global', '10', '50', '1'],
    ['anthropic:claude-opus-5-global-standard', '5', '25', '0.5'],
    ['anthropic:claude-opus-4-8-global-standard', '5', '25', '0.5'],
    ['anthropic:claude-opus-4-7-global', '5', '25', '0.5'],
    ['anthropic:claude-opus-4-6-global', '5', '25', '0.5'],
    ['anthropic:claude-opus-4-5-20251101', '5', '25', '0.5'],
    ['anthropic:claude-sonnet-5-global', '2', '10', '0.2'],
    ['anthropic:claude-sonnet-4-6-global', '3', '15', '0.3'],
    ['anthropic:claude-haiku-4-5-20251001', '1', '5', '0.1'],
]);

it('prices each OpenRouter Claude bound basis at 1.1x the global list rate', function (string $basis, string $native): void {
    expectBasisPrices($basis, [...scaledTokenPrices($native, '1.1'), 'web_searches' => '0.01']);

    expect(packagePricingSource()->allowsFallback(new ModelIdentity('openrouter', substr($basis, 11))))->toBeFalse();
})->with([
    ['openrouter:anthropic/claude-fable-5-standard-max', 'anthropic:claude-fable-5-global'],
    ['openrouter:anthropic/claude-opus-5.5-standard-max', 'anthropic:claude-opus-5-5-global-standard'],
    ['openrouter:anthropic/claude-opus-5-standard-max', 'anthropic:claude-opus-5-global-standard'],
    ['openrouter:anthropic/claude-opus-4.8-standard-max', 'anthropic:claude-opus-4-8-global-standard'],
    ['openrouter:anthropic/claude-opus-4.7-standard-max', 'anthropic:claude-opus-4-7-global'],
    ['openrouter:anthropic/claude-opus-4.6-standard-max', 'anthropic:claude-opus-4-6-global'],
    ['openrouter:anthropic/claude-opus-4.5-standard-max', 'anthropic:claude-opus-4-5-20251101'],
    ['openrouter:anthropic/claude-sonnet-5.5-standard-max', 'anthropic:claude-sonnet-5-5-global-standard'],
    ['openrouter:anthropic/claude-sonnet-5-standard-max', 'anthropic:claude-sonnet-5-global'],
    ['openrouter:anthropic/claude-sonnet-4.6-standard-max', 'anthropic:claude-sonnet-4-6-global'],
    ['openrouter:anthropic/claude-haiku-4.5-standard-max', 'anthropic:claude-haiku-4-5-20251001'],
    ['openrouter:anthropic/claude-haiku-5.5-standard-short-max', 'anthropic:claude-haiku-5-5-global-short'],
    ['openrouter:anthropic/claude-haiku-5.5-standard-long-max', 'anthropic:claude-haiku-5-5-global-long'],
]);

it('prices each native Gemini basis with 0.1x cache reads and per-query search only from Gemini 3', function (string $key, string $input, string $output, bool $search): void {
    $expected = [
        'input_tokens' => $input,
        'output_tokens' => $output,
        'cached_input_tokens' => BigDecimal::of($input)->multipliedBy('0.1'),
    ];

    if ($search) {
        $expected['web_searches'] = '0.014';
    }

    expectBasisPrices($key, $expected);
})->with([
    ['gemini:gemini-3.8-flash-standard-2026', '0.75', '3.75', true],
    ['gemini:gemini-3.8-flash-standard-2027', '1.50', '7.50', true],
    ['gemini:gemini-3.6-flash-standard-2026', '0.75', '3.75', true],
    ['gemini:gemini-3.6-flash-standard-2027', '1.50', '7.50', true],
    ['gemini:gemini-3.5-flash-lite-standard', '0.30', '2.50', true],
    ['gemini:gemini-3.1-flash-lite-standard', '0.25', '1.50', true],
    ['gemini:gemini-3.1-pro-preview-standard-short', '2', '12', true],
    ['gemini:gemini-3.1-pro-preview-standard-long', '4', '18', true],
    ['gemini:gemini-3.1-pro-preview-customtools-standard-short', '2', '12', true],
    ['gemini:gemini-3.1-pro-preview-customtools-standard-long', '4', '18', true],
    ['gemini:gemini-3-flash-preview-standard', '0.50', '3', true],
    ['gemini:gemini-2.5-pro-standard-short', '1.25', '10', false],
    ['gemini:gemini-2.5-pro-standard-long', '2.50', '15', false],
    ['gemini:gemini-2.5-flash-standard', '0.30', '2.50', false],
    ['gemini:gemini-2.5-flash-lite-standard', '0.10', '0.40', false],
]);

it('prices each OpenRouter Gemini bound basis from the direct rate and its routing uplift', function (string $basis, string $native, string $uplift): void {
    expectBasisPrices($basis, [...scaledTokenPrices($native, $uplift), 'web_searches' => '0.014']);
})->with([
    'regional Vertex 1.1x' => ['openrouter:google/gemini-3.6-flash-standard-max-2026', 'gemini:gemini-3.6-flash-standard-2026', '1.1'],
    'regional Vertex 1.1x lite' => ['openrouter:google/gemini-3.5-flash-lite-standard-max', 'gemini:gemini-3.5-flash-lite-standard', '1.1'],
    'global only, short' => ['openrouter:google/gemini-3.1-pro-preview-standard-short', 'gemini:gemini-3.1-pro-preview-standard-short', '1'],
    'global only, long' => ['openrouter:google/gemini-3.1-pro-preview-standard-long', 'gemini:gemini-3.1-pro-preview-standard-long', '1'],
    'customtools, short' => ['openrouter:google/gemini-3.1-pro-preview-customtools-standard-short', 'gemini:gemini-3.1-pro-preview-customtools-standard-short', '1'],
    'customtools, long' => ['openrouter:google/gemini-3.1-pro-preview-customtools-standard-long', 'gemini:gemini-3.1-pro-preview-customtools-standard-long', '1'],
]);

it('prices each native OpenAI basis from its input and output rates and published cache multipliers', function (string $key, string $input, string $output, ?string $cacheRead, ?string $cacheWrite, bool $search): void {
    $expected = ['input_tokens' => $input, 'output_tokens' => $output];

    if ($cacheRead !== null) {
        $expected['cached_input_tokens'] = BigDecimal::of($input)->multipliedBy($cacheRead);
    }

    // GPT-5.6 and later bill cache writes at 1.25x input; earlier models have
    // no write surcharge, so a written token bills once at the input rate.
    if ($cacheWrite !== null) {
        $expected['cache_write_input_tokens'] = BigDecimal::of($input)->multipliedBy($cacheWrite);
    }

    if ($search) {
        $expected['web_searches'] = '0.01';
    }

    expectBasisPrices($key, $expected);
})->with([
    ['openai:gpt-6-astra-standard-short', '10', '50', '0.1', '1.25', true],
    ['openai:gpt-6-astra-standard-long', '20', '75', '0.1', '1.25', true],
    ['openai:gpt-6-sol-standard-short', '2', '10', '0.1', '1.25', true],
    ['openai:gpt-6-sol-standard-long', '4', '15', '0.1', '1.25', true],
    ['openai:gpt-5.6-sol-standard-short', '4', '20', '0.1', '1.25', true],
    ['openai:gpt-5.6-sol-standard-long', '8', '30', '0.1', '1.25', true],
    ['openai:gpt-5.6-terra-standard-short', '2', '12', '0.1', '1.25', true],
    ['openai:gpt-5.6-terra-standard-long', '4', '18', '0.1', '1.25', true],
    ['openai:gpt-5.6-luna-standard-short', '0.20', '1.20', '0.1', '1.25', true],
    ['openai:gpt-5.6-luna-standard-long', '0.40', '1.80', '0.1', '1.25', true],
    ['openai:gpt-5.5-standard-short', '5', '30', '0.1', '1', true],
    ['openai:gpt-5.5-standard-long', '10', '45', '0.1', '1', true],
    ['openai:gpt-5.5-pro-standard-short', '30', '180', null, null, true],
    ['openai:gpt-5.5-pro-standard-long', '60', '270', null, null, true],
    ['openai:gpt-5.4-standard-short', '2.50', '15', '0.1', '1', true],
    ['openai:gpt-5.4-standard-long', '5', '22.50', '0.1', '1', true],
    ['openai:gpt-5.4-pro-standard-short', '30', '180', null, null, true],
    ['openai:gpt-5.4-pro-standard-long', '60', '270', null, null, true],
    ['openai:gpt-5.4-mini-standard', '0.75', '4.50', '0.1', '1', true],
    ['openai:gpt-5.2-standard', '1.75', '14', '0.1', '1', true],
    ['openai:gpt-5.2-pro-standard', '21', '168', null, null, true],
    ['openai:gpt-4.1-standard', '2', '8', '0.25', '1', false],
    ['openai:gpt-4.1-mini-standard', '0.40', '1.60', '0.25', '1', false],
    ['openai:gpt-4o-standard', '2.50', '10', '0.5', '1', false],
    ['openai:gpt-4o-mini-standard', '0.15', '0.60', '0.5', '1', false],
    ['openai:chat-latest-standard', '5', '30', '0.1', '1', false],
]);

it('prices each OpenRouter OpenAI bound basis at 1.1x the direct standard rate', function (string $basis, string $native, bool $cacheWrite, bool $search): void {
    $expected = scaledTokenPrices($native, '1.1');

    // OpenRouter bills pre-GPT-5.6 cache writes at no cost while OpenAI bills
    // them at the input rate, so those bases leave the write unpriced.
    if (! $cacheWrite) {
        unset($expected['cache_write_input_tokens']);
    }

    if ($search) {
        $expected['web_searches'] = '0.01';
    }

    expectBasisPrices($basis, $expected);
})->with(function (): array {
    $rows = [];

    foreach (['gpt-6.1-sol', 'gpt-6-astra', 'gpt-6-sol', 'gpt-6-luna', 'gpt-5.6-sol', 'gpt-5.6-terra', 'gpt-5.6-luna'] as $model) {
        foreach (['', '-pro'] as $variant) {
            foreach (['short', 'long'] as $tier) {
                $rows[] = ["openrouter:openai/{$model}{$variant}-standard-max-{$tier}", "openai:{$model}-standard-{$tier}", true, true];
            }
        }
    }

    foreach (['gpt-5.5', 'gpt-5.4'] as $model) {
        foreach (['short', 'long'] as $tier) {
            $rows[] = ["openrouter:openai/{$model}-standard-max-{$tier}", "openai:{$model}-standard-{$tier}", false, true];
        }
    }

    $rows[] = ['openrouter:openai/gpt-5.4-mini-standard-max', 'openai:gpt-5.4-mini-standard', false, true];
    $rows[] = ['openrouter:openai/gpt-4.1-standard-max', 'openai:gpt-4.1-standard', false, false];
    $rows[] = ['openrouter:openai/gpt-4.1-mini-standard-max', 'openai:gpt-4.1-mini-standard', false, false];
    $rows[] = ['openrouter:openai/gpt-4o-mini-standard-max', 'openai:gpt-4o-mini-standard', false, false];

    return $rows;
});

it('prices DeepSeek off-peak bases at half the peak rate', function (string $offPeak, string $peak): void {
    expectBasisPrices($offPeak, scaledTokenPrices($peak, '0.5'));
})->with([
    ['deepseek:deepseek-flash-off-peak', 'deepseek:deepseek-flash-peak'],
    ['deepseek:deepseek-v4-pro-off-peak', 'deepseek:deepseek-v4-pro-peak'],
]);

it('prices each direct DeepSeek, Z.ai and Kimi basis at its list rates', function (string $key, array $expected): void {
    expectBasisPrices($key, $expected);
})->with([
    ['deepseek:deepseek-flash-peak', ['input_tokens' => '0.3', 'cached_input_tokens' => '0.006', 'output_tokens' => '1.2']],
    ['zai:glm-5.3-flashx', ['input_tokens' => '0.37', 'cached_input_tokens' => '0.075', 'output_tokens' => '1.25']],
    ['zai:glm-5.2', ['input_tokens' => '1.4', 'cached_input_tokens' => '0.26', 'output_tokens' => '4.4']],
    ['zai:glm-5.1', ['input_tokens' => '1.4', 'cached_input_tokens' => '0.26', 'output_tokens' => '4.4']],
    ['zai:glm-5', ['input_tokens' => '1', 'cached_input_tokens' => '0.2', 'output_tokens' => '3.2']],
    ['zai:glm-4.7', ['input_tokens' => '0.6', 'cached_input_tokens' => '0.11', 'output_tokens' => '2.2']],
    ['zai:glm-4.6', ['input_tokens' => '0.6', 'cached_input_tokens' => '0.11', 'output_tokens' => '2.2']],
    ['zai:glm-4.5-air', ['input_tokens' => '0.2', 'cached_input_tokens' => '0.03', 'output_tokens' => '1.1']],
    ['moonshot:kimi-k3', ['input_tokens' => '3', 'cached_input_tokens' => '0.3', 'cache_write_input_tokens_5m' => '3', 'cache_write_input_tokens_1h' => '6', 'output_tokens' => '15']],
    ['moonshot:kimi-k2.7-code', ['input_tokens' => '0.95', 'cached_input_tokens' => '0.19', 'output_tokens' => '4']],
    ['moonshot:kimi-k2.7-code-highspeed', ['input_tokens' => '1.9', 'cached_input_tokens' => '0.38', 'output_tokens' => '8']],
    ['moonshot:kimi-k2.6', ['input_tokens' => '0.95', 'cached_input_tokens' => '0.16', 'output_tokens' => '4']],
]);

it('prices each Qwen basis with 20% implicit cache hits and 125% explicit cache creation', function (string $key, string $input, string $output): void {
    $expected = [
        'input_tokens' => $input,
        'output_tokens' => $output,
        'cache_write_input_tokens' => BigDecimal::of($input)->multipliedBy('1.25'),
    ];

    // Alibaba publishes the qwen3.8 cache-hit rate only in its console.
    if (! str_starts_with($key, 'dashscope:qwen3.8-')) {
        $expected['cached_input_tokens'] = BigDecimal::of($input)->multipliedBy('0.2');
    }

    expectBasisPrices($key, $expected);
})->with([
    ['dashscope:qwen3.8-max-intl', '2', '6'],
    ['dashscope:qwen3.8-flash-intl', '0.15', '0.47'],
    ['dashscope:qwen3.7-max-intl', '2.5', '7.5'],
    ['dashscope:qwen3.7-plus-intl-256k', '0.4', '1.6'],
    ['dashscope:qwen3.7-plus-intl-1m', '1.2', '4.8'],
    ['dashscope:qwen3.7-flash-intl-32k', '0.03', '0.13'],
    ['dashscope:qwen3.7-flash-intl-256k', '0.1', '0.4'],
    ['dashscope:qwen3.7-flash-intl-1m', '0.2', '0.8'],
    ['dashscope:qwen3-max-intl-32k', '1.2', '6'],
    ['dashscope:qwen3-max-intl-128k', '2.4', '12'],
    ['dashscope:qwen3-max-intl-256k', '3', '15'],
    ['dashscope:qwen-plus-intl-nonthinking-256k', '0.4', '1.2'],
    ['dashscope:qwen-plus-intl-thinking-256k', '0.4', '4'],
    ['dashscope:qwen-plus-intl-nonthinking-1m', '1.2', '3.6'],
    ['dashscope:qwen-plus-intl-thinking-1m', '1.2', '12'],
    ['dashscope:qwen-flash-intl-256k', '0.05', '0.4'],
    ['dashscope:qwen-flash-intl-1m', '0.25', '2'],
    ['dashscope:qwen3-coder-plus-intl-32k', '1', '5'],
    ['dashscope:qwen3-coder-plus-intl-128k', '1.8', '9'],
    ['dashscope:qwen3-coder-plus-intl-256k', '3', '15'],
    ['dashscope:qwen3-coder-plus-intl-1m', '6', '60'],
    ['dashscope:qwen3-coder-flash-intl-32k', '0.3', '1.5'],
    ['dashscope:qwen3-coder-flash-intl-128k', '0.5', '2.5'],
    ['dashscope:qwen3-coder-flash-intl-256k', '0.8', '4'],
    ['dashscope:qwen3-coder-flash-intl-1m', '1.6', '9.6'],
]);

it('prices each open-lab OpenRouter bound basis at its worst default-routed endpoint', function (string $model, string $input, string $cacheRead, ?string $cacheWrite, string $output): void {
    $expected = ['input_tokens' => $input, 'cached_input_tokens' => $cacheRead, 'output_tokens' => $output];

    if ($cacheWrite !== null) {
        $expected['cache_write_input_tokens'] = $cacheWrite;
    }

    expectBasisPrices("openrouter:{$model}-standard-max", $expected);

    expect(packagePricingSnapshot()['prices']["openrouter:{$model}-standard-max"]['notes'])->toContain('Worst-case default-routed endpoint, re-review on endpoint churn')
        ->and(packagePricingSource()->allowsFallback(new ModelIdentity('openrouter', $model)))->toBeFalse();
})->with([
    ['deepseek/deepseek-v4.1-flash', '0.45', '0.049', null, '1.8'],
    ['deepseek/deepseek-v4-pro-0813', '1.65', '0.219', null, '5'],
    ['deepseek/deepseek-v4-pro', '1.91', '0.33', null, '10.5'],
    ['deepseek/deepseek-v4-flash', '0.44', '0.07', null, '1.536'],
    ['deepseek/deepseek-v4-flash-0731', '0.44', '0.07', null, '1.536'],
    ['deepseek/deepseek-v3.2', '3', '0.5', null, '4.5'],
    ['qwen/qwen3.7-plus', '0.96', '0.192', '1.2', '3.84'],
    ['qwen/qwen3.7-max', '2', '0.4', '1.84375', '6'],
    ['qwen/qwen3.7-flash', '0.23', '0.046', '0.25', '0.92'],
    ['qwen/qwen3.8-2.4t-a95b', '2', '0.25', '2.5', '6'],
    ['qwen/qwen3.8-27b', '0.99', '0.99', '0.53125', '4.7'],
    ['qwen/qwen3-coder-flash', '0.52', '0.104', '0.65', '2.6'],
    ['qwen/qwen3-coder', '0.35', '0.1', null, '1.8'],
    ['qwen/qwen-plus', '0.78', '0.156', '0.975', '2.34'],
    ['moonshotai/kimi-k3', '4.5', '1.2', '3.75', '22.5'],
    ['moonshotai/kimi-k2.7-code', '1.9', '0.38', null, '8'],
    ['moonshotai/kimi-k2.6', '1.09', '0.37', null, '4.6'],
    ['z-ai/glm-5.3', '1.54', '0.26', null, '6'],
    ['z-ai/glm-5.3-flash', '0.225', '0.099', null, '1.6'],
    ['z-ai/glm-5.2', '1.54', '0.26', null, '10'],
    ['z-ai/glm-5.1', '1.4014', '0.6', null, '4.4044'],
    ['z-ai/glm-5', '1', '0.2', null, '3.2'],
    ['z-ai/glm-4.7', '0.7', '0.11', null, '2.5'],
    ['z-ai/glm-4.6', '0.6', '0.11', null, '2.2'],
    ['z-ai/glm-4.5-air', '0.2', '0.03', null, '1.1'],
]);

it('quotes one model per new family against a hand-calculated total', function (string $provider, string $model, array $usage, string $expected): void {
    $quote = AiPricing::quote($provider, $model, new Usage($usage));

    expect($quote->amount?->isEqualTo($expected))->toBeTrue("Expected {$expected}, got {$quote->amount}.")
        ->and($quote->completeness)->toBe(CostCompleteness::Complete)
        ->and($quote->source)->toBe(PricingSource::ProviderNative);
})->with([
    // 1,000 x (10 + 50 + 0.25 + 12.50 + 20)/M + 2 x 0.01 = 0.09275 + 0.02.
    'Claude Fable 5.1, every unit' => ['anthropic', 'claude-fable-5-1-global', [
        'input_tokens' => 1_000, 'output_tokens' => 1_000, 'cached_input_tokens' => 1_000,
        'cache_write_input_tokens_5m' => 1_000, 'cache_write_input_tokens_1h' => 1_000, 'web_searches' => 2,
    ], '0.11275'],
    // Alias to claude-haiku-4-5-20251001: 1M x 1/M + 1M x 5/M.
    'Claude Haiku 4.5 alias' => ['anthropic', 'claude-haiku-4-5', ['input_tokens' => 1_000_000, 'output_tokens' => 1_000_000], '6'],
    // 300,000 x 4/M + 10,000 x 0.40/M + 1,000 x 18/M + 2 x 0.014 = 1.2 + 0.004 + 0.018 + 0.028.
    'Gemini 3.1 Pro long prompt' => ['gemini', 'gemini-3.1-pro-preview-standard-long', [
        'input_tokens' => 300_000, 'cached_input_tokens' => 10_000, 'output_tokens' => 1_000, 'web_searches' => 2,
    ], '1.25'],
    // 400,000 x 8/M + 100,000 x 0.80/M + 50,000 x 10/M + 20,000 x 30/M + 3 x 0.01 = 3.2 + 0.08 + 0.5 + 0.6 + 0.03.
    'GPT-5.6 Sol long prompt' => ['openai', 'gpt-5.6-sol-standard-long', [
        'input_tokens' => 400_000, 'cached_input_tokens' => 100_000, 'cache_write_input_tokens' => 50_000,
        'output_tokens' => 20_000, 'web_searches' => 3,
    ], '4.41'],
    // 1M x 1.65/M + 200,000 x 0.219/M + 100,000 x 5/M = 1.65 + 0.0438 + 0.5.
    'DeepSeek V4 Pro on OpenRouter' => ['openrouter', 'deepseek/deepseek-v4-pro-0813-standard-max', [
        'input_tokens' => 1_000_000, 'cached_input_tokens' => 200_000, 'output_tokens' => 100_000,
    ], '2.1938'],
    // 500,000 x 1.2/M + 100,000 x 0.24/M + 20,000 x 1.5/M + 10,000 x 4.8/M = 0.6 + 0.024 + 0.03 + 0.048.
    'Qwen 3.7 Plus 1M tier' => ['dashscope', 'qwen3.7-plus-intl-1m', [
        'input_tokens' => 500_000, 'cached_input_tokens' => 100_000, 'cache_write_input_tokens' => 20_000, 'output_tokens' => 10_000,
    ], '0.702'],
    // 1M x 1.4/M + 1M x 0.26/M + 1M x 4.4/M.
    'GLM 5.2 direct' => ['zai', 'glm-5.2', ['input_tokens' => 1_000_000, 'cached_input_tokens' => 1_000_000, 'output_tokens' => 1_000_000], '6.06'],
    // 1M x 3/M + 100,000 x 6/M + 100,000 x 15/M = 3 + 0.6 + 1.5.
    'Kimi K3 1-hour cache write' => ['moonshot', 'kimi-k3', ['input_tokens' => 1_000_000, 'cache_write_input_tokens_1h' => 100_000, 'output_tokens' => 100_000], '5.1'],
]);
