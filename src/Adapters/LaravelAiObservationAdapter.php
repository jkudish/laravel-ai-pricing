<?php

declare(strict_types=1);

namespace Jkudish\LaravelAiPricing\Adapters;

use Brick\Math\BigDecimal;
use Illuminate\Support\Enumerable;
use InvalidArgumentException;
use Jkudish\LaravelAiPricing\ValueObjects\PricingObservation;
use Override;
use Throwable;

final class LaravelAiObservationAdapter implements ObservationAdapter
{
    private const array INCLUSIVE_DRIVERS = ['openrouter', 'groq', 'openai-compatible', 'deepseek', 'mistral'];

    private const array EXCLUSIVE_REASONING_DRIVERS = ['gemini', 'xai'];

    private const array OUTPUT_ALIASES = ['output_tokens', 'completion_tokens', 'outputTokens', 'completionTokens'];

    private const array REASONING_ALIASES = ['reasoning_tokens', 'reasoning_output_tokens', 'reasoningTokens', 'reasoningOutputTokens'];

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
            $usage = $this->foldExclusiveReasoningTokens($data, $usage, $provider);
            $data['usage'] = $usage;
            $inclusive = $this->usesInclusivePromptTokens($data, $provider, $this->reportsInclusiveInputTokens($usage));
            $prompt = $usage['promptTokens'] ?? $usage['inputTokens'] ?? $usage['prompt_tokens'] ?? $usage['input_tokens'] ?? 0;
            $read = $usage['cacheReadInputTokens'] ?? $usage['cache_read_input_tokens'] ?? 0;
            $reportedWrite = $usage['cacheWriteInputTokens'] ?? $usage['cache_write_input_tokens'] ?? $usage['cache_creation_input_tokens'] ?? $usage['cacheCreationInputTokens'] ?? null;
            $write = $reportedWrite ?? 0;

