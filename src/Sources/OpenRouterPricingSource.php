<?php

declare(strict_types=1);

namespace Jkudish\LaravelAiPricing\Sources;

use DateTimeImmutable;
use Jkudish\LaravelAiPricing\Contracts\FallbackPolicy;
use Jkudish\LaravelAiPricing\Enums\PricingSource;
use Jkudish\LaravelAiPricing\ValueObjects\ModelIdentity;
use Jkudish\LaravelAiPricing\ValueObjects\PriceDefinition;
use Jkudish\LaravelAiPricing\ValueObjects\Rate;
use Override;
use UnexpectedValueException;

final class OpenRouterPricingSource extends AbstractRemotePricingSource implements FallbackPolicy
{
    /**
     * Catalog pricing fields and the usage units they price.
     *
     * OpenRouter's image and audio fields are prices per input token of that
     * modality, not per image or per second of audio, so they have no unit
     * here and stay unpriced rather than pricing an image or audio count.
     */
    private const array RATE_MAPPING = [
        'prompt' => 'input_tokens',
        'completion' => 'output_tokens',
        'input_cache_read' => 'cached_input_tokens',
        'input_cache_write' => 'cache_write_input_tokens',
        'input_cache_write_1h' => 'cache_write_input_tokens_1h',
        'request' => 'requests',
        'web_search' => 'web_searches',
        'internal_reasoning' => 'reasoning_tokens',
    ];

    private const array CACHE_WRITE_FIELDS = ['input_cache_write', 'input_cache_write_1h'];

    /**
     * Identities whose latest lookup found pricing overrides this source cannot apply.
     *
     * @var array<string, true>
     */
    private array $refusedIdentities = [];

    /** @return array<int|string, mixed> */
    #[Override]
    protected function retrieve(): array
    {
        $payload = $this->http->acceptJson()->get($this->endpoint)->throw()->json();

        if (! is_array($payload)) {
            throw new UnexpectedValueException('OpenRouter returned an empty or unusable pricing catalog.');
        }

        $data = $payload['data'] ?? null;

        if (! is_array($data)) {
            throw new UnexpectedValueException('OpenRouter returned an empty or unusable pricing catalog.');
        }

        $catalog = $this->usableModels($data);

        if ($catalog === []) {
            throw new UnexpectedValueException('OpenRouter returned an empty or unusable pricing catalog.');
        }

        return $catalog;
    }

    #[Override]
    protected function supportsIdentity(ModelIdentity $identity): bool
    {
        return strtolower(trim($identity->provider)) === 'openrouter';
    }

    /** @param array<int|string, mixed> $catalog
     * @return array<string, mixed>|null
     */
    #[Override]
    protected function findModel(array $catalog, ModelIdentity $identity): ?array
    {
        unset($this->refusedIdentities[$identity->key()]);

        foreach ($catalog as $model) {
            if (is_array($model) && ($model['id'] ?? null) === $identity->model) {
                $record = $this->record($model);

                if (is_array($record['pricing'] ?? null) && $this->hasPricingOverrides($record['pricing'])) {
                    $this->refusedIdentities[$identity->key()] = true;
                }

                return $record;
            }
        }

        return null;
    }

    /** @param array<string, mixed> $model */
    #[Override]
    protected function definition(ModelIdentity $identity, array $model): ?PriceDefinition
    {
        $pricing = $model['pricing'] ?? null;

        if (! is_array($pricing) || $this->hasPricingOverrides($pricing)) {
            return null;
        }

        $rates = $this->rates($pricing, $identity->model);

        if ($rates === []) {
            return null;
        }

        return new PriceDefinition(
            identity: $identity,
            rates: $rates,
            source: PricingSource::ProviderNative,
            retrievedAt: $this->retrievedAt ?? new DateTimeImmutable,
            sourceReference: $this->sourceReference,
        );
    }

    /**
     * Keep the fallback catalog away from a model this catalog lists but cannot price.
     *
     * A model with pricing overrides bills by prompt length or time of day,
     * which a catalog lookup cannot see. The fallback catalog would price the
     * same model from a flat headline rate, so it must not price it either.
     * The answer reports the refusal from the identity's latest find(), not
     * a second catalog read, so a cache miss followed by an outage cannot
     * turn the refusal into a fallback.
     */
    #[Override]
    public function allowsFallback(ModelIdentity $identity): bool
    {
        return ! isset($this->refusedIdentities[$identity->key()]);
    }

