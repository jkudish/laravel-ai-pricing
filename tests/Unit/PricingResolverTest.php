<?php

declare(strict_types=1);

use Jkudish\LaravelAiPricing\Contracts\PricingCatalog;
use Jkudish\LaravelAiPricing\Enums\CostCompleteness;
use Jkudish\LaravelAiPricing\Enums\PricingSource;
use Jkudish\LaravelAiPricing\PricingResolver;
use Jkudish\LaravelAiPricing\ValueObjects\ModelIdentity;
use Jkudish\LaravelAiPricing\ValueObjects\Money;
use Jkudish\LaravelAiPricing\ValueObjects\PriceDefinition;
use Jkudish\LaravelAiPricing\ValueObjects\PricingObservation;
use Jkudish\LaravelAiPricing\ValueObjects\Rate;
use Jkudish\LaravelAiPricing\ValueObjects\Usage;

function catalog(?PriceDefinition $definition): PricingCatalog
{
    return new class($definition) implements PricingCatalog
    {
        public function __construct(private readonly ?PriceDefinition $definition) {}

        public function find(ModelIdentity $identity): ?PriceDefinition
        {
            return $this->definition;
        }

        public function sync(): int
        {
            return $this->definition === null ? 0 : 1;
        }
    };
}

function price(ModelIdentity $identity, PricingSource $source, string $amount): PriceDefinition
{
    return new PriceDefinition($identity, ['input_tokens' => new Rate('input_tokens', $amount)], $source);
}

it('uses provider reported cost before every catalog', function (): void {
    $identity = new ModelIdentity('provider', 'model');
    $resolver = new PricingResolver(
        catalog(price($identity, PricingSource::Configured, '9')),
        catalog(price($identity, PricingSource::ProviderNative, '8')),
        catalog(price($identity, PricingSource::Portkey, '7')),
    );

    $quote = $resolver->resolve(new PricingObservation($identity, Usage::tokens(1, 0), new Money('0.42')));

    expect($quote->source)->toBe(PricingSource::ProviderReported)
        ->and((string) $quote->cost?->amount)->toBe('0.42');
});

it('resolves configured native observation remote native then fallback in order', function (string $expected, ?PriceDefinition $configured, ?PriceDefinition $observationNative, ?PriceDefinition $native, ?PriceDefinition $fallback): void {
    $identity = new ModelIdentity('provider', 'model');
    $resolver = new PricingResolver(catalog($configured), catalog($native), catalog($fallback));
    $quote = $resolver->resolve(new PricingObservation($identity, Usage::tokens(1, 0), providerNativePricing: $observationNative));

    expect($quote->source->value)->toBe($expected);
})->with(function (): array {
    $identity = new ModelIdentity('provider', 'model');

    return [
        'configured first' => [PricingSource::Configured->value, price($identity, PricingSource::Configured, '1'), price($identity, PricingSource::ProviderNative, '2'), price($identity, PricingSource::ProviderNative, '3'), price($identity, PricingSource::Portkey, '4')],
        'observation native before remote' => [PricingSource::ProviderNative->value, null, price($identity, PricingSource::ProviderNative, '2'), price($identity, PricingSource::ProviderNative, '3'), price($identity, PricingSource::Portkey, '4')],
        'remote native before fallback' => [PricingSource::ProviderNative->value, null, null, price($identity, PricingSource::ProviderNative, '3'), price($identity, PricingSource::Portkey, '4')],
        'fallback last' => [PricingSource::Portkey->value, null, null, null, price($identity, PricingSource::Portkey, '4')],
    ];
});

it('returns unavailable without throwing when prices are missing', function (): void {
    $identity = new ModelIdentity('provider', 'unknown');
    $quote = (new PricingResolver(catalog(null), catalog(null), catalog(null)))
        ->resolve(new PricingObservation($identity, Usage::tokens(10, 20)));

    expect($quote->completeness)->toBe(CostCompleteness::Unavailable)
        ->and($quote->cost)->toBeNull();
});

it('does not attribute a provider reported cost in another currency', function (): void {
    $identity = new ModelIdentity('provider', 'model');
    $quote = (new PricingResolver(catalog(null), catalog(null), catalog(null)))
        ->resolve(new PricingObservation($identity, Usage::tokens(1, 1), new Money('1', 'CAD')));

    expect($quote->completeness)->toBe(CostCompleteness::Unavailable);
});

it('does not attribute catalog pricing in another currency', function (PricingSource $source): void {
    $identity = new ModelIdentity('provider', 'model');
    $foreign = new PriceDefinition($identity, ['input_tokens' => new Rate('input_tokens', '1', 1, 'CAD')], $source);
    $resolver = new PricingResolver(
        catalog($source === PricingSource::Configured ? $foreign : null),
        catalog($source === PricingSource::ProviderNative ? $foreign : null),
        catalog($source === PricingSource::Portkey ? $foreign : null),
    );

    expect($resolver->resolve(new PricingObservation($identity, Usage::tokens(1, 0)))->completeness)
        ->toBe(CostCompleteness::Unavailable);
})->with([PricingSource::Configured, PricingSource::ProviderNative, PricingSource::Portkey]);
