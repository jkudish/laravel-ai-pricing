<?php

declare(strict_types=1);

namespace Jkudish\LaravelAiPricing\ValueObjects;

use DateTimeImmutable;
use Jkudish\LaravelAiPricing\Enums\PricingSource;

final readonly class PricingProvenance
{
    public function __construct(
        public PricingSource $source,
        public ?DateTimeImmutable $effectiveAt = null,
        public ?DateTimeImmutable $retrievedAt = null,
        public ?string $reference = null,
    ) {}

    /** @return array{source: string, effective_at: ?string, retrieved_at: ?string, reference: ?string} */
    public function toArray(): array
    {
        return [
            'source' => $this->source->value,
            'effective_at' => $this->effectiveAt?->format(DATE_ATOM),
            'retrieved_at' => $this->retrievedAt?->format(DATE_ATOM),
            'reference' => $this->reference,
        ];
    }
}
