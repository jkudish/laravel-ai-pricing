<?php

declare(strict_types=1);

namespace Jkudish\LaravelAiPricing;

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

    /** @param array<string, mixed>|object $response */
    public function cost(array|object $response): CostQuote
    {
        return $this->resolver->resolve($this->laravelAi->adapt($response));
    }

    public function quote(string $provider, string $model, Usage $usage): CostQuote
    {
        return $this->resolver->resolve(new PricingObservation(
            identity: new ModelIdentity($provider, $model),
            usage: $usage,
        ));
    }
}
