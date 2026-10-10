<?php

declare(strict_types=1);

namespace Jkudish\LaravelAiPricing\Adapters;

use InvalidArgumentException;
use Jkudish\LaravelAiPricing\ValueObjects\ModelIdentity;
use Jkudish\LaravelAiPricing\ValueObjects\Money;
use Jkudish\LaravelAiPricing\ValueObjects\PricingObservation;
use Jkudish\LaravelAiPricing\ValueObjects\Usage;
use Override;

class NormalizedObservationAdapter implements ObservationAdapter
{
    /** @param array<string, mixed>|object $value */
    #[Override]
    public function adapt(array|object $value): PricingObservation
    {
        $data = $this->record(is_array($value) ? $value : get_object_vars($value));
        $effective = $this->pair($data, ['effective_provider', 'effectiveProvider'], ['effective_model', 'effectiveModel'], 'effective');
        $explicit = $this->pair($data, ['provider'], ['model'], 'explicit');
        $requested = $this->pair($data, ['requested_provider', 'requestedProvider'], ['requested_model', 'requestedModel'], 'requested');
        $identity = $effective ?? $explicit ?? $requested;
        $usage = $data['usage'] ?? $data;

        if ($identity === null || (! is_array($usage) && ! is_object($usage))) {
            throw new InvalidArgumentException('An observation requires provider, model, and usage.');
        }

        $usage = $this->record(is_array($usage) ? $usage : get_object_vars($usage));
        $units = $this->normalizeUsage($usage, $identity, $this->webSearchEngine($data));
        $cost = $data['cost'] ?? $data['provider_cost'] ?? null;
        $currency = is_string($data['currency'] ?? null) ? $data['currency'] : 'USD';

        if ($cost !== null && ! is_string($cost) && ! is_int($cost)) {
            throw new InvalidArgumentException('Provider-reported cost must be an integer or decimal string; binary floats are not authoritative.');
        }

        return new PricingObservation(
            identity: $identity,
            usage: new Usage($units),
            providerReportedCost: is_string($cost) || is_int($cost) ? new Money($cost, $currency) : null,
            requestedIdentity: $requested,
        );
    }

    /** @param array<string, mixed> $data
     * @return array<string, string|int>
     */
    private function normalizeUsage(array $data, ModelIdentity $identity, ?string $webSearchEngine): array
    {
        $aliases = [
            'input_tokens' => ['input_tokens', 'prompt_tokens', 'inputTokens', 'promptTokens'],
            'output_tokens' => ['output_tokens', 'completion_tokens', 'outputTokens', 'completionTokens'],
            'cached_input_tokens' => ['cached_input_tokens', 'cache_read_input_tokens', 'cachedInputTokens', 'cacheReadInputTokens'],
            'cache_write_input_tokens' => [
                'cache_write_input_tokens',
                'cache_creation_input_tokens',
                'cacheWriteInputTokens',
                'cacheCreationInputTokens',
            ],
            'reasoning_tokens' => ['reasoning_tokens', 'reasoning_output_tokens', 'reasoningTokens', 'reasoningOutputTokens'],
            'images' => ['images', 'image_count'],
            'audio_seconds' => ['audio_seconds', 'audioSeconds'],
        ];
        $knownKeys = [
            ...array_merge(...array_values($aliases)),
            'total_tokens',
            'totalTokens',
        ];
        $units = [];

        foreach ($aliases as $unit => $keys) {
            foreach ($keys as $key) {
                $value = $data[$key] ?? null;

                if (is_int($value) || (is_string($value) && is_numeric($value))) {
                    $units[$unit] = $value;

                    break;
                }
            }
        }

        foreach ($data as $unit => $value) {
            if (! in_array($unit, $knownKeys, true)
                && ! array_key_exists($unit, $units)
                && (is_int($value) || (is_string($value) && is_numeric($value)))) {
                $units[$unit] = $value;
            }
        }

        return [...$units, ...$this->cacheWriteTtlUnits($data, $units), ...$this->webSearchUnits($data, $units, $identity, $webSearchEngine)];
    }

