<?php

declare(strict_types=1);

namespace Jkudish\LaravelAiPricing\ValueObjects;

use DateTimeImmutable;
use Jkudish\LaravelAiPricing\Enums\PricingSource;

final readonly class PricingProvenance
{
    /**
     * @param  list<RateWindow>  $rateWindows  The dated windows whose rates priced the quote: the one in
     *                                         effect for an actual cost, or every window an estimate
     *                                         interval overlapped. Empty for unwindowed prices.
     */
    public function __construct(
        public PricingSource $source,
        public ?DateTimeImmutable $effectiveAt = null,
        public ?DateTimeImmutable $retrievedAt = null,
        public ?string $reference = null,
        public array $rateWindows = [],
    ) {}

    /** @return array{source: string, effective_at: ?string, retrieved_at: ?string, reference: ?string, rate_windows?: list<array{effective_from: ?string, effective_until: ?string}>} */
    public function toArray(): array
    {
        $value = [
            'source' => $this->source->value,
            'effective_at' => $this->effectiveAt?->format(DATE_ATOM),
            'retrieved_at' => $this->retrievedAt?->format(DATE_ATOM),
            'reference' => $this->reference,
        ];

        if ($this->rateWindows !== []) {
            $value['rate_windows'] = array_map(static fn (RateWindow $window): array => $window->bounds(), $this->rateWindows);
        }

        return $value;
    }
}
