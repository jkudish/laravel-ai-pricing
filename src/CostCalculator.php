<?php

declare(strict_types=1);

namespace Jkudish\LaravelAiPricing;

use Jkudish\LaravelAiPricing\Enums\CostCompleteness;
use Jkudish\LaravelAiPricing\ValueObjects\CostQuote;
use Jkudish\LaravelAiPricing\ValueObjects\Money;
use Jkudish\LaravelAiPricing\ValueObjects\PriceDefinition;
use Jkudish\LaravelAiPricing\ValueObjects\PricingProvenance;
use Jkudish\LaravelAiPricing\ValueObjects\PricingSnapshot;
use Jkudish\LaravelAiPricing\ValueObjects\Usage;

final class CostCalculator
{
    public function calculate(Usage $usage, PriceDefinition $pricing): CostQuote
    {
        $total = null;
        $missing = [];

        foreach ($usage->toArray() as $unit => $quantity) {
            if ($usage->quantity($unit)->isZero()) {
                continue;
            }

            $rate = $pricing->rates[$unit] ?? null;

            if ($rate === null) {
                $missing[] = $unit;

                continue;
            }

            $line = $rate->cost($usage->quantity($unit));
            $total = $total instanceof Money ? $total->plus($line) : $line;
        }

        if (! $total instanceof Money) {
            return new CostQuote(
                cost: null,
                completeness: CostCompleteness::Unavailable,
                source: $pricing->source,
                snapshot: new PricingSnapshot($pricing),
                missingUnits: $missing,
                provenance: new PricingProvenance($pricing->source, $pricing->effectiveAt, $pricing->retrievedAt, $pricing->sourceReference),
            );
        }

        return new CostQuote(
            cost: $total,
            completeness: $missing === [] ? CostCompleteness::Complete : CostCompleteness::Partial,
            source: $pricing->source,
            snapshot: new PricingSnapshot($pricing),
            missingUnits: $missing,
            provenance: new PricingProvenance($pricing->source, $pricing->effectiveAt, $pricing->retrievedAt, $pricing->sourceReference),
        );
    }
}
