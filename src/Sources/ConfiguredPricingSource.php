<?php

declare(strict_types=1);

namespace Jkudish\LaravelAiPricing\Sources;

use Jkudish\LaravelAiPricing\Contracts\PricingCatalog;
use Jkudish\LaravelAiPricing\Enums\PricingSource;
use Jkudish\LaravelAiPricing\ValueObjects\ModelIdentity;
use Jkudish\LaravelAiPricing\ValueObjects\PriceDefinition;
use Jkudish\LaravelAiPricing\ValueObjects\Rate;
use Override;

final readonly class ConfiguredPricingSource implements PricingCatalog
{
    /** @param array<string, array<string, array{amount: string|int, per?: string|int, currency?: string}>> $prices */
    public function __construct(private array $prices, private string $currency = 'USD') {}

    #[Override]
    public function find(ModelIdentity $identity): ?PriceDefinition
    {
        $configured = $this->prices[$identity->key()] ?? null;

        if ($configured === null) {
            return null;
        }

        $rates = [];

        foreach ($configured as $unit => $rate) {
            $rates[$unit] = new Rate(
                unit: $unit,
                amount: $rate['amount'],
                per: $rate['per'] ?? 1,
                currency: $rate['currency'] ?? $this->currency,
            );
        }

        return new PriceDefinition(
            identity: $identity,
            rates: $rates,
            source: PricingSource::Configured,
            sourceReference: 'config:ai-pricing.prices',
        );
    }

    #[Override]
    public function sync(): int
    {
        return count($this->prices);
    }
}
