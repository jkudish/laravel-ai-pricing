<?php

declare(strict_types=1);

namespace Jkudish\LaravelAiPricing\ValueObjects;

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;
use Jkudish\LaravelAiPricing\Enums\PricingSource;

final readonly class PriceDefinition
{
    /** @var array<string, Rate> */
    public array $rates;

    /** @var list<RateWindow> */
    public array $rateWindows;

    public string $currency;

    /**
     * A definition carries either one set of rates or an ordered list of
     * dated rate windows. Windows are half-open, contiguous and
     * non-overlapping: only the first may be open at its start and only the
     * last may be open at its end, and every window prices the same units.
     *
     * @param  array<string, Rate>  $rates
     * @param  list<RateWindow>  $rateWindows
     */
    public function __construct(
        public ModelIdentity $identity,
        array $rates,
        public PricingSource $source,
        public ?DateTimeImmutable $effectiveAt = null,
        public ?DateTimeImmutable $retrievedAt = null,
        public ?string $sourceReference = null,
        array $rateWindows = [],
    ) {
        if ($rateWindows === []) {
            if ($rates === []) {
                throw new InvalidArgumentException('A price definition requires at least one rate.');
            }

            $this->currency = Rate::currencyOf($rates);
        } else {
            if ($rates !== []) {
                throw new InvalidArgumentException('A windowed price definition carries its rates in its windows.');
            }

            $this->currency = self::validateWindows($rateWindows);
        }

        $this->rates = $rates;
        $this->rateWindows = $rateWindows;
    }

    public function isWindowed(): bool
    {
        return $this->rateWindows !== [];
    }

    /** The window in effect at an instant, or null when the instant is outside every window. */
    public function windowAt(DateTimeInterface $instant): ?RateWindow
    {
        foreach ($this->rateWindows as $window) {
            if ($window->contains($instant)) {
                return $window;
            }
        }

        return null;
    }

    /**
     * Every window overlapping the half-open interval [from, until), or an
     * empty list when the windows do not cover the whole interval. An
     * interval that ends at or before it starts is the instant `from`.
     *
     * @return list<RateWindow>
     */
    public function windowsCovering(DateTimeInterface $from, DateTimeInterface $until): array
    {
        if ($until <= $from) {
            $window = $this->windowAt($from);

            return $window === null ? [] : [$window];
        }

        $overlapping = array_values(array_filter(
            $this->rateWindows,
            static fn (RateWindow $window): bool => $window->overlaps($from, $until),
        ));

        if ($overlapping === []) {
            return [];
        }

        // Windows are contiguous, so the overlap covers the interval exactly
        // when its first window contains the start and its last reaches the end.
        $last = $overlapping[array_key_last($overlapping)];

        if (! $overlapping[0]->contains($from) || ($last->effectiveUntil !== null && $last->effectiveUntil < $until)) {
            return [];
        }

        return $overlapping;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $value = [
            'identity' => $this->identity->toArray(),
            'rates' => array_map(static fn (Rate $rate): array => $rate->toArray(), $this->rates),
        ];

        // Unwindowed definitions keep their original encoding, so their
        // snapshot fingerprints are unchanged.
        if ($this->rateWindows !== []) {
            $value['rate_windows'] = array_map(static fn (RateWindow $window): array => $window->toArray(), $this->rateWindows);
        }

        return [
            ...$value,
            'source' => $this->source->value,
            'currency' => $this->currency,
            'effective_at' => $this->effectiveAt?->format(DATE_ATOM),
            'retrieved_at' => $this->retrievedAt?->format(DATE_ATOM),
            'source_reference' => $this->sourceReference,
        ];
    }

    /**
     * @param  non-empty-list<RateWindow>  $windows
     */
    private static function validateWindows(array $windows): string
    {
        $first = $windows[0];
        $units = array_keys($first->rates);
        sort($units);

        foreach ($windows as $index => $window) {
            $windowUnits = array_keys($window->rates);
            sort($windowUnits);

            if ($windowUnits !== $units) {
                throw new InvalidArgumentException('Every rate window must price the same units.');
            }

            if ($window->currency !== $first->currency) {
                throw new InvalidArgumentException('A price definition cannot contain mixed currencies.');
            }

            if ($index === 0) {
                continue;
            }

            $previousUntil = $windows[$index - 1]->effectiveUntil;

            if ($previousUntil === null || $window->effectiveFrom === null || $previousUntil != $window->effectiveFrom) {
                throw new InvalidArgumentException('Rate windows must be ordered, contiguous and non-overlapping.');
            }
        }

        return $first->currency;
    }
}
