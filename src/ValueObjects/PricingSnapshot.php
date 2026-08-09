<?php

declare(strict_types=1);

namespace Jkudish\LaravelAiPricing\ValueObjects;

final readonly class PricingSnapshot
{
    public string $fingerprint;

    public function __construct(public PriceDefinition $definition)
    {
        $encoded = json_encode($definition->toArray(), JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);
        $this->fingerprint = hash('sha256', $encoded);
    }

    /** @return array{fingerprint: string, definition: array<string, mixed>} */
    public function toArray(): array
    {
        return ['fingerprint' => $this->fingerprint, 'definition' => $this->definition->toArray()];
    }
}
