<?php

declare(strict_types=1);

namespace Jkudish\LaravelAiPricing\Sources;

use DateTimeImmutable;
use Jkudish\LaravelAiPricing\Contracts\FallbackPolicy;
use Jkudish\LaravelAiPricing\Contracts\PricingCatalog;
use Jkudish\LaravelAiPricing\Enums\PricingSource;
use Jkudish\LaravelAiPricing\ValueObjects\ModelIdentity;
use Jkudish\LaravelAiPricing\ValueObjects\PriceDefinition;
use Jkudish\LaravelAiPricing\ValueObjects\Rate;
use Override;

final readonly class PackagePricingSource implements FallbackPolicy, PricingCatalog
{
    /**
     * @param array{
     *     version: int,
     *     retrieved_at: string,
     *     effective_at: string|null,
     *     currency: string,
     *     fallback_blocked?: list<string>,
     *     aliases?: array<string, string>,
     *     prices: array<string, array{
     *         source: string,
     *         notes?: string,
     *         rates: array<string, array{amount: string|int, per: string|int}>
     *     }>
     * } $snapshot
     */
    public function __construct(private array $snapshot) {}

    /**
     * Find the reviewed price for an identity or a documented alias of one.
     *
     * An alias borrows its target's rates and provenance but keeps the
     * requested identity, so a quote for a moving alias still reports the
     * alias it was asked about. An alias whose target is not priced resolves
     * to nothing rather than to a guess.
     */
    #[Override]
    public function find(ModelIdentity $identity): ?PriceDefinition
    {
        $key = $identity->key();
        $price = $this->snapshot['prices'][$this->snapshot['aliases'][$key] ?? $key] ?? null;

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

    #[Override]
    public function allowsFallback(ModelIdentity $identity): bool
    {
        return ! in_array($identity->key(), $this->snapshot['fallback_blocked'] ?? [], true);
    }

    private function date(?string $date): ?DateTimeImmutable
    {
        return $date === null ? null : new DateTimeImmutable($date);
    }
}
