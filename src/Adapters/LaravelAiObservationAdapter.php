<?php

declare(strict_types=1);

namespace Jkudish\LaravelAiPricing\Adapters;

use InvalidArgumentException;
use Jkudish\LaravelAiPricing\ValueObjects\PricingObservation;
use Override;

final class LaravelAiObservationAdapter implements ObservationAdapter
{
    private const array INCLUSIVE_DRIVERS = ['openrouter', 'groq', 'openai-compatible', 'deepseek', 'mistral'];

    /** @param array<string, string> $providerDrivers */
    public function __construct(
        private readonly NormalizedObservationAdapter $normalized = new NormalizedObservationAdapter,
        private readonly array $providerDrivers = [],
        private readonly LaravelAiProviderCostExtractor $providerCosts = new LaravelAiProviderCostExtractor,
    ) {
        foreach ($this->providerDrivers as $provider => $driver) {
            if (trim($provider) === '' || trim($driver) === '') {
                throw new InvalidArgumentException('Laravel AI provider driver mappings require non-empty provider and driver names.');
            }
        }
    }

    /** @param array<string, mixed>|object $value */
    #[Override]
    public function adapt(array|object $value): PricingObservation
    {
        $data = $this->record(is_array($value) ? $value : $this->objectData($value));

        if (is_object($data['meta'] ?? null)) {
            $data = $this->record([...get_object_vars($data['meta']), ...$data]);
        } elseif (is_array($data['meta'] ?? null)) {
            $data = $this->record([...$data['meta'], ...$data]);
        }

        if (! isset($data['usage'])) {
            throw new InvalidArgumentException('Laravel AI observation does not expose usage metadata.');
        }

        $provider = $data['effectiveProvider'] ?? $data['effective_provider'] ?? $data['provider'] ?? null;

        if (! isset($data['cost']) && ! isset($data['provider_cost']) && is_string($provider)) {
            $providerCost = $this->providerCosts->extract($data, $provider);

            if ($providerCost !== null) {
                $data['cost'] = (string) $providerCost->amount;
                $data['currency'] = $providerCost->currency;
            }
        }

        $usage = is_object($data['usage']) ? get_object_vars($data['usage']) : $data['usage'];

        if (is_array($usage)) {
            $inclusive = $this->usesInclusivePromptTokens($data, $provider);
            $prompt = $usage['promptTokens'] ?? $usage['inputTokens'] ?? $usage['prompt_tokens'] ?? $usage['input_tokens'] ?? 0;
            $read = $usage['cacheReadInputTokens'] ?? $usage['cache_read_input_tokens'] ?? 0;
            $write = $usage['cacheWriteInputTokens'] ?? $usage['cache_write_input_tokens'] ?? 0;

            if (is_int($prompt) && is_int($read) && is_int($write)) {
                $uncached = $inclusive
                    ? max(0, $prompt - $read - $write)
                    : $prompt;
                $usage['input_tokens'] = $uncached;
                $usage['cached_input_tokens'] = $read;
                $usage['cache_write_input_tokens'] = $write;
                unset($usage['promptTokens'], $usage['inputTokens'], $usage['prompt_tokens'], $usage['cacheReadInputTokens'], $usage['cacheWriteInputTokens']);
                $data['usage'] = $usage;
            }
        }

        return $this->normalized->adapt($this->record($data));
    }

    /** @param array<string, mixed> $data */
    private function usesInclusivePromptTokens(array $data, mixed $provider): bool
    {
        $semantic = $data['inputTokenSemantic'] ?? $data['input_token_semantic'] ?? null;

        if ($semantic !== null) {
            if (! is_string($semantic) || ! in_array(strtolower($semantic), ['inclusive', 'exclusive'], true)) {
                throw new InvalidArgumentException('Laravel AI input token semantic must be either [inclusive] or [exclusive].');
            }

            return strtolower($semantic) === 'inclusive';
        }

        $driver = $data['driver'] ?? $data['provider_driver'] ?? $data['providerDriver'] ?? null;

        if ($driver !== null && (! is_string($driver) || trim($driver) === '')) {
            throw new InvalidArgumentException('Laravel AI provider driver must be a non-empty string.');
        }

        if ($driver === null && is_string($provider)) {
            $driver = $this->mappedDriver($provider);
        }

        $usageProvider = is_string($driver) ? $driver : $provider;

        return is_string($usageProvider) && in_array(strtolower($usageProvider), self::INCLUSIVE_DRIVERS, true);
    }

    private function mappedDriver(string $provider): ?string
    {
        foreach ($this->providerDrivers as $configuredProvider => $driver) {
            if (strtolower($configuredProvider) === strtolower($provider)) {
                return $driver;
            }
        }

        return null;
    }

    /** @param array<mixed> $data
     * @return array<string, mixed>
     */
    private function record(array $data): array
    {
        $record = [];

        foreach ($data as $key => $value) {
            if (is_string($key)) {
                $record[$key] = $value;
            }
        }

        return $record;
    }

    /** @return array<string, mixed> */
    private function objectData(object $value): array
    {
        return $this->record(get_object_vars($value));
    }
}
