<?php

declare(strict_types=1);

namespace Jkudish\LaravelAiPricing\ValueObjects;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;

/**
 * The rates a price definition charges during one half-open period
 * [effectiveFrom, effectiveUntil). A null bound leaves that side open.
 */
final readonly class RateWindow
{
    public ?DateTimeImmutable $effectiveFrom;

    public ?DateTimeImmutable $effectiveUntil;

    /** @var array<string, Rate> */
    public array $rates;

    public string $currency;

    /** @param array<string, Rate> $rates */
    public function __construct(
        ?DateTimeInterface $effectiveFrom,
        ?DateTimeInterface $effectiveUntil,
        array $rates,
    ) {
        if ($rates === []) {
            throw new InvalidArgumentException('A rate window requires at least one rate.');
        }

        $this->effectiveFrom = self::utc($effectiveFrom);
        $this->effectiveUntil = self::utc($effectiveUntil);

        if ($this->effectiveFrom !== null && $this->effectiveUntil !== null && $this->effectiveFrom >= $this->effectiveUntil) {
            throw new InvalidArgumentException('A rate window must start before it ends.');
        }

        $this->currency = Rate::currencyOf($rates);
        $this->rates = $rates;
    }

    public function contains(DateTimeInterface $instant): bool
    {
        return ($this->effectiveFrom === null || $this->effectiveFrom <= $instant)
            && ($this->effectiveUntil === null || $instant < $this->effectiveUntil);
    }

    /** Whether the window shares any instant with the half-open interval [from, until). */
    public function overlaps(DateTimeInterface $from, DateTimeInterface $until): bool
    {
        return ($this->effectiveFrom === null || $this->effectiveFrom < $until)
            && ($this->effectiveUntil === null || $from < $this->effectiveUntil);
    }

    /** @return array{effective_from: ?string, effective_until: ?string} */
    public function bounds(): array
    {
        return [
            'effective_from' => $this->effectiveFrom?->format(DATE_ATOM),
            'effective_until' => $this->effectiveUntil?->format(DATE_ATOM),
        ];
    }

    /** @return array{effective_from: ?string, effective_until: ?string, rates: array<string, array{unit: string, amount: string, per: string, currency: string}>} */
    public function toArray(): array
    {
        return [
            ...$this->bounds(),
            'rates' => array_map(static fn (Rate $rate): array => $rate->toArray(), $this->rates),
        ];
    }

    private static function utc(?DateTimeInterface $instant): ?DateTimeImmutable
    {
        return $instant === null
            ? null
            : DateTimeImmutable::createFromInterface($instant)->setTimezone(new DateTimeZone('UTC'));
    }
}
