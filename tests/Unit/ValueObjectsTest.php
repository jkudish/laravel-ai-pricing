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
            // Constructed in non-jsonb order to prove the rates map is the
            // only ordering that survives the round trip through storage.
            'cache_write_input_tokens' => new Rate('cache_write_input_tokens', '5', '1000000'),
            'exa_search_requests' => new Rate('exa_search_requests', '0.007', '1'),
            'output_tokens' => new Rate('output_tokens', '18', '1000000'),
            'cached_input_tokens' => new Rate('cached_input_tokens', '0.4', '1000000'),
            'input_tokens' => new Rate('input_tokens', '4', '1000000'),
        ],
        PricingSource::ProviderNative,
    );
    $snapshot = new PricingSnapshot($definition);

    // Model the exact jsonb round trip: PostgreSQL jsonb re-sorts every
    // object key by key length first, then bytewise, and the replay path
    // (CollectionSettlement::snapshot) rebuilds the value objects from that
    // normalized payload, which re-emits every key order except the rates
    // map deterministically.
    $jsonbNormalize = static function (array $value) use (&$jsonbNormalize): array {
        uksort($value, static fn (string $a, string $b): int => [strlen($a), $a] <=> [strlen($b), $b]);

        return array_map(
            static fn (mixed $item): mixed => is_array($item) ? $jsonbNormalize($item) : $item,
            $value,
        );
    };
    $stored = $jsonbNormalize($definition->toArray());
    assert(is_string($stored['source']));
    $roundTripped = new PricingSnapshot(new PriceDefinition(
        new ModelIdentity($stored['identity']['provider'], $stored['identity']['model']),
        array_map(
            static fn (array $rate): Rate => new Rate($rate['unit'], $rate['amount'], $rate['per'], $rate['currency']),
            $stored['rates'],
        ),
        PricingSource::from($stored['source']),
    ));

    expect($roundTripped->fingerprint)->toBe($snapshot->fingerprint);
});

it('reproduces legacy fingerprints for snapshots persisted before jsonb canonicalization', function (): void {
    // A pre-change snapshot of a definition whose rates map was already in
    // jsonb key order was fingerprinted over the plain toArray encoding.
    // Canonicalizing only the rates map keeps that fingerprint byte-identical
    // so already-persisted settlements still validate on replay.
    $definition = new PriceDefinition(
        new ModelIdentity('anthropic', 'claude-3-5-haiku-latest'),
        [
            'input_tokens' => new Rate('input_tokens', '0.8', '1000000'),
            'output_tokens' => new Rate('output_tokens', '4', '1000000'),
        ],
        PricingSource::ProviderNative,
    );

    $legacyEncoded = json_encode($definition->toArray(), JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);
    assert(is_string($legacyEncoded));
    $legacyFingerprint = hash('sha256', $legacyEncoded);

    expect((new PricingSnapshot($definition))->fingerprint)->toBe($legacyFingerprint);
});

it('normalizes the rates map using jsonb key ordering rather than plain bytewise ordering', function (): void {
    // Bytewise sorting places cache_write_input_tokens before
    // cached_input_tokens ('_' < 'd'); jsonb orders by key length first and
    // does the opposite. The fingerprint must follow jsonb.
    $identity = ['provider' => 'openrouter', 'model' => 'openai/gpt-5.6-terra-272k'];
    $rate = static fn (string $unit, string $amount): array => [
        'unit' => $unit, 'amount' => $amount, 'per' => '1000000', 'currency' => 'USD',
    ];
    $definition = new PriceDefinition(
        new ModelIdentity('openrouter', 'openai/gpt-5.6-terra-272k'),
        [
            'cache_write_input_tokens' => new Rate('cache_write_input_tokens', '5', '1000000'),
            'cached_input_tokens' => new Rate('cached_input_tokens', '0.4', '1000000'),
        ],
        PricingSource::ProviderNative,
    );

    $encode = static function (array $rates) use ($identity): string {
        return json_encode([
            'identity' => $identity,
            'rates' => $rates,
            'source' => 'provider_native',
            'currency' => 'USD',
            'effective_at' => null,
            'retrieved_at' => null,
            'source_reference' => null,
        ], JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);
    };
    $jsonbOrdered = $encode([
        'cached_input_tokens' => $rate('cached_input_tokens', '0.4'),
        'cache_write_input_tokens' => $rate('cache_write_input_tokens', '5'),
    ]);
    $bytewiseOrdered = $encode([
        'cache_write_input_tokens' => $rate('cache_write_input_tokens', '5'),
        'cached_input_tokens' => $rate('cached_input_tokens', '0.4'),
    ]);
    $fingerprint = (new PricingSnapshot($definition))->fingerprint;

    expect($fingerprint)->toBe(hash('sha256', $jsonbOrdered))
        ->not->toBe(hash('sha256', $bytewiseOrdered));
});

