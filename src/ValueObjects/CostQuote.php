<?php

declare(strict_types=1);

namespace Jkudish\LaravelAiPricing\ValueObjects;

use Jkudish\LaravelAiPricing\Enums\CostCompleteness;
use Jkudish\LaravelAiPricing\Enums\PricingSource;

final readonly class CostQuote
{
    /** @param list<string> $missingUnits */
    public function __construct(
        public ?Money $cost,
        public CostCompleteness $completeness,
        public PricingSource $source,
        public ?PricingSnapshot $snapshot = null,
        public array $missingUnits = [],
        public ?PricingProvenance $provenance = null,
    ) {}

    public static function unavailable(): self
    {
        return new self(null, CostCompleteness::Unavailable, PricingSource::Unavailable);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'cost' => $this->cost?->toArray(),
            'completeness' => $this->completeness->value,
            'source' => $this->source->value,
            'snapshot' => $this->snapshot?->toArray(),
            'missing_units' => $this->missingUnits,
            'provenance' => $this->provenance?->toArray(),
        ];
    }
}
