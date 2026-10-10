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

        $remoteCatalogsAllowed = $this->allowsRemoteCatalogs($observation);

        if ($pricing === null) {
            $pricing = $observation->providerNativePricing
                ?? ($remoteCatalogsAllowed ? $this->native->find($observation->identity) : null)
                ?? $this->snapshot?->find($observation->identity);
        }

        if ($pricing === null && $remoteCatalogsAllowed && $this->nativeAllowsFallback($observation)) {
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

    /**
     * Decide whether remote catalogs may price the observed identity.
     *
     * An identity the snapshot's fallback policy blocks is one whose billed
     * rate a remote catalog cannot select safely: an ambiguous generic model,
     * a configured-only SKU, or a deliberately bound basis. Such an identity
     * skips both the remote native catalog and the fallback catalog, so only
     * provider-reported cost, configured prices, pricing attached to the
     * observation, or the reviewed snapshot can price it.
     */
    private function allowsRemoteCatalogs(PricingObservation $observation): bool
    {
        return ! $this->snapshot instanceof FallbackPolicy
            || $this->snapshot->allowsFallback($observation->identity);
    }

    /**
     * Decide whether the fallback catalog may price an identity the native catalog declined.
     *
     * A native catalog can list a model whose pricing it cannot apply, such
     * as OpenRouter's prompt-length tiers. The fallback catalog would price
     * that model from a flat rate, so the native catalog's refusal stands.
     */
    private function nativeAllowsFallback(PricingObservation $observation): bool
    {
        return ! $this->native instanceof FallbackPolicy
            || $this->native->allowsFallback($observation->identity);
    }
}