it('rehydrates persisted snapshots with their stored identity and verifying algorithm', function (): void {
    $definition = new PriceDefinition(
        new ModelIdentity('openrouter', 'openai/gpt-5.6-terra-272k'),
        [
            'cache_write_input_tokens' => new Rate('cache_write_input_tokens', '5', '1000000'),
            'exa_search_requests' => new Rate('exa_search_requests', '0.007', '1'),
            'output_tokens' => new Rate('output_tokens', '18', '1000000'),
            'cached_input_tokens' => new Rate('cached_input_tokens', '0.4', '1000000'),
            'input_tokens' => new Rate('input_tokens', '4', '1000000'),
        ],
        PricingSource::ProviderNative,
    );
    $snapshot = new PricingSnapshot($definition);

    $rehydrated = PricingSnapshot::fromPersisted($snapshot->toArray(), $definition);

    expect($rehydrated->fingerprint)->toBe($snapshot->fingerprint)
        ->and($rehydrated->algorithm)->toBe(PricingSnapshot::ALGORITHM_RATES_JSONB_V1)
        ->and($rehydrated->toArray())->toBe($snapshot->toArray());
});

it('replays recursive-ksort era fingerprints through a jsonb storage round trip', function (): void {
    // The first wave-internal revision fingerprinted the whole definition
    // under recursive ksort. Rates stored out of ksort order — as jsonb
    // stores exa_search_requests before cache_write_input_tokens — must
    // still verify, because that algorithm is content-addressed and
    // independent of storage order. The rehydrated snapshot keeps the
    // stored fingerprint instead of adopting the current one.
    $definition = new PriceDefinition(
        new ModelIdentity('openrouter', 'openai/gpt-5.6-terra-272k'),
        [
            'input_tokens' => new Rate('input_tokens', '4', '1000000'),
            'output_tokens' => new Rate('output_tokens', '18', '1000000'),
            'cached_input_tokens' => new Rate('cached_input_tokens', '0.4', '1000000'),
            'exa_search_requests' => new Rate('exa_search_requests', '0.007', '1'),
            'cache_write_input_tokens' => new Rate('cache_write_input_tokens', '5', '1000000'),
        ],
        PricingSource::ProviderNative,
    );

    $recursiveKsort = static function (array $value) use (&$recursiveKsort): array {
        ksort($value);

        return array_map(
            static fn (mixed $item): mixed => is_array($item) ? $recursiveKsort($item) : $item,
            $value,
        );
    };
    $legacyEncoded = json_encode($recursiveKsort($definition->toArray()), JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);
    assert(is_string($legacyEncoded));
    $storedFingerprint = hash('sha256', $legacyEncoded);

    // Rebuild the definition the way a jsonb replay does: every object key
    // in jsonb order, which differs from the insertion order above.
    $jsonbNormalize = static function (array $value) use (&$jsonbNormalize): array {
        uksort($value, static fn (string $a, string $b): int => [strlen($a), $a] <=> [strlen($b), $b]);

        return array_map(
            static fn (mixed $item): mixed => is_array($item) ? $jsonbNormalize($item) : $item,
            $value,
        );
    };
    $stored = $jsonbNormalize($definition->toArray());
    assert(is_string($stored['source']));
    $rebuilt = new PriceDefinition(
        new ModelIdentity($stored['identity']['provider'], $stored['identity']['model']),
        array_map(
            static fn (array $rate): Rate => new Rate($rate['unit'], $rate['amount'], $rate['per'], $rate['currency']),
            $stored['rates'],
        ),
        PricingSource::from($stored['source']),
    );

    $rehydrated = PricingSnapshot::fromPersisted(['fingerprint' => $storedFingerprint, 'definition' => $stored], $rebuilt);

    expect($rehydrated->fingerprint)->toBe($storedFingerprint)
        ->and($rehydrated->fingerprint)->not->toBe((new PricingSnapshot($rebuilt))->fingerprint)
        ->and($rehydrated->algorithm)->toBe(PricingSnapshot::ALGORITHM_RECURSIVE_KSORT_V0);
});

