<?php

declare(strict_types=1);

namespace Jkudish\LaravelAiPricing\Adapters;

use Illuminate\Support\Enumerable;
use Jkudish\LaravelAiPricing\ValueObjects\Money;
use Throwable;

final class LaravelAiProviderCostExtractor
{
    /** @param array<string, array<string, mixed>> $providers */
    public function __construct(
        private readonly array $providers = [
            'openrouter' => ['path' => 'usage.cost', 'currency' => 'USD'],
        ],
    ) {}

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
        $definition = $this->providers[strtolower($provider)] ?? null;

        if (! is_array($definition) || ! is_string($definition['path'] ?? null)) {
            return null;
        }

        $currency = $definition['currency'] ?? 'USD';

        return is_string($currency)
            ? ['path' => $definition['path'], 'currency' => $currency]
            : null;
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

        $value = data_get($payload, $path);

        if (is_string($value) || is_int($value)) {
            return $value;
        }

        if (! is_float($value) || ! is_finite($value)) {
            return null;
        }

        $encoded = json_encode($value, JSON_PRESERVE_ZERO_FRACTION);

        return is_string($encoded) ? $encoded : null;
    }
}
