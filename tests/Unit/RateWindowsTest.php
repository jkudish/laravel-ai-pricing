<?php

declare(strict_types=1);

use Jkudish\LaravelAiPricing\Contracts\PricingCatalog;
use Jkudish\LaravelAiPricing\CostCalculator;
use Jkudish\LaravelAiPricing\Enums\CostCompleteness;
use Jkudish\LaravelAiPricing\Enums\PricingSource;
use Jkudish\LaravelAiPricing\PricingResolver;
use Jkudish\LaravelAiPricing\Sources\ConfiguredPricingSource;
use Jkudish\LaravelAiPricing\Sources\PackagePricingSource;
use Jkudish\LaravelAiPricing\ValueObjects\ModelIdentity;
use Jkudish\LaravelAiPricing\ValueObjects\PriceDefinition;
use Jkudish\LaravelAiPricing\ValueObjects\PricingObservation;
use Jkudish\LaravelAiPricing\ValueObjects\PricingSnapshot;
use Jkudish\LaravelAiPricing\ValueObjects\Rate;
use Jkudish\LaravelAiPricing\ValueObjects\RateWindow;
use Jkudish\LaravelAiPricing\ValueObjects\Usage;

function utc(string $instant): DateTimeImmutable
{
    return new DateTimeImmutable($instant, new DateTimeZone('UTC'));
}

/** @return array<string, Rate> */
function perMillion(string $input, string $output): array
{
    return [
        'input_tokens' => new Rate('input_tokens', $input, '1000000'),
        'output_tokens' => new Rate('output_tokens', $output, '1000000'),
    ];
}

function windowed(RateWindow ...$windows): PriceDefinition
{
    return new PriceDefinition(
        new ModelIdentity('vendor', 'model'),
        [],
        PricingSource::ProviderNative,
        sourceReference: 'https://vendor.test/pricing',
        rateWindows: array_values($windows),
    );
}

/** Introductory rates until 2027, doubled from then: Google's Gemini Flash schedule. */
function doubling(): PriceDefinition
{
    return windowed(
        new RateWindow(null, utc('2027-01-01T00:00:00Z'), perMillion('0.75', '3.75')),
        new RateWindow(utc('2027-01-01T00:00:00Z'), null, perMillion('1.50', '7.50')),
    );
}

function windowResolver(PriceDefinition $definition): PricingResolver
{
    $none = new ConfiguredPricingSource([]);
    $snapshot = new class($definition) implements PricingCatalog
    {
        public function __construct(private readonly PriceDefinition $definition) {}

        public function find(ModelIdentity $identity): PriceDefinition
        {
            return $this->definition;
        }

        public function sync(): int
        {
            return 1;
        }
    };

    return new PricingResolver($none, $none, $none, snapshot: $snapshot);
}

it('rejects windows that overlap, leave a gap, open inside the range or price different units', function (callable $windows, string $message): void {
    expect(fn () => windowed(...$windows()))->toThrow(InvalidArgumentException::class, $message);
})->with([
    'overlap' => [fn (): array => [
        new RateWindow(null, utc('2027-01-02T00:00:00Z'), perMillion('1', '1')),
        new RateWindow(utc('2027-01-01T00:00:00Z'), null, perMillion('2', '2')),
    ], 'contiguous'],
    'gap' => [fn (): array => [
        new RateWindow(null, utc('2027-01-01T00:00:00Z'), perMillion('1', '1')),
        new RateWindow(utc('2027-01-02T00:00:00Z'), null, perMillion('2', '2')),
    ], 'contiguous'],
    'out of order' => [fn (): array => [
        new RateWindow(utc('2027-01-01T00:00:00Z'), null, perMillion('2', '2')),
        new RateWindow(null, utc('2027-01-01T00:00:00Z'), perMillion('1', '1')),
    ], 'contiguous'],
    'open inner bound' => [fn (): array => [
        new RateWindow(null, null, perMillion('1', '1')),
        new RateWindow(utc('2027-01-01T00:00:00Z'), null, perMillion('2', '2')),
    ], 'contiguous'],
    'different units' => [fn (): array => [
        new RateWindow(null, utc('2027-01-01T00:00:00Z'), perMillion('1', '1')),
        new RateWindow(utc('2027-01-01T00:00:00Z'), null, ['input_tokens' => new Rate('input_tokens', '2', '1000000')]),
    ], 'same units'],
]);

