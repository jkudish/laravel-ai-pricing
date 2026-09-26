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

        if (! isset($data['usage']) && array_key_exists('tokens', $data)) {
            if (! is_int($data['tokens']) || $data['tokens'] < 0) {
                throw new InvalidArgumentException('Laravel AI embedding token usage must be a non-negative integer.');
            }

            $data['usage'] = ['input_tokens' => $data['tokens']];
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
            $inclusive = $this->usesInclusivePromptTokens($data, $provider, $this->reportsInclusiveInputTokens($usage));
            $prompt = $usage['promptTokens'] ?? $usage['inputTokens'] ?? $usage['prompt_tokens'] ?? $usage['input_tokens'] ?? 0;
            $read = $usage['cacheReadInputTokens'] ?? $usage['cache_read_input_tokens'] ?? 0;
            $write = $usage['cacheWriteInputTokens'] ?? $usage['cache_write_input_tokens'] ?? $usage['cache_creation_input_tokens'] ?? $usage['cacheCreationInputTokens'] ?? 0;

            if (is_int($prompt) && is_int($read) && is_int($write)) {
                $uncached = $inclusive
                    ? max(0, $prompt - $read - $write)
                    : $prompt;
                $usage['input_tokens'] = $uncached;
                $usage['cached_input_tokens'] = $read;
                $usage['cache_write_input_tokens'] = $write;
                unset(
                    $usage['promptTokens'],
                    $usage['inputTokens'],
                    $usage['prompt_tokens'],
                    $usage['cacheReadInputTokens'],
                    $usage['cacheWriteInputTokens'],
                    $usage['cache_creation_input_tokens'],
                    $usage['cacheCreationInputTokens'],
                );
                $data['usage'] = $usage;
            }
        }

        return $this->normalized->adapt($this->record($data));
    }

    /**
     * Resolve whether the reported input-token count includes cached and cache-written tokens.
     *
     * Precedence:
     * 1. An explicit [inclusive] or [exclusive] input token semantic, for callers that
     *    know their payload better than any heuristic (for example raw Bedrock responses
     *    keyed with camelCase [inputTokens] that excludes cache reads and writes).
     * 2. The laravel/ai 1.0+ usage dialect, which reports [inputTokens] or [input_tokens]
     *    as a total that always includes cached and cache-written input tokens.
     * 3. The historical driver heuristic for the laravel/ai 0.x dialect, whose
     *    [promptTokens] inclusivity depends on the driver that produced the observation.
     *
     * @param  array<string, mixed>  $data
     */
    private function usesInclusivePromptTokens(array $data, mixed $provider, bool $inclusiveDialect): bool
    {
        $semantic = $data['inputTokenSemantic'] ?? $data['input_token_semantic'] ?? null;

        if ($semantic !== null) {
            if (! is_string($semantic) || ! in_array(strtolower($semantic), ['inclusive', 'exclusive'], true)) {
                throw new InvalidArgumentException('Laravel AI input token semantic must be either [inclusive] or [exclusive].');
            }

            return strtolower($semantic) === 'inclusive';
        }

        if ($inclusiveDialect) {
            return true;
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

    /**
     * Determine whether the usage payload speaks the laravel/ai 1.0+ dialect.
     *
     * laravel/ai 1.0 renamed the usage keys and made the input count a total that
     * always includes cached and cache-written tokens. The dialect is detected by
     * the presence of the renamed input keys without any legacy prompt keys or raw
     * Anthropic cache-creation keys; payloads that report both input shapes, or
     * that use provider-native naming, keep their historical interpretation, and
     * an explicit input token semantic always wins.
     *
     * @param  array<mixed, mixed>  $usage
     */
    private function reportsInclusiveInputTokens(array $usage): bool
    {
        if (array_key_exists('promptTokens', $usage) || array_key_exists('prompt_tokens', $usage)) {
            return false;
        }

        if (array_key_exists('cache_creation_input_tokens', $usage) || array_key_exists('cacheCreationInputTokens', $usage)) {
            return false;
        }

        return array_key_exists('inputTokens', $usage) || array_key_exists('input_tokens', $usage);
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
