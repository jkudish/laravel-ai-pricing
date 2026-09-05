<?php

declare(strict_types=1);

namespace Jkudish\LaravelAiPricing\Sources;

use DateTimeImmutable;
use Jkudish\LaravelAiPricing\Contracts\PricingCatalog;
use Jkudish\LaravelAiPricing\Enums\PricingSource;
use Jkudish\LaravelAiPricing\ValueObjects\ModelIdentity;
use Jkudish\LaravelAiPricing\ValueObjects\PriceDefinition;
use Jkudish\LaravelAiPricing\ValueObjects\Rate;
use Override;

final readonly class PackagePricingSource implements PricingCatalog
{
    /**
     * @param array{
     *     version: int,
     *     retrieved_at: string,
     *     effective_at: string|null,
     *     currency: string,
     *     prices: array<string, array{
     *         source: string,
     *         notes?: string,
     *         rates: array<string, array{amount: string|int, per: string|int}>
     *     }>
     * } $snapshot
     */
    public function __construct(private array $snapshot) {}

    #[Override]
    public function find(ModelIdentity $identity): ?PriceDefinition
    {
        $price = $this->snapshot['prices'][$identity->key()] ?? null;

        if ($price === null) {
            return null;
        }

        $rates = [];

        foreach ($price['rates'] as $unit => $rate) {
            $rates[$unit] = new Rate(
                unit: $unit,
                amount: $rate['amount'],
                per: $rate['per'],
                currency: $this->snapshot['currency'],
            );
        }

        return new PriceDefinition(
            identity: $identity,
            rates: $rates,
            source: PricingSource::ProviderNative,
            effectiveAt: $this->date($this->snapshot['effective_at']),
            retrievedAt: $this->date($this->snapshot['retrieved_at']),
            sourceReference: $price['source'],
        );
    }

    #[Override]
    public function sync(): int
    {
        return count($this->snapshot['prices']);
    }

    private function date(?string $date): ?DateTimeImmutable
    {
        return $date === null ? null : new DateTimeImmutable($date);
    }
}
