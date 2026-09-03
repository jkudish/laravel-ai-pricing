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
    /** @var array{version: int, retrieved_at: string, effective_at: string|null, currency: string, prices: array<string, array{source: string, notes?: string, rates: array<string, array{amount: string|int, per: string|int}>}>} $snapshot */
    $snapshot = require __DIR__.'/../../resources/pricing/provider-skus.php';

    return new PackagePricingSource($snapshot);
}

it('provides reviewed package pricing for stable provider SKUs', function (string $provider, string $sku, string $unit, string $amount, string $per): void {
    $definition = packagePricingSource()->find(new ModelIdentity($provider, $sku));

    expect($definition)->not->toBeNull()
        ->and($definition?->source)->toBe(PricingSource::ProviderNative)
        ->and($definition?->identity->toArray())->toBe(['provider' => $provider, 'model' => $sku])
        ->and((string) $definition?->rates[$unit]->amount)->toBe($amount)
        ->and((string) $definition?->rates[$unit]->per)->toBe($per)
        ->and($definition?->retrievedAt?->format('Y-m-d'))->toBe('2026-09-03')
        ->and($definition?->effectiveAt)->toBeNull()
        ->and($definition?->sourceReference)->toStartWith('https://');
})->with([
    'Brave Answers' => ['brave', 'answers', 'queries', '4', '1000'],
    'Exa Search' => ['exa', 'search', 'additional_results', '1', '1000'],
    'Kagi FastGPT' => ['kagi', 'fastgpt', 'uncached_queries', '15', '1000'],
    'You Answer' => ['you', 'answer', 'requests', '5', '1000'],
    'You Research Lite' => ['you', 'research-lite', 'requests', '12', '1000'],
    'You Research Standard' => ['you', 'research-standard', 'requests', '50', '1000'],
    'You Research Deep' => ['you', 'research-deep', 'requests', '100', '1000'],
    'You Research Exhaustive' => ['you', 'research-exhaustive', 'requests', '450', '1000'],
    'Perplexity Agent Low' => ['perplexity', 'agent-low', 'web_searches', '0.0025', '1'],
    'Perplexity Agent High' => ['perplexity', 'agent-high', 'sandbox_sessions', '0.03', '1'],
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

it('keeps frontier research and unknown SKUs unavailable', function (string $provider, string $sku): void {
    expect(packagePricingSource()->find(new ModelIdentity($provider, $sku)))->toBeNull();
})->with([
    ['you', 'research-frontier'],
    ['perplexity', 'agent-auto'],
    ['searchapi', 'search'],
    ['searchapi', 'google'],
]);
