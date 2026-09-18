<?php

declare(strict_types=1);

use Brick\Math\RoundingMode;
use Jkudish\LaravelAiPricing\CostCalculator;
use Jkudish\LaravelAiPricing\Enums\CostCompleteness;
use Jkudish\LaravelAiPricing\Enums\PricingSource;
use Jkudish\LaravelAiPricing\Enums\RoundingBoundary;
use Jkudish\LaravelAiPricing\ValueObjects\ModelIdentity;
use Jkudish\LaravelAiPricing\ValueObjects\Money;
use Jkudish\LaravelAiPricing\ValueObjects\PriceDefinition;
use Jkudish\LaravelAiPricing\ValueObjects\PricingSnapshot;
use Jkudish\LaravelAiPricing\ValueObjects\Rate;
use Jkudish\LaravelAiPricing\ValueObjects\Usage;

it('calculates costs with decimal arithmetic and rounds only at an explicit boundary', function (): void {
    $pricing = new PriceDefinition(
        new ModelIdentity('openrouter', 'vendor/model'),
        [
            'input_tokens' => new Rate('input_tokens', '0.1234567', '1000000'),
            'output_tokens' => new Rate('output_tokens', '0.7654321', '1000000'),
        ],
        PricingSource::Configured,
    );

    $quote = (new CostCalculator)->calculate(Usage::tokens(987_654, 123_456), $pricing);

    expect((string) $quote->cost?->amount)->toBe('0.2164296889194')
        ->and((string) $quote->cost?->at(RoundingBoundary::Display, RoundingMode::HalfUp)->amount)->toBe('0.216430')
        ->and($quote->completeness)->toBe(CostCompleteness::Complete)
        ->and($quote->snapshot?->fingerprint)->toHaveLength(64);
});

it('labels a quote partial when a non-zero usage unit has no rate', function (): void {
    $pricing = new PriceDefinition(
        new ModelIdentity('openai', 'gpt'),
        ['input_tokens' => new Rate('input_tokens', '1', '1000000')],
        PricingSource::Configured,
    );

    $quote = (new CostCalculator)->calculate(new Usage(['input_tokens' => 100, 'images' => 2]), $pricing);

    expect($quote->completeness)->toBe(CostCompleteness::Partial)
        ->and($quote->missingUnits)->toBe(['images'])
        ->and((string) $quote->cost?->amount)->toBe('0.0001');
});

it('labels a quote unavailable when no used unit has a rate', function (): void {
    $pricing = new PriceDefinition(
        new ModelIdentity('openai', 'gpt'),
        ['input_tokens' => new Rate('input_tokens', '1', '1000000')],
        PricingSource::Configured,
    );

    $quote = (new CostCalculator)->calculate(new Usage(['images' => 1]), $pricing);

    expect($quote->completeness)->toBe(CostCompleteness::Unavailable)
        ->and($quote->cost)->toBeNull();
});

it('rejects mixed currencies when constructing a price definition', function (): void {
    expect(fn () => new PriceDefinition(
        new ModelIdentity('provider', 'model'),
        [
            'input_tokens' => new Rate('input_tokens', '1', 1, 'USD'),
            'output_tokens' => new Rate('output_tokens', '1', 1, 'CAD'),
        ],
        PricingSource::Configured,
    ))->toThrow(InvalidArgumentException::class, 'mixed currencies');
});

it('rejects invalid money usage and rate values', function (): void {
    expect(fn () => new Money('-1'))->toThrow(InvalidArgumentException::class)
        ->and(fn () => new Money('1', 'US'))->toThrow(InvalidArgumentException::class)
        ->and(fn () => (new Money('1'))->rounded(-1))->toThrow(InvalidArgumentException::class, 'Money scale cannot be negative.')
        ->and(fn () => new Usage(['tokens' => -1]))->toThrow(InvalidArgumentException::class)
        ->and(fn () => new Rate('tokens', 1, 0))->toThrow(InvalidArgumentException::class);
});

it('keeps pricing fingerprints stable across a jsonb key-order round trip', function (): void {
    $definition = new PriceDefinition(
        new ModelIdentity('openrouter', 'openai/gpt-5.6-terra-272k'),
        [
            'input_tokens' => new Rate('input_tokens', '4', '1000000'),
            'output_tokens' => new Rate('output_tokens', '18', '1000000'),
            'cached_input_tokens' => new Rate('cached_input_tokens', '0.4', '1000000'),
            'cache_write_input_tokens' => new Rate('cache_write_input_tokens', '5', '1000000'),
            'exa_search_requests' => new Rate('exa_search_requests', '0.007', '1'),
        ],
        PricingSource::ProviderNative,
    );
    $snapshot = new PricingSnapshot($definition);

    // PostgreSQL jsonb normalizes object key order; decode with ksort on every
    // nested object to model that storage round trip before re-fingerprinting.
    $encoded = json_encode($snapshot->toArray()['definition'], JSON_THROW_ON_ERROR);
    assert(is_string($encoded));
    $decoded = json_decode($encoded, true, flags: JSON_THROW_ON_ERROR);
    assert(is_array($decoded));
    $reordered = json_decode(json_encode($decoded, JSON_THROW_ON_ERROR | JSON_FORCE_OBJECT), true, flags: JSON_THROW_ON_ERROR);
    assert(is_array($reordered));
    ksort($reordered);
    foreach ($reordered as $key => $value) {
        if (is_array($value)) {
            ksort($value);
            $reordered[$key] = $value;
        }
    }
    $roundTripped = new PricingSnapshot(new PriceDefinition(
        new ModelIdentity('openrouter', 'openai/gpt-5.6-terra-272k'),
        array_map(
            static fn (array $rate): Rate => new Rate($rate['unit'], $rate['amount'], $rate['per'], $rate['currency']),
            $reordered['rates'],
        ),
        PricingSource::ProviderNative,
    ));

    expect($roundTripped->fingerprint)->toBe($snapshot->fingerprint);
});
