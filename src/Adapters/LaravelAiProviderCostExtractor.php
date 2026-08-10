<?php

declare(strict_types=1);

namespace Jkudish\LaravelAiPricing\Adapters;

use Brick\Math\BigDecimal;
use Illuminate\Support\Enumerable;
use InvalidArgumentException;
use Jkudish\LaravelAiPricing\ValueObjects\Money;
use Throwable;

final class LaravelAiProviderCostExtractor
{
    /** @var array<string, array{path: string, currency: string}> */
    private readonly array $providers;

    /** @param array<array-key, array<string, mixed>> $providers */
    public function __construct(
        array $providers = [
            'openrouter' => ['path' => 'usage.cost', 'currency' => 'USD'],
        ],
    ) {
        $normalized = [];

        foreach ($providers as $provider => $definition) {
            $path = $definition['path'] ?? null;
            $currency = $definition['currency'] ?? 'USD';

            if (! is_string($provider) || trim($provider) === '' || ! is_string($path) || trim($path) === '') {
                throw new InvalidArgumentException('Provider cost mappings require non-empty provider names and response paths.');
            }

            if (! is_string($currency) || ! preg_match('/^[A-Za-z]{3}$/', $currency)) {
                throw new InvalidArgumentException('Provider cost mapping currency must be a three-letter ISO code.');
            }

            $provider = strtolower(trim($provider));

            if (isset($normalized[$provider])) {
                throw new InvalidArgumentException("Provider cost mapping [{$provider}] is configured more than once.");
            }

            $normalized[$provider] = ['path' => trim($path), 'currency' => strtoupper($currency)];
        }

        $this->providers = $normalized;
    }

    /** @param array<string, mixed>|object $response */
    public function extract(array|object $response, string $provider): ?Money
    {
        $definition = $this->definition($provider);

        if ($definition === null) {
            return null;
        }

        $steps = data_get($response, 'steps');

        if ($steps instanceof Enumerable) {
            $steps = $steps->all();
        }

        if (is_iterable($steps)) {
            $costs = [];
            $stepCount = 0;

            foreach ($steps as $step) {
                $stepCount++;
                $cost = $this->extractRaw(data_get($step, 'raw'), $definition['path']);

                if ($cost === null) {
                    return null;
                }

                $costs[] = new Money($cost, $definition['currency']);
            }

            if ($stepCount > 0) {
                return array_reduce(
                    $costs,
                    static fn (?Money $total, Money $cost): Money => $total?->plus($cost) ?? $cost,
                );
            }
        }

        $cost = $this->extractRaw(data_get($response, 'raw'), $definition['path']);

        return $cost !== null ? new Money($cost, $definition['currency']) : null;
    }

    /** @return array{path: string, currency: string}|null */
    private function definition(string $provider): ?array
    {
        return $this->providers[strtolower(trim($provider))] ?? null;
    }

    private function extractRaw(mixed $raw, string $path): string|int|null
    {
        if (! is_object($raw) || ! is_callable([$raw, 'json'])) {
            return null;
        }

        try {
            $payload = $raw->json();
        } catch (Throwable) {
            return null;
        }

        if (! is_array($payload)) {
            return null;
        }

        return $this->decimal(data_get($payload, $path));
    }

    private function decimal(mixed $value): string|int|null
    {
        if (is_int($value)) {
            return $value >= 0 ? $value : null;
        }

        if (is_float($value)) {
            if (! is_finite($value) || $value < 0) {
                return null;
            }

            $previousPrecision = ini_set('serialize_precision', '-1');

            try {
                $encoded = json_encode($value, JSON_PRESERVE_ZERO_FRACTION);
            } finally {
                if ($previousPrecision !== false) {
                    ini_set('serialize_precision', $previousPrecision);
                }
            }

            $value = $encoded;
        }

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        if (strlen($value) > 64
            || ! preg_match('/^(?:0|[1-9]\d*)(?:\.\d+)?(?:[eE][+-]?\d+)?$/', $value)
            || preg_match('/[eE]([+-]?\d+)$/', $value, $matches) && abs((int) $matches[1]) > 1024) {
            return null;
        }

        try {
            return BigDecimal::of($value)->isNegative() ? null : $value;
        } catch (Throwable) {
            return null;
        }
    }
}
