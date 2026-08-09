<?php

declare(strict_types=1);

namespace Jkudish\LaravelAiPricing\ValueObjects;

use DateTimeImmutable;
use InvalidArgumentException;
use Jkudish\LaravelAiPricing\Enums\PricingSource;

final readonly class PriceDefinition
{
    /** @var array<string, Rate> */
    public array $rates;

    public string $currency;

    /** @param array<string, Rate> $rates */
    public function __construct(
        public ModelIdentity $identity,
        array $rates,
        public PricingSource $source,
        public ?DateTimeImmutable $effectiveAt = null,
        public ?DateTimeImmutable $retrievedAt = null,
        public ?string $sourceReference = null,
    ) {
        if ($rates === []) {
            throw new InvalidArgumentException('A price definition requires at least one rate.');
        }

        $currency = null;

        foreach ($rates as $unit => $rate) {
            if ($rate->unit !== $unit) {
                throw new InvalidArgumentException('Rates must be keyed by their usage unit.');
            }

            if ($currency !== null && $currency !== $rate->currency) {
                throw new InvalidArgumentException('A price definition cannot contain mixed currencies.');
            }

            $currency = $rate->currency;
        }

        $this->rates = $rates;
        $this->currency = $currency;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'identity' => $this->identity->toArray(),
            'rates' => array_map(static fn (Rate $rate): array => $rate->toArray(), $this->rates),
            'source' => $this->source->value,
            'currency' => $this->currency,
            'effective_at' => $this->effectiveAt?->format(DATE_ATOM),
            'retrieved_at' => $this->retrievedAt?->format(DATE_ATOM),
            'source_reference' => $this->sourceReference,
        ];
    }
}
