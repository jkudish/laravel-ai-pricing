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
        ->and(fn () => new Usage(['tokens' => -1]))->toThrow(InvalidArgumentException::class)
        ->and(fn () => new Rate('tokens', 1, 0))->toThrow(InvalidArgumentException::class);
});