it('rejects empty and inverted windows, rates beside windows, and intervals that end before they start', function (): void {
    expect(fn () => new RateWindow(utc('2027-01-01T00:00:00Z'), utc('2027-01-01T00:00:00Z'), perMillion('1', '1')))
        ->toThrow(InvalidArgumentException::class, 'start before it ends')
        ->and(fn () => new PriceDefinition(
            new ModelIdentity('vendor', 'model'),
            perMillion('1', '1'),
            PricingSource::Configured,
            rateWindows: [new RateWindow(null, null, perMillion('1', '1'))],
        ))->toThrow(InvalidArgumentException::class, 'carries its rates in its windows')
        ->and(fn () => new PricingObservation(
            new ModelIdentity('vendor', 'model'),
            Usage::tokens(1, 1),
            pricedAt: utc('2027-01-02T00:00:00Z'),
            pricedUntil: utc('2027-01-01T00:00:00Z'),
        ))->toThrow(InvalidArgumentException::class, 'end before it starts');
});

it('prices an actual cost at the window in effect when the request ran, switching exactly at the boundary', function (string $pricedAt, string $expected, ?string $from, ?string $until): void {
    $quote = windowResolver(doubling())->resolve(new PricingObservation(
        new ModelIdentity('vendor', 'model'),
        new Usage(['input_tokens' => 1_000_000, 'output_tokens' => 100_000]),
        pricedAt: new DateTimeImmutable($pricedAt),
    ));

    expect((string) $quote->cost?->amount)->toBe($expected)
        ->and($quote->completeness)->toBe(CostCompleteness::Complete)
        ->and($quote->provenance?->toArray()['rate_windows'] ?? null)->toBe([['effective_from' => $from, 'effective_until' => $until]]);
})->with([
    'last second of 2026' => ['2026-12-31T23:59:59Z', '1.125', null, '2027-01-01T00:00:00+00:00'],
    'first instant of 2027' => ['2027-01-01T00:00:00Z', '2.25', '2027-01-01T00:00:00+00:00', null],
    // 2026-12-31T16:30-08:00 is 2027-01-01T00:30Z: windows compare instants, not wall clocks.
    'Vancouver New Year\'s Eve after midnight UTC' => ['2026-12-31T16:30:00-08:00', '2.25', '2027-01-01T00:00:00+00:00', null],
]);

it('prices an observation without a timestamp at the window in effect now', function (): void {
    $observation = new PricingObservation(new ModelIdentity('vendor', 'model'), new Usage(['input_tokens' => 1_000_000]));

    $this->travelTo(utc('2026-12-31T23:00:00Z'));
    $before = windowResolver(doubling())->resolve($observation);

    $this->travelTo(utc('2027-01-01T01:00:00Z'));
    $after = windowResolver(doubling())->resolve($observation);

    expect((string) $before->cost?->amount)->toBe('0.75')
        ->and((string) $after->cost?->amount)->toBe('1.5');
});