it('replays plain-era fingerprints for definitions written in jsonb key order', function (): void {
    // The plain-era writer fingerprinted its in-memory definition directly.
    // When the catalog's insertion order was already jsonb key order, the
    // jsonb storage round trip preserves that encoding byte-for-byte and
    // the stored fingerprint still verifies, rehydrating as the plain
    // algorithm with its stored identity.
    $definition = new PriceDefinition(
        new ModelIdentity('openrouter', 'openai/gpt-5.6-terra-272k'),
        [
            'input_tokens' => new Rate('input_tokens', '4', '1000000'),
            'output_tokens' => new Rate('output_tokens', '18', '1000000'),
            'cached_input_tokens' => new Rate('cached_input_tokens', '0.4', '1000000'),
            'exa_search_requests' => new Rate('exa_search_requests', '0.007', '1'),
            'cache_write_input_tokens' => new Rate('cache_write_input_tokens', '5', '1000000'),
        ],
        PricingSource::ProviderNative,
    );
    $plainEncoded = json_encode($definition->toArray(), JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);
    assert(is_string($plainEncoded));
    $storedFingerprint = hash('sha256', $plainEncoded);

    // The reader rebuilds from jsonb, which keeps this rates order intact.
    $jsonbNormalize = static function (array $value) use (&$jsonbNormalize): array {
        uksort($value, static fn (string $a, string $b): int => [strlen($a), $a] <=> [strlen($b), $b]);

        return array_map(
            static fn (mixed $item): mixed => is_array($item) ? $jsonbNormalize($item) : $item,
            $value,
        );
    };
    $stored = $jsonbNormalize($definition->toArray());
    assert(is_string($stored['source']));
    $rebuilt = new PriceDefinition(
        new ModelIdentity($stored['identity']['provider'], $stored['identity']['model']),
        array_map(
            static fn (array $rate): Rate => new Rate($rate['unit'], $rate['amount'], $rate['per'], $rate['currency']),
            $stored['rates'],
        ),
        PricingSource::from($stored['source']),
    );

    $rehydrated = PricingSnapshot::fromPersisted(['fingerprint' => $storedFingerprint, 'definition' => $stored], $rebuilt);

    // For a definition already written in jsonb key order, the plain and
    // current encodings are byte-identical, so the probe order reports the
    // current label while preserving the stored fingerprint exactly: the
    // snapshot verifies either way and no identity is recomputed.
    expect($rehydrated->fingerprint)->toBe($storedFingerprint)
        ->and($rehydrated->algorithm)->toBe(PricingSnapshot::ALGORITHM_RATES_JSONB_V1)
        ->and((new PricingSnapshot($rebuilt))->fingerprint)->toBe($storedFingerprint);
});

it('fails closed when plain-era rates were written out of jsonb key order', function (): void {
    // A plain-era definition whose rates were inserted out of jsonb order
    // fingerprinted that insertion order. jsonb storage destroys it, so the
    // stored fingerprint no longer matches any recomputable encoding and
    // replay rejects rather than forgiving: this is the documented
    // compatibility boundary of the plain algorithm, not a pass.
    $written = new PriceDefinition(
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
    $plainEncoded = json_encode($written->toArray(), JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);
    assert(is_string($plainEncoded));
    $storedFingerprint = hash('sha256', $plainEncoded);

    $jsonbNormalize = static function (array $value) use (&$jsonbNormalize): array {
        uksort($value, static fn (string $a, string $b): int => [strlen($a), $a] <=> [strlen($b), $b]);

        return array_map(
            static fn (mixed $item): mixed => is_array($item) ? $jsonbNormalize($item) : $item,
            $value,
        );
    };
    $stored = $jsonbNormalize($written->toArray());
    assert(is_string($stored['source']));
    $rebuilt = new PriceDefinition(
        new ModelIdentity($stored['identity']['provider'], $stored['identity']['model']),
        array_map(
            static fn (array $rate): Rate => new Rate($rate['unit'], $rate['amount'], $rate['per'], $rate['currency']),
            $stored['rates'],
        ),
        PricingSource::from($stored['source']),
    );

    PricingSnapshot::fromPersisted(['fingerprint' => $storedFingerprint, 'definition' => $stored], $rebuilt);
})->throws(InvalidArgumentException::class);

it('fails closed on tampered, unknown, and mismatched persisted identities', function (string $kind): void {
    $definition = new PriceDefinition(
        new ModelIdentity('openrouter', 'openai/gpt-5.6-terra-272k'),
        [
            'input_tokens' => new Rate('input_tokens', '4', '1000000'),
            'output_tokens' => new Rate('output_tokens', '18', '1000000'),
            'exa_search_requests' => new Rate('exa_search_requests', '0.007', '1'),
        ],
        PricingSource::ProviderNative,
    );
    $snapshot = new PricingSnapshot($definition);

    $value = match ($kind) {
        'tampered fingerprint' => ['fingerprint' => str_repeat('0', 64), 'definition' => $definition->toArray()],
        'unknown declared algorithm' => ['fingerprint' => $snapshot->fingerprint, 'algorithm' => 'sha256:future@9', 'definition' => $definition->toArray()],
        'declared algorithm does not verify' => ['fingerprint' => hash('sha256', 'not-the-snapshot'), 'algorithm' => PricingSnapshot::ALGORITHM_RATES_JSONB_V1, 'definition' => $definition->toArray()],
        'missing fingerprint' => ['definition' => $definition->toArray()],
        default => throw new LogicException("Unknown identity fixture [{$kind}]."),
    };

    PricingSnapshot::fromPersisted($value, $definition);
})->with([
    'tampered fingerprint',
    'unknown declared algorithm',
    'declared algorithm does not verify',
    'missing fingerprint',
])->throws(InvalidArgumentException::class);
