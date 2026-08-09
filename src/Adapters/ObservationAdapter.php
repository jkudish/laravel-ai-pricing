<?php

declare(strict_types=1);

namespace Jkudish\LaravelAiPricing\Adapters;

use Jkudish\LaravelAiPricing\ValueObjects\PricingObservation;

interface ObservationAdapter
{
    /** @param array<string, mixed>|object $value */
    public function adapt(array|object $value): PricingObservation;
}
