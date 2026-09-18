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
     * PostgreSQL jsonb persistence normalizes object key order, so a
     * definition rebuilt from storage can only reproduce its fingerprint
     * when the only ordering that varies through that round trip is
     * normalized here: the rates map. Every other key order is re-emitted
     * deterministically by the value objects, so canonicalizing more than
     * the rates map would change fingerprints of already-persisted
     * snapshots that were computed over the legacy encoding.
     *
     * The rates map is sorted the way jsonb itself sorts object keys: by
     * key length first, then bytewise (for example `cached_input_tokens`
     * sorts before `cache_write_input_tokens` in jsonb even though plain
     * bytewise sorting would reverse them).
     *
     * @param  array<string, mixed>  $value
     * @return array<string, mixed>
     */
    private function canonical(array $value): array
    {
        $value['rates'] = self::sortKeysJsonbOrder($value['rates'] ?? []);

        return $value;
    }

    /**
     * @param  array<string, mixed>  $value
     * @return array<string, mixed>
     */
    private static function sortKeysJsonbOrder(array $value): array
    {
        uksort($value, static function (string $a, string $b): int {
            return [strlen($a), $a] <=> [strlen($b), $b];
        });

        return $value;
    }

    /** @return array{fingerprint: string, definition: array<string, mixed>} */
    public function toArray(): array
    {
        return ['fingerprint' => $this->fingerprint, 'definition' => $this->definition->toArray()];
    }
}
