<?php

declare(strict_types=1);

namespace Jkudish\LaravelAiPricing\Sources;

use DateTimeImmutable;
use Jkudish\LaravelAiPricing\Enums\PricingSource;
use Jkudish\LaravelAiPricing\ValueObjects\ModelIdentity;
use Jkudish\LaravelAiPricing\ValueObjects\PriceDefinition;
use Jkudish\LaravelAiPricing\ValueObjects\Rate;
use Override;
use UnexpectedValueException;

final class OpenRouterPricingSource extends AbstractRemotePricingSource
{
    private const array RATE_MAPPING = [
        'prompt' => 'input_tokens',
        'completion' => 'output_tokens',
        'input_cache_read' => 'cached_input_tokens',
        'input_cache_write' => 'cache_write_input_tokens',
        'image' => 'images',
        'audio' => 'audio_seconds',
        'request' => 'requests',
        'web_search' => 'web_searches',
        'internal_reasoning' => 'reasoning_tokens',
    ];

    /** @return array<int|string, mixed> */
    #[Override]
    protected function retrieve(): array
    {
        $payload = $this->http->acceptJson()->get($this->endpoint)->throw()->json();

        if (! is_array($payload)) {
            throw new UnexpectedValueException('OpenRouter returned an empty or unusable pricing catalog.');
        }

        $data = $payload['data'] ?? null;

        if (! is_array($data) || ! $this->hasUsableModel($data)) {
            throw new UnexpectedValueException('OpenRouter returned an empty or unusable pricing catalog.');
        }

        return $data;
    }

    /** @param array<int|string, mixed> $catalog
     * @return array<string, mixed>|null
     */
    #[Override]
    protected function findModel(array $catalog, ModelIdentity $identity): ?array
    {
        foreach ($catalog as $model) {
            if (is_array($model) && ($model['id'] ?? null) === $identity->model) {
                return $this->record($model);
            }
        }

        return null;
    }

    /** @param array<string, mixed> $model */
    #[Override]
    protected function definition(ModelIdentity $identity, array $model): ?PriceDefinition
    {
        $pricing = $model['pricing'] ?? null;

        if (! is_array($pricing)) {
            return null;
        }

        $rates = $this->rates($pricing);

        if ($rates === []) {
            return null;
        }

        return new PriceDefinition(
            identity: $identity,
            rates: $rates,
            source: PricingSource::ProviderNative,
            retrievedAt: $this->retrievedAt ?? new DateTimeImmutable,
            sourceReference: $this->endpoint,
        );
    }

    #[Override]
    protected function cacheKey(): string
    {
        return 'ai-pricing:catalog:openrouter:v1';
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

    /** @param array<int|string, mixed> $catalog */
    private function hasUsableModel(array $catalog): bool
    {
        $usable = false;

        foreach ($catalog as $model) {
            $pricing = is_array($model) ? ($model['pricing'] ?? null) : null;

            if (is_array($pricing)) {
                $rates = $this->rates($pricing);

                if (is_string($model['id'] ?? null) && trim($model['id']) !== '' && $rates !== []) {
                    $usable = true;
                }
            }
        }

        return $usable;
    }

    /** @param array<int|string, mixed> $pricing
     * @return array<string, Rate>
     */
    private function rates(array $pricing): array
    {
        $rates = [];

        foreach (self::RATE_MAPPING as $field => $unit) {
            if (! array_key_exists($field, $pricing)) {
                continue;
            }

            $amount = $pricing[$field];

            if (! is_string($amount) && ! is_int($amount)) {
                throw new UnexpectedValueException("OpenRouter pricing field [{$field}] must be an integer or decimal string.");
            }

            try {
                $rates[$unit] = new Rate($unit, $amount);
            } catch (\Throwable $exception) {
                throw new UnexpectedValueException("OpenRouter pricing field [{$field}] is not a finite, non-negative decimal.", previous: $exception);
            }
        }

        return $rates;
    }
}