    /**
     * Map the server-side web search count Anthropic and OpenRouter report.
     *
     * Both report usage.server_tool_use.web_search_requests. Anthropic counts
     * its native search, which maps to web_searches. OpenRouter counts every
     * engine it ran (native, Exa, Firecrawl, Parallel or Perplexity, each
     * billed differently) and does not say which, so its count maps to the
     * distinct, unpriced openrouter_web_searches unit and keeps the quote
     * partial. Only a caller that declares the native engine
     * (web_search_engine: native) gets web_searches for an OpenRouter count.
     * An explicit flat web_searches or openrouter_web_searches unit wins.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, string|int>  $units
     * @return array<string, string|int>
     */
    private function webSearchUnits(array $data, array $units, ModelIdentity $identity, ?string $webSearchEngine): array
    {
        $serverTools = $data['server_tool_use'] ?? null;

        if (is_object($serverTools)) {
            $serverTools = get_object_vars($serverTools);
        }

        $value = is_array($serverTools) ? ($serverTools['web_search_requests'] ?? null) : null;

        if (array_key_exists('web_searches', $units)
            || array_key_exists('openrouter_web_searches', $units)
            || (! is_int($value) && ! (is_string($value) && is_numeric($value)))) {
            return [];
        }

        $native = strtolower(trim($identity->provider)) !== 'openrouter' || $webSearchEngine === 'native';

        return [$native ? 'web_searches' : 'openrouter_web_searches' => $value];
    }

    /**
     * Read the search engine the caller declares its OpenRouter request ran.
     *
     * @param  array<string, mixed>  $data
     */
    private function webSearchEngine(array $data): ?string
    {
        $engine = $data['web_search_engine'] ?? $data['webSearchEngine'] ?? null;

        if ($engine !== null && (! is_string($engine) || trim($engine) === '')) {
            throw new InvalidArgumentException('The web search engine must be a non-empty string.');
        }

        return $engine === null ? null : strtolower(trim($engine));
    }

    /**
     * Map Anthropic's nested TTL breakdown of cache-written input tokens.
     *
     * Anthropic reports cache_creation_input_tokens as the aggregate and
     * usage.cache_creation.ephemeral_5m_input_tokens and
     * ephemeral_1h_input_tokens as its split by cache TTL. The split maps to
     * the cache_write_input_tokens_5m and cache_write_input_tokens_1h units
     * alongside the unchanged aggregate; the cost calculator treats them as
     * one partitioned family, so the split never bills on top of the
     * aggregate. Explicit flat TTL units already on the payload win.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, string|int>  $units
     * @return array<string, string|int>
     */
    private function cacheWriteTtlUnits(array $data, array $units): array
    {
        $breakdown = $data['cache_creation'] ?? null;

        if (is_object($breakdown)) {
            $breakdown = get_object_vars($breakdown);
        }

        if (! is_array($breakdown)) {
            return [];
        }

        $mapped = [];

        foreach (['cache_write_input_tokens_5m' => 'ephemeral_5m_input_tokens', 'cache_write_input_tokens_1h' => 'ephemeral_1h_input_tokens'] as $unit => $key) {
            $value = $breakdown[$key] ?? null;

            if (! array_key_exists($unit, $units) && (is_int($value) || (is_string($value) && is_numeric($value)))) {
                $mapped[$unit] = $value;
            }
        }

        return $mapped;
    }

    /** @param array<string, mixed> $data
     * @param  list<string>  $keys
     */
    private function string(array $data, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (is_string($data[$key] ?? null) && trim($data[$key]) !== '') {
                return $data[$key];
            }
        }

        return null;
    }

    /** @param array<string, mixed> $data
     * @param  list<string>  $providerKeys
     * @param  list<string>  $modelKeys
     */
    private function pair(array $data, array $providerKeys, array $modelKeys, string $label): ?ModelIdentity
    {
        $provider = $this->string($data, $providerKeys);
        $model = $this->string($data, $modelKeys);

        if (($provider === null) !== ($model === null)) {
            throw new InvalidArgumentException("The {$label} provider and model must be supplied together.");
        }

        return $provider !== null && $model !== null ? new ModelIdentity($provider, $model) : null;
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
}
