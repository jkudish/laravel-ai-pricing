<?php

declare(strict_types=1);

namespace Jkudish\LaravelAiPricing;

use Jkudish\LaravelAiPricing\Contracts\CostResolver;
use Jkudish\LaravelAiPricing\Contracts\FallbackPolicy;
use Jkudish\LaravelAiPricing\Contracts\PricingCatalog;
use Jkudish\LaravelAiPricing\Enums\CostCompleteness;
use Jkudish\LaravelAiPricing\Enums\PricingSource;
use Jkudish\LaravelAiPricing\ValueObjects\CostQuote;
use Jkudish\LaravelAiPricing\ValueObjects\PricingObservation;
use Jkudish\LaravelAiPricing\ValueObjects\PricingProvenance;
use Override;

final readonly class PricingResolver implements CostResolver
{
    public function __construct(
        private PricingCatalog $configured,
        private PricingCatalog $native,
        private PricingCatalog $fallback,
        private string $currency = 'USD',
        private CostCalculator $calculator = new CostCalculator,
        private ?PricingCatalog $snapshot = null,
    ) {}

    #[Override]
    public function resolve(PricingObservation $observation): CostQuote
    {
        if ($observation->providerReportedCost !== null) {
            if ($observation->providerReportedCost->currency !== strtoupper($this->currency)) {
                return CostQuote::unavailable();
            }

            return new CostQuote(
                cost: $observation->providerReportedCost,
                completeness: CostCompleteness::Complete,
                source: PricingSource::ProviderReported,
                provenance: new PricingProvenance(PricingSource::ProviderReported),
            );
        }

        $pricing = $this->configured->find($observation->identity);

        if ($pricing === null) {
            $pricing = $observation->providerNativePricing
                ?? $this->native->find($observation->identity)
                ?? $this->snapshot?->find($observation->identity);
        }

        if ($pricing === null && $this->allowsFallback($observation)) {
            $pricing = $this->fallback->find($observation->identity);
        }

        if ($pricing === null) {
            return CostQuote::unavailable();
        }

        if ($pricing->currency !== strtoupper($this->currency)) {
            return CostQuote::unavailable();
        }

        return $this->calculator->calculate($observation->usage, $pricing);
    }

    private function allowsFallback(PricingObservation $observation): bool
    {
        return ! $this->snapshot instanceof FallbackPolicy
            || $this->snapshot->allowsFallback($observation->identity);
    }
}
