<?php

declare(strict_types=1);

namespace Jkudish\LaravelAiPricing\ValueObjects;

use DateTimeImmutable;
use InvalidArgumentException;

final readonly class PricingObservation
{
    /**
     * Time only matters for prices with dated rate windows.
     *
     * @param  DateTimeImmutable|null  $pricedAt  When the request ran, for an actual cost; the start of the
     *                                            interval, for an estimate. Defaults to now.
     * @param  DateTimeImmutable|null  $pricedUntil  When set, the observation is a maximum-cost estimate for a
     *                                               run that can bill until this instant (its deadline). Each
     *                                               rate is the highest of every window overlapping
     *                                               [pricedAt, pricedUntil).
     */
    public function __construct(
        public ModelIdentity $identity,
        public Usage $usage,
        public ?Money $providerReportedCost = null,
        public ?PriceDefinition $providerNativePricing = null,
        public ?ModelIdentity $requestedIdentity = null,
        public ?DateTimeImmutable $pricedAt = null,
        public ?DateTimeImmutable $pricedUntil = null,
    ) {
        if ($pricedAt !== null && $pricedUntil !== null && $pricedUntil < $pricedAt) {
            throw new InvalidArgumentException('A pricing interval cannot end before it starts.');
        }
    }
}
