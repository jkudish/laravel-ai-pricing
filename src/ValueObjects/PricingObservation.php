<?php

declare(strict_types=1);

namespace Jkudish\LaravelAiPricing\ValueObjects;

final readonly class PricingObservation
{
    public function __construct(
        public ModelIdentity $identity,
        public Usage $usage,
        public ?Money $providerReportedCost = null,
        public ?PriceDefinition $providerNativePricing = null,
        public ?ModelIdentity $requestedIdentity = null,
    ) {}
}