    #[Override]
    protected function cacheKey(): string
    {
        return 'ai-pricing:catalog:openrouter:v2:'.hash('sha256', $this->endpointIdentity());
    }

    /** @param array<mixed> $value
     * @return array<string, mixed>
     */
    private function record(array $value): array
    {
        $record = [];

        foreach ($value as $key => $item) {
            if (is_string($key)) {
                $record[$key] = $item;
            }
        }

        return $record;
    }

    /** @param array<int|string, mixed> $catalog
     * @return list<array<string, mixed>>
     */
    private function usableModels(array $catalog): array
    {
        $usable = [];

        foreach ($catalog as $model) {
            if (! is_array($model)) {
                continue;
            }

            $pricing = $model['pricing'] ?? null;
            $id = $model['id'] ?? null;

            if (! is_array($pricing) || ! is_string($id) || trim($id) === '') {
                continue;
            }

            // A model with overrides is never priced, but its record must
            // survive so the source can still refuse it, even when its base
            // rates are unusable.
            if ($this->hasPricingOverrides($pricing)) {
                $usable[] = $this->record($model);

                continue;
            }

            try {
                $rates = $this->rates($pricing, $id);
            } catch (\Throwable) {
                continue;
            }

            if ($rates !== []) {
                $usable[] = $this->record($model);
            }
        }

        return $usable;
    }

    /**
     * Whether the catalog prices the model in tiers this source cannot apply.
     *
     * OpenRouter lists prompt-length and time-of-day tiers under
     * pricing.overrides. Any present, non-empty value fails closed, including
     * a malformed one, because pricing from the base tier would understate
     * every request the override covers.
     *
     * @param  array<int|string, mixed>  $pricing
     */
    private function hasPricingOverrides(array $pricing): bool
    {
        return ($pricing['overrides'] ?? null) !== null && $pricing['overrides'] !== [];
    }

    /** @param array<int|string, mixed> $pricing
     * @return array<string, Rate>
     */
    private function rates(array $pricing, string $model): array
    {
        $rates = [];

        foreach (self::RATE_MAPPING as $field => $unit) {
            if (! array_key_exists($field, $pricing)) {
                continue;
            }

            // OpenRouter lists a Google cache write as a few minutes of cache
            // storage, while its caching guide bills the input price on top.
            // Neither is safe, so a Google cache-write count stays missing.
            if (in_array($field, self::CACHE_WRITE_FIELDS, true) && str_starts_with(ltrim($model, '~'), 'google/')) {
                continue;
            }

            // A model that also publishes a 1-hour write rate publishes its
            // 5-minute rate as input_cache_write. OpenRouter usage reports only
            // the aggregate, so pricing that at the cheaper 5-minute rate could
            // understate 1-hour writes; the TTL units leave it missing instead.
            if ($field === 'input_cache_write' && array_key_exists('input_cache_write_1h', $pricing)) {
                $unit = 'cache_write_input_tokens_5m';
            }

            $amount = $pricing[$field];

            if (! is_string($amount) && ! is_int($amount)) {
                throw new UnexpectedValueException("OpenRouter pricing field [{$field}] must be an integer or decimal string.");
            }

            try {
                $rate = new Rate($unit, $amount);
            } catch (\Throwable $exception) {
                throw new UnexpectedValueException("OpenRouter pricing field [{$field}] is not a finite, non-negative decimal.", previous: $exception);
            }

            // A zero internal_reasoning price means the model bills reasoning
            // inside its completion price, not that reasoning is free. The
            // calculator's output-family partition treats a published
            // reasoning_tokens rate of zero as deliberately free reasoning, so
            // a zero here must not become a rate: skipping it leaves the
            // reasoning subset billed at the output rate.
            if ($field === 'internal_reasoning' && $rate->amount->isZero()) {
                continue;
            }

            $rates[$unit] = $rate;
        }

        return $rates;
    }
}
