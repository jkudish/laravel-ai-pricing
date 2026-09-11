<?php

declare(strict_types=1);

use Jkudish\LaravelAiPricing\Contracts\PricingCatalog;
use Jkudish\LaravelAiPricing\Enums\CostCompleteness;
use Jkudish\LaravelAiPricing\Enums\PricingSource;
use Jkudish\LaravelAiPricing\PricingResolver;
use Jkudish\LaravelAiPricing\Sources\PackagePricingSource;
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

function packageFallbackPolicy(): PackagePricingSource
{
    /** @var array{version: int, retrieved_at: string, effective_at: string|null, currency: string, fallback_blocked?: list<string>, prices: array<string, array{source: string, notes?: string, rates: array<string, array{amount: string|int, per: string|int}>}>} $snapshot */
    $snapshot = require __DIR__.'/../../resources/pricing/provider-skus.php';

    return new PackagePricingSource($snapshot);
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

it('uses a remote native catalog before a package snapshot and the snapshot before fallback', function (bool $hasNative, string $expectedAmount): void {
    $identity = new ModelIdentity('provider', 'sku');
    $resolver = new PricingResolver(
        configured: catalog(null),
        native: catalog($hasNative ? price($identity, PricingSource::ProviderNative, '3') : null),
        fallback: catalog(price($identity, PricingSource::Portkey, '1')),
        snapshot: catalog(price($identity, PricingSource::ProviderNative, '2')),
    );

    $quote = $resolver->resolve(new PricingObservation($identity, Usage::tokens(1, 0)));

    expect((string) $quote->cost?->amount)->toBe($expectedAmount);
})->with([
    'remote native first' => [true, '3'],
    'snapshot before fallback' => [false, '2'],
]);

it('uses the conservative xAI snapshot instead of incompatible fallback token rates', function (): void {
    $identity = new ModelIdentity('xai', 'grok-4.6');
    $snapshot = packageFallbackPolicy();
    $resolver = new PricingResolver(
        configured: catalog(null),
        native: catalog(null),
        fallback: catalog(price($identity, PricingSource::Portkey, '0.001')),
        snapshot: $snapshot,
    );

    $tokenOnly = $resolver->resolve(new PricingObservation($identity, Usage::tokens(1, 0)));
    $withSearch = $resolver->resolve(new PricingObservation(
        $identity,
        new Usage(['input_tokens' => 1, 'searches' => 2]),
    ));

    expect($tokenOnly->cost)->toBeNull()
        ->and($tokenOnly->completeness)->toBe(CostCompleteness::Unavailable)
        ->and((string) $withSearch->cost?->amount)->toBe('0.01')
        ->and($withSearch->completeness)->toBe(CostCompleteness::Partial)
        ->and($withSearch->missingUnits)->toBe(['input_tokens'])
        ->and($withSearch->source)->toBe(PricingSource::ProviderNative);
});

it('blocks fallback pricing only for identities declared unavailable without a package rate', function (): void {
    $blocked = new ModelIdentity('tavily', 'search');
    $unblocked = new ModelIdentity('other', 'search');
    $resolver = new PricingResolver(
        configured: catalog(null),
        native: catalog(null),
        fallback: catalog(price($unblocked, PricingSource::Portkey, '4')),
        snapshot: packageFallbackPolicy(),
    );

    $blockedQuote = $resolver->resolve(new PricingObservation($blocked, Usage::tokens(1, 0)));
    $unblockedQuote = $resolver->resolve(new PricingObservation($unblocked, Usage::tokens(1, 0)));

    expect($blockedQuote->completeness)->toBe(CostCompleteness::Unavailable)
        ->and($blockedQuote->cost)->toBeNull()
        ->and($unblockedQuote->completeness)->toBe(CostCompleteness::Complete)
        ->and($unblockedQuote->source)->toBe(PricingSource::Portkey)
        ->and((string) $unblockedQuote->cost?->amount)->toBe('4');
});

it('uses explicit configured pricing for an identity whose remote fallback is blocked', function (): void {
    $identity = new ModelIdentity('parallel', 'search');
    $resolver = new PricingResolver(
        configured: catalog(price($identity, PricingSource::Configured, '2')),
        native: catalog(null),
        fallback: catalog(price($identity, PricingSource::Portkey, '4')),
        snapshot: packageFallbackPolicy(),
    );

    $quote = $resolver->resolve(new PricingObservation($identity, Usage::tokens(1, 0)));

    expect($quote->completeness)->toBe(CostCompleteness::Complete)
        ->and($quote->source)->toBe(PricingSource::Configured)
        ->and((string) $quote->cost?->amount)->toBe('2');
});

it('uses provider reported cost for an identity whose remote fallback is blocked', function (): void {
    $identity = new ModelIdentity('firecrawl', 'search');
    $resolver = new PricingResolver(
        configured: catalog(price($identity, PricingSource::Configured, '2')),
        native: catalog(null),
        fallback: catalog(price($identity, PricingSource::Portkey, '4')),
        snapshot: packageFallbackPolicy(),
    );

    $quote = $resolver->resolve(new PricingObservation(
        $identity,
        Usage::tokens(1, 0),
        providerReportedCost: new Money('0.42'),
    ));

    expect($quote->completeness)->toBe(CostCompleteness::Complete)
        ->and($quote->source)->toBe(PricingSource::ProviderReported)
        ->and((string) $quote->cost?->amount)->toBe('0.42');
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
