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
        $rates = $value['rates'] ?? null;
        if (is_array($rates)) {
            $value['rates'] = self::sortKeysJsonbOrder($rates);
        }

        return $value;
    }

    /**
     * Keys reach here as the strings of a JSON object, but nothing depends on
     * that: integer keys stringify the same way PHP stringifies them as array
     * offsets, so the comparison stays well defined for any array key.
     *
     * @param  array<array-key, mixed>  $value
     * @return array<array-key, mixed>
     */
    private static function sortKeysJsonbOrder(array $value): array
    {
        uksort($value, static function (int|string $a, int|string $b): int {
            return [strlen((string) $a), (string) $a] <=> [strlen((string) $b), (string) $b];
        });

        return $value;
    }

    /** @return array{fingerprint: string, definition: array<string, mixed>} */
    public function toArray(): array
    {
        return ['fingerprint' => $this->fingerprint, 'definition' => $this->definition->toArray()];
    }
}
