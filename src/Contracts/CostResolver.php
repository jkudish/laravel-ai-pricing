<?php

declare(strict_types=1);

namespace Jkudish\LaravelAiPricing\Contracts;

use Jkudish\LaravelAiPricing\ValueObjects\CostQuote;
use Jkudish\LaravelAiPricing\ValueObjects\PricingObservation;

interface CostResolver
{
    public function resolve(PricingObservation $observation): CostQuote;
}