it('bounds an estimate by the highest rate of each unit across every window the interval overlaps', function (): void {
    // Input is dearest in the first window and output in the second, so
    // picking one whole window would understate one of them.
    $definition = windowed(
        new RateWindow(null, utc('2027-01-01T00:00:00Z'), perMillion('4', '1')),
        new RateWindow(utc('2027-01-01T00:00:00Z'), utc('2027-02-01T00:00:00Z'), perMillion('2', '3')),
        new RateWindow(utc('2027-02-01T00:00:00Z'), null, perMillion('9', '9')),
    );
    $usage = new Usage(['input_tokens' => 1_000_000, 'output_tokens' => 1_000_000]);
    $estimate = static fn (string $from, string $until) => windowResolver($definition)->resolve(new PricingObservation(
        new ModelIdentity('vendor', 'model'),
        $usage,
        pricedAt: utc($from),
        pricedUntil: utc($until),
    ));

    $spanning = $estimate('2026-12-31T12:00:00Z', '2027-01-01T12:00:00Z');
    $endsAtBoundary = $estimate('2026-12-31T12:00:00Z', '2027-01-01T00:00:00Z');
    $endsAfterThird = $estimate('2026-12-31T12:00:00Z', '2027-02-01T00:00:01Z');

    expect((string) $spanning->cost?->amount)->toBe('7')
        ->and($spanning->provenance?->toArray()['rate_windows'] ?? null)->toBe([
            ['effective_from' => null, 'effective_until' => '2027-01-01T00:00:00+00:00'],
            ['effective_from' => '2027-01-01T00:00:00+00:00', 'effective_until' => '2027-02-01T00:00:00+00:00'],
        ])
        ->and((string) $endsAtBoundary->cost?->amount)->toBe('5')
        ->and($endsAtBoundary->provenance?->toArray()['rate_windows'] ?? null)->toBe([['effective_from' => null, 'effective_until' => '2027-01-01T00:00:00+00:00']])
        ->and((string) $endsAfterThird->cost?->amount)->toBe('18');
});

it('bounds an estimate from now to the deadline when the observation has no start', function (): void {
    $this->travelTo(utc('2026-12-31T23:00:00Z'));

    $resolver = windowResolver(doubling());
    $observation = static fn (?DateTimeImmutable $until): PricingObservation => new PricingObservation(
        new ModelIdentity('vendor', 'model'),
        new Usage(['input_tokens' => 1_000_000]),
        pricedUntil: $until,
    );

    expect((string) $resolver->resolve($observation(utc('2026-12-31T23:30:00Z')))->cost?->amount)->toBe('0.75')
        ->and((string) $resolver->resolve($observation(utc('2027-01-01T00:30:00Z')))->cost?->amount)->toBe('1.5');
});

it('quotes unavailable instead of guessing outside the declared windows', function (): void {
    $definition = windowed(
        new RateWindow(utc('2026-01-01T00:00:00Z'), utc('2027-01-01T00:00:00Z'), perMillion('1', '1')),
    );
    $usage = new Usage(['input_tokens' => 1_000_000]);
    $calculator = new CostCalculator;

    $before = $calculator->calculate($usage, $definition, utc('2025-12-31T23:59:59Z'));
    $after = $calculator->calculate($usage, $definition, utc('2027-01-01T00:00:00Z'));
    $runsPastTheEnd = $calculator->calculate($usage, $definition, utc('2026-12-31T00:00:00Z'), utc('2027-01-01T00:00:01Z'));
    $inside = $calculator->calculate($usage, $definition, utc('2026-12-31T00:00:00Z'), utc('2027-01-01T00:00:00Z'));

    expect($before->completeness)->toBe(CostCompleteness::Unavailable)
        ->and($before->missingUnits)->toBe(['input_tokens'])
        ->and($after->completeness)->toBe(CostCompleteness::Unavailable)
        ->and($runsPastTheEnd->completeness)->toBe(CostCompleteness::Unavailable)
        ->and($runsPastTheEnd->cost)->toBeNull()
        ->and((string) $inside->cost?->amount)->toBe('1');
});

