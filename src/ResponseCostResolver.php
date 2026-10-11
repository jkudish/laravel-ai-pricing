<?php

declare(strict_types=1);

namespace Jkudish\LaravelAiPricing;

use DateTimeImmutable;
use Jkudish\LaravelAiPricing\Adapters\LaravelAiObservationAdapter;
use Jkudish\LaravelAiPricing\Contracts\CostResolver;
use Jkudish\LaravelAiPricing\ValueObjects\CostQuote;
use Jkudish\LaravelAiPricing\ValueObjects\ModelIdentity;
use Jkudish\LaravelAiPricing\ValueObjects\PricingObservation;
use Jkudish\LaravelAiPricing\ValueObjects\Usage;

final readonly class ResponseCostResolver
{
    public function __construct(
        private CostResolver $resolver,
        private LaravelAiObservationAdapter $laravelAi,
    ) {}

    /**
     * Cost a completed response. Pass when the request ran as $at when
     * costing it later, so a price with dated rate windows uses the window
     * in effect then instead of now.
     *
     * @param  array<string, mixed>|object  $response
     */
    public function cost(array|object $response, ?DateTimeImmutable $at = null): CostQuote
    {
        $observation = $this->laravelAi->adapt($response);

        if ($at !== null) {
            $observation = new PricingObservation(
                identity: $observation->identity,
                usage: $observation->usage,
                providerReportedCost: $observation->providerReportedCost,
                providerNativePricing: $observation->providerNativePricing,
                requestedIdentity: $observation->requestedIdentity,
                pricedAt: $at,
            );
        }

        return $this->resolver->resolve($observation);
    }

    /**
     * Quote usage before a request runs. Pass the run's deadline as
     * $until to bound a price with dated rate windows by the highest rate
     * in effect between now (or $at) and the deadline.
     */
    public function quote(
        string $provider,
        string $model,
        Usage $usage,
        ?DateTimeImmutable $at = null,
        ?DateTimeImmutable $until = null,
    ): CostQuote {
        return $this->resolver->resolve(new PricingObservation(
            identity: new ModelIdentity($provider, $model),
            usage: $usage,
            pricedAt: $at,
            pricedUntil: $until,
        ));
    }
}
