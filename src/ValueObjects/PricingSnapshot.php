<?php

declare(strict_types=1);

namespace Jkudish\LaravelAiPricing\ValueObjects;

final readonly class PricingSnapshot
{
    public string $fingerprint;

    public function __construct(public PriceDefinition $definition)
    {
        $encoded = json_encode($this->canonical($definition->toArray()), JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);
        $this->fingerprint = hash('sha256', $encoded);
    }

    /**
     * PostgreSQL jsonb persistence normalizes object key order, so the
     * fingerprint must be computed over a canonically key-sorted encoding to
     * stay valid after a storage round trip.
     *
     * @param  array<mixed, mixed>  $value
     * @return array<mixed, mixed>
     */
    private function canonical(array $value): array
    {
        ksort($value);

        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = $this->canonical($item);
            }
        }

        return $value;
    }

    /** @return array{fingerprint: string, definition: array<string, mixed>} */
    public function toArray(): array
    {
        return ['fingerprint' => $this->fingerprint, 'definition' => $this->definition->toArray()];
    }
}
