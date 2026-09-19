<?php

declare(strict_types=1);

namespace Jkudish\LaravelAiPricing\ValueObjects;

use InvalidArgumentException;
use ReflectionClass;

final readonly class PricingSnapshot
{
    /**
     * The algorithm new snapshots are fingerprinted with: the definition
     * encoded in value-object order with only the rates map normalized.
     */
    public const string ALGORITHM_RATES_JSONB_V1 = 'sha256:rates-jsonb-order@1';

    /**
     * The recursive whole-definition ksort algorithm used by the first
     * wave-internal revision. It survives jsonb storage round trips because
     * it is order-independent, so persisted snapshots fingerprinted with it
     * stay replayable.
     */
    public const string ALGORITHM_RECURSIVE_KSORT_V0 = 'sha256:recursive-ksort@0';

    /**
     * The original algorithm: the definition encoded exactly as the value
     * objects emit it, with rates in their original insertion order. It
     * survives jsonb storage only when a definition's insertion order was
     * already jsonb order; otherwise the stored fingerprint references an
     * ordering the round trip destroys and cannot be verified again.
     */
    public const string ALGORITHM_PLAIN_V0 = 'sha256:plain@0';

    public string $fingerprint;

    public string $algorithm;

    public function __construct(public PriceDefinition $definition)
    {
        $this->algorithm = self::ALGORITHM_RATES_JSONB_V1;
        $fingerprint = self::hash($definition, $this->algorithm);
        if ($fingerprint === null) {
            throw new InvalidArgumentException('The current fingerprint algorithm is unknown.');
        }
        $this->fingerprint = $fingerprint;
    }

    /**
     * Rehydrate a persisted snapshot while preserving the identity it was
     * persisted with. The stored fingerprint is verified against the
     * definition rebuilt from the same persisted value — under the declared
     * algorithm when the persisted form carries one, otherwise against every
     * algorithm this package has ever used for fingerprints — and the
     * returned snapshot keeps the stored fingerprint and the algorithm that
     * verified it instead of silently recomputing a new identity.
     *
     * Verification is strict: a fingerprint that matches none of the known
     * algorithms, or an unknown declared algorithm, is rejected rather than
     * forgiven.
     *
     * @param  array{fingerprint?: mixed, algorithm?: mixed}  $value
     */
    public static function fromPersisted(array $value, PriceDefinition $definition): self
    {
        $stored = $value['fingerprint'] ?? null;
        $declared = $value['algorithm'] ?? null;

        if (! is_string($stored) || $stored === '' || ($declared !== null && (! is_string($declared) || $declared === ''))) {
            throw new InvalidArgumentException('The persisted pricing snapshot identity is malformed.');
        }

        $algorithms = is_string($declared)
            ? [$declared]
            : [self::ALGORITHM_RATES_JSONB_V1, self::ALGORITHM_RECURSIVE_KSORT_V0, self::ALGORITHM_PLAIN_V0];

        foreach ($algorithms as $algorithm) {
            $hash = self::hash($definition, $algorithm);

            if ($hash !== null && hash_equals($stored, $hash)) {
                return self::rehydrate($definition, $stored, $algorithm);
            }
        }

        throw new InvalidArgumentException('The persisted pricing fingerprint does not verify under a known fingerprint algorithm.');
    }

    /** @return array{fingerprint: string, definition: array<string, mixed>} */
    public function toArray(): array
    {
        return ['fingerprint' => $this->fingerprint, 'definition' => $this->definition->toArray()];
    }

    /**
     * Compute the fingerprint of a definition under a named algorithm, or
     * null when the algorithm is unknown so callers fail closed.
     */
    private static function hash(PriceDefinition $definition, string $algorithm): ?string
    {
        $value = $definition->toArray();

        $canonical = match ($algorithm) {
            self::ALGORITHM_RATES_JSONB_V1 => self::withRatesSortedJsonbOrder($value),
            self::ALGORITHM_RECURSIVE_KSORT_V0 => self::recursiveKsort($value),
            self::ALGORITHM_PLAIN_V0 => $value,
            default => null,
        };

        if ($canonical === null) {
            return null;
        }

        $encoded = json_encode($canonical, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);

        return hash('sha256', $encoded);
    }

    /**
     * A verified persisted identity rehydrates the immutable snapshot with
     * its stored fingerprint and algorithm rather than a recomputed one.
     * Read-only properties initialize once from class scope, so the
     * constructor's computed identity is bypassed deliberately here and
     * nowhere else.
     */
    private static function rehydrate(PriceDefinition $definition, string $fingerprint, string $algorithm): self
    {
        $snapshot = (new ReflectionClass(self::class))->newInstanceWithoutConstructor();
        // Read-only properties legally initialize once from class scope;
        // PHPStan's rule does not model initialization outside the
        // constructor, so each assignment below carries that reason.
        $snapshot->definition = $definition; // @phpstan-ignore property.readOnlyAssignNotInConstructor (once, from class scope)
        $snapshot->algorithm = $algorithm; // @phpstan-ignore property.readOnlyAssignNotInConstructor (once, from class scope)
        $snapshot->fingerprint = $fingerprint; // @phpstan-ignore property.readOnlyAssignNotInConstructor (once, from class scope)

        return $snapshot;
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
    private static function withRatesSortedJsonbOrder(array $value): array
    {
        $rates = $value['rates'] ?? null;
        if (is_array($rates)) {
            $value['rates'] = self::sortKeysJsonbOrder($rates);
        }

        return $value;
    }

    /**
     * The recursive whole-definition ksort of the first wave-internal
     * revision, preserved so its persisted snapshots keep verifying.
     *
     * @param  array<mixed, mixed>  $value
     * @return array<mixed, mixed>
     */
    private static function recursiveKsort(array $value): array
    {
        ksort($value);

        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = self::recursiveKsort($item);
            }
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
}