            if (is_int($prompt) && is_int($read) && is_int($write)) {
                $uncached = $inclusive
                    ? max(0, $prompt - $read - $write)
                    : $prompt;
                $usage['input_tokens'] = $uncached;
                $usage['cached_input_tokens'] = $read;
                unset(
                    $usage['promptTokens'],
                    $usage['inputTokens'],
                    $usage['prompt_tokens'],
                    $usage['cacheReadInputTokens'],
                    $usage['cacheWriteInputTokens'],
                    $usage['cache_creation_input_tokens'],
                    $usage['cacheCreationInputTokens'],
                );

                // An absent aggregate defaults to zero, except next to a TTL
                // split, where a synthetic zero would read as contradictory.
                if ($reportedWrite !== null || ! $this->reportsCacheWriteTtlSplit($usage)) {
                    $usage['cache_write_input_tokens'] = $write;
                }

                $split = $write > 0 && ! $this->reportsCacheWriteTtlSplit($usage)
                    ? $this->rawCacheWriteTtlSplit($data)
                    : null;

                if ($split !== null && $write === $split['ephemeral_5m_input_tokens'] + $split['ephemeral_1h_input_tokens']) {
                    $usage['cache_creation'] = $split;
                }

                $data['usage'] = $usage;
            }
        }

        return $this->normalized->adapt($this->record($data));
    }

    /**
     * Fold an exclusive reasoning count into the reported output count.
     *
     * Most dialects this adapter accepts report the output count inclusive of
     * its reasoning subset — OpenAI, Anthropic and OpenRouter in both the
     * laravel/ai 0.x and 1.0 dialects — which is the semantics the cost
     * calculator assumes when it prices the output token family as a
     * partition. Two 0.x dialects report it exclusive instead: the laravel/ai
     * 0.x Gemini and xAI drivers kept the thought or reasoning count outside
     * the completion count, so for those drivers the fold applies by default.
     * The 1.0 dialect never folds, because its outputTokens already includes
     * reasoning. Callers that know their payload better can pass
     * [reasoning_token_semantic] or [reasoningTokenSemantic] as [inclusive] or
     * [exclusive] to override either default.
     *
     * @param  array<string, mixed>  $data
     * @param  array<mixed, mixed>  $usage
     * @return array<mixed, mixed>
     */
    private function foldExclusiveReasoningTokens(array $data, array $usage, mixed $provider): array
    {
        if (! $this->foldsExclusiveReasoning($data, $usage, $provider)) {
            return $usage;
        }

        $reasoning = $this->numericUsageEntry($usage, self::REASONING_ALIASES);
        $output = $this->numericUsageEntry($usage, self::OUTPUT_ALIASES);

        if ($reasoning === null || ! BigDecimal::of($reasoning[1])->isPositive()) {
            return $usage;
        }

        if ($output === null) {
            throw new InvalidArgumentException('Laravel AI exclusive reasoning tokens require a reported output token count.');
        }

        $total = BigDecimal::of($output[1])->plus(BigDecimal::of($reasoning[1]));

        unset(
            $usage['output_tokens'],
            $usage['completion_tokens'],
            $usage['outputTokens'],
            $usage['completionTokens'],
        );

        $usage['output_tokens'] = (string) $total;

        return $usage;
    }

    /**
     * Resolve whether the payload's reasoning count sits outside its output count.
     *
     * Precedence:
     * 1. An explicit [inclusive] or [exclusive] reasoning token semantic, for
     *    callers that know their payload better than any driver default.
     * 2. The laravel/ai 0.x dialect produced by the Gemini and xAI drivers,
     *    whose completion count excluded the reasoning count. The 1.0 dialect
     *    renamed the keys and folds reasoning into the output total itself,
     *    so it never folds here regardless of driver.
     *
     * @param  array<string, mixed>  $data
     * @param  array<mixed, mixed>  $usage
     */
    private function foldsExclusiveReasoning(array $data, array $usage, mixed $provider): bool
    {
        $semantic = $data['reasoningTokenSemantic'] ?? $data['reasoning_token_semantic'] ?? null;

        if ($semantic !== null) {
            if (! is_string($semantic) || ! in_array(strtolower($semantic), ['inclusive', 'exclusive'], true)) {
                throw new InvalidArgumentException('Laravel AI reasoning token semantic must be either [inclusive] or [exclusive].');
            }

            return strtolower($semantic) === 'exclusive';
        }

        if (! $this->speaksLegacyOutputDialect($usage)) {
            return false;
        }

        $driver = $this->resolvedDriver($data, $provider);
        $usageProvider = is_string($driver) ? $driver : $provider;

        return is_string($usageProvider) && in_array(strtolower($usageProvider), self::EXCLUSIVE_REASONING_DRIVERS, true);
    }

    /**
     * Determine whether the usage payload speaks a legacy output dialect.
     *
     * The laravel/ai 0.x dialect reports prompt or completion keys; the 1.0
     * dialect renamed them to input and output keys, so the exclusive-reasoning
     * driver default can only ever apply to the legacy spellings.
     *
     * @param  array<mixed, mixed>  $usage
     */
    private function speaksLegacyOutputDialect(array $usage): bool
    {
        foreach (['promptTokens', 'prompt_tokens', 'completionTokens', 'completion_tokens'] as $key) {
            if (array_key_exists($key, $usage)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Find the first usage entry the normalized adapter would accept.
     *
     * The alias order and the integer-or-numeric-string predicate mirror
     * NormalizedObservationAdapter exactly, so the fold reads the same value
     * the normalization would have chosen and rewrites it canonically.
     *
     * @param  array<mixed, mixed>  $usage
     * @param  list<string>  $keys
     * @return array{0: string, 1: int|string}|null
     */
    private function numericUsageEntry(array $usage, array $keys): ?array
    {
        foreach ($keys as $key) {
            $value = $usage[$key] ?? null;

            if (is_int($value) || (is_string($value) && is_numeric($value))) {
                return [$key, $value];
            }
        }

        return null;
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

        $driver = $this->resolvedDriver($data, $provider);

        if ($inclusiveDialect) {
            return true;
        }

        $usageProvider = is_string($driver) ? $driver : $provider;

        return is_string($usageProvider) && in_array(strtolower($usageProvider), self::INCLUSIVE_DRIVERS, true);
    }

    /**
     * Resolve the driver name that produced the observation.
     *
     * An explicitly provided driver must be a non-empty string — the check
     * runs before any dialect shortcut so a malformed driver never slips
     * through unvalidated — and an omitted driver falls back to the configured
     * provider-to-driver mapping before the provider name itself.
     *
     * @param  array<string, mixed>  $data
     */
    private function resolvedDriver(array $data, mixed $provider): mixed
    {
        $driver = $data['driver'] ?? $data['provider_driver'] ?? $data['providerDriver'] ?? null;

        if ($driver !== null && (! is_string($driver) || trim($driver) === '')) {
            throw new InvalidArgumentException('Laravel AI provider driver must be a non-empty string.');
        }

        if ($driver === null && is_string($provider)) {
            $driver = $this->mappedDriver($provider);
        }

        return $driver;
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

    /** @param array<mixed, mixed> $usage */
    private function reportsCacheWriteTtlSplit(array $usage): bool
    {
        return array_key_exists('cache_creation', $usage)
            || array_key_exists('cache_write_input_tokens_5m', $usage)
            || array_key_exists('cache_write_input_tokens_1h', $usage);
    }

    /**
     * Recover Anthropic's TTL split of cache writes from the raw provider responses.
     *
     * laravel/ai normalizes Anthropic usage to a single cacheWriteInputTokens
     * aggregate and drops usage.cache_creation, but a non-streamed response
     * keeps the provider's HTTP response on its public raw property. A
     * multi-step response sums every step's raw split, because the top-level
     * raw response only describes the final step. Any step without a readable
     * split, a streamed response, or a serialized response yields null; the
     * caller also discards a split that does not sum to the reported
     * aggregate, so the aggregate is never re-attributed by guesswork.
     *
     * @param  array<string, mixed>  $data
     * @return array{ephemeral_5m_input_tokens: int, ephemeral_1h_input_tokens: int}|null
     */
    private function rawCacheWriteTtlSplit(array $data): ?array
    {
        $steps = data_get($data, 'steps');

        if ($steps instanceof Enumerable) {
            $steps = $steps->all();
        }

        if (is_iterable($steps)) {
            $total = null;

            foreach ($steps as $step) {
                $split = $this->rawResponseTtlSplit(data_get($step, 'raw'));

                if ($split === null) {
                    return null;
                }

                $total = $total === null ? $split : [
                    'ephemeral_5m_input_tokens' => $total['ephemeral_5m_input_tokens'] + $split['ephemeral_5m_input_tokens'],
                    'ephemeral_1h_input_tokens' => $total['ephemeral_1h_input_tokens'] + $split['ephemeral_1h_input_tokens'],
                ];
            }

            if ($total !== null) {
                return $total;
            }
        }

        return $this->rawResponseTtlSplit($data['raw'] ?? null);
    }

    /** @return array{ephemeral_5m_input_tokens: int, ephemeral_1h_input_tokens: int}|null */
    private function rawResponseTtlSplit(mixed $raw): ?array
    {
        if (! is_object($raw) || ! is_callable([$raw, 'json'])) {
            return null;
        }

        try {
            $payload = $raw->json();
        } catch (Throwable) {
            return null;
        }

        $breakdown = is_array($payload) ? data_get($payload, 'usage.cache_creation') : null;

        if (! is_array($breakdown)) {
            return null;
        }

        $fiveMinute = $breakdown['ephemeral_5m_input_tokens'] ?? 0;
        $oneHour = $breakdown['ephemeral_1h_input_tokens'] ?? 0;

        if (! is_int($fiveMinute) || ! is_int($oneHour) || $fiveMinute < 0 || $oneHour < 0) {
            return null;
        }

        return ['ephemeral_5m_input_tokens' => $fiveMinute, 'ephemeral_1h_input_tokens' => $oneHour];
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