it('resolves prices without windows exactly as before, at any time and for any interval', function (): void {
    /** @var array{version: int, retrieved_at: string, effective_at: string|null, currency: string, prices: array<string, array{source: string, rates?: array<string, array{amount: string|int, per: string|int}>}>} $snapshot */
    $snapshot = require __DIR__.'/../../resources/pricing/provider-skus.php';
    // Pinned from laravel-ai-pricing 0.2.3 (before rate windows existed).
    $snapshot['retrieved_at'] = '2026-10-10T01:47:24+00:00';
    $none = new ConfiguredPricingSource([]);
    $resolver = new PricingResolver($none, $none, $none, snapshot: new PackagePricingSource($snapshot));
    $identity = new ModelIdentity('gemini', 'gemini-3.8-flash-standard-2026');
    $usage = new Usage(['input_tokens' => 1234567, 'output_tokens' => 89012, 'cached_input_tokens' => 3456, 'web_searches' => 3]);
    $rates = [
        'input_tokens' => ['unit' => 'input_tokens', 'amount' => '0.75', 'per' => '1000000', 'currency' => 'USD'],
        'output_tokens' => ['unit' => 'output_tokens', 'amount' => '3.75', 'per' => '1000000', 'currency' => 'USD'],
        'cached_input_tokens' => ['unit' => 'cached_input_tokens', 'amount' => '0.075', 'per' => '1000000', 'currency' => 'USD'],
        'web_searches' => ['unit' => 'web_searches', 'amount' => '0.014', 'per' => '1', 'currency' => 'USD'],
    ];
    $before = [
        'cost' => ['amount' => '1.30197945', 'currency' => 'USD'],
        'completeness' => 'complete',
        'source' => 'provider_native',
        'snapshot' => [
            'fingerprint' => '5c69a1cbc96effc12dbc00f77623cb8f225f385aa700ff8d30390ebd2782528d',
            'definition' => [
                'identity' => ['provider' => 'gemini', 'model' => 'gemini-3.8-flash-standard-2026'],
                'rates' => $rates,
                'source' => 'provider_native',
                'currency' => 'USD',
                'effective_at' => null,
                'retrieved_at' => '2026-10-10T01:47:24+00:00',
                'source_reference' => 'https://ai.google.dev/gemini-api/docs/pricing',
            ],
        ],
        'missing_units' => [],
        'provenance' => [
            'source' => 'provider_native',
            'effective_at' => null,
            'retrieved_at' => '2026-10-10T01:47:24+00:00',
            'reference' => 'https://ai.google.dev/gemini-api/docs/pricing',
        ],
    ];

    foreach ([[null, null], [utc('2030-06-01T00:00:00Z'), null], [utc('2020-01-01T00:00:00Z'), utc('2031-01-01T00:00:00Z')], [null, utc('2031-01-01T00:00:00Z')]] as [$at, $until]) {
        expect($resolver->resolve(new PricingObservation($identity, $usage, pricedAt: $at, pricedUntil: $until))->toArray())->toBe($before);
    }
});

it('fingerprints a windowed definition independently of each window\'s rate order', function (): void {
    $reordered = windowed(
        new RateWindow(null, utc('2027-01-01T00:00:00Z'), array_reverse(perMillion('0.75', '3.75'))),
        new RateWindow(utc('2027-01-01T00:00:00Z'), null, array_reverse(perMillion('1.50', '7.50'))),
    );
    $persisted = new PricingSnapshot(doubling());

    expect((new PricingSnapshot($reordered))->fingerprint)->toBe($persisted->fingerprint)
        ->and(PricingSnapshot::fromPersisted($persisted->toArray(), $reordered)->fingerprint)->toBe($persisted->fingerprint)
        ->and($persisted->toArray()['definition']['rate_windows'][1]['effective_from'])->toBe('2027-01-01T00:00:00+00:00')
        ->and((new PricingSnapshot(windowed(
            new RateWindow(null, utc('2027-01-01T00:00:00Z'), perMillion('0.75', '3.75')),
            new RateWindow(utc('2027-01-01T00:00:00Z'), null, perMillion('1.50', '7.51')),
        )))->fingerprint)->not->toBe($persisted->fingerprint);
});
