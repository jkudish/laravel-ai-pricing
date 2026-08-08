<?php

declare(strict_types=1);

namespace Jkudish\LaravelAiPricing\Sources;

use Brick\Math\BigDecimal;
use DateTimeImmutable;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Client\Factory as HttpFactory;
use Jkudish\LaravelAiPricing\Contracts\PricingCatalog;
use Jkudish\LaravelAiPricing\Enums\PricingSource;
use Jkudish\LaravelAiPricing\Support\Endpoint;
use Jkudish\LaravelAiPricing\ValueObjects\ModelIdentity;
use Jkudish\LaravelAiPricing\ValueObjects\PriceDefinition;
use Jkudish\LaravelAiPricing\ValueObjects\Rate;
use Override;
use Throwable;
use UnexpectedValueException;

final class PortkeyPricingSource implements PricingCatalog
{
    private const array RATE_MAPPING = [
        'request_token' => 'input_tokens',
        'response_token' => 'output_tokens',
        'cache_read_input_token' => 'cached_input_tokens',
        'cache_write_input_token' => 'cache_write_input_tokens',
        'request_audio_token' => 'input_audio_tokens',
        'response_audio_token' => 'output_audio_tokens',
    ];

    /** @var array<string, DateTimeImmutable|null> */
    private array $retrievedAt = [];

    /** @var array<string, string|null> */
    private array $sourceReferences = [];

    /** @param list<string> $providers */
    public function __construct(
        private readonly HttpFactory $http,
        private readonly CacheRepository $cache,
        private readonly string $endpoint,
        private readonly int $ttlSeconds,
        private readonly bool $offline,
        private readonly array $providers = [],
    ) {}

    #[Override]
    public function find(ModelIdentity $identity): ?PriceDefinition
    {
        $catalog = $this->catalog($identity->provider);
        $model = $catalog[$identity->model] ?? null;

        if (! is_array($model)) {
            return null;
        }

        return $this->definition($identity, $this->record($model));
    }

    #[Override]
    public function sync(): int
    {
        $count = 0;

        foreach ($this->providers as $provider) {
            if ($this->offline) {
                $count += count($this->lastKnownGoodCatalog($provider));

                continue;
            }

            $catalog = $this->retrieve($provider);
            $this->store($provider, $catalog);
            $count += count($catalog);
        }

        return $count;
    }

    public function hasConfiguredProviders(): bool
    {
        return $this->providers !== [];
    }

    /** @return array<string, mixed> */
    private function catalog(string $provider): array
    {
        $cached = $this->offline ? $this->lastKnownGoodCatalog($provider) : $this->cachedCatalog($provider);

        if ($cached !== [] || $this->offline) {
            return $cached;
        }

        try {
            $catalog = $this->retrieve($provider);
            $this->store($provider, $catalog);

            return $catalog;
        } catch (Throwable) {
            return $this->lastKnownGoodCatalog($provider);
        }
    }

    /** @return array<string, mixed> */
    private function retrieve(string $provider): array
    {
        $provider = $this->normalizedProvider($provider);
        $url = $this->sourceUrl($provider);
        $payload = $this->http->acceptJson()->get($url)->throw()->json();

        $catalog = is_array($payload) ? $this->record($payload) : [];

        if (! $this->hasUsableModel($catalog)) {
            throw new UnexpectedValueException('Portkey returned an empty or unusable pricing catalog.');
        }

        $this->retrievedAt[$provider] = new DateTimeImmutable;
        $this->sourceReferences[$provider] = Endpoint::provenance($url);

        return $catalog;
    }

    /** @return array<string, mixed> */
    private function cachedCatalog(string $provider): array
    {
        $provider = $this->normalizedProvider($provider);
        $payload = $this->cache->get($this->cacheKey($provider), []);

        if (! is_array($payload)) {
            return [];
        }

        $catalog = $payload['catalog'] ?? $payload;

        if (array_key_exists('catalog', $payload)) {
            $this->recordProvenance($provider, $payload);
        }

        return is_array($catalog) ? $this->record($catalog) : [];
    }

    /** @param array<string, mixed> $catalog */
    private function store(string $provider, array $catalog): void
    {
        $provider = $this->normalizedProvider($provider);
        $payload = [
            'retrieved_at' => ($this->retrievedAt[$provider] ?? new DateTimeImmutable)->format(DATE_ATOM),
            'source_reference' => $this->sourceReferences[$provider] ?? Endpoint::provenance($this->sourceUrl($provider)),
            'catalog' => $catalog,
        ];
        $this->cache->put($this->cacheKey($provider), $payload, $this->ttlSeconds);
        $this->cache->forever($this->cacheKey($provider).':lkg', $payload);
    }

    /** @return array<string, mixed> */
    private function lastKnownGoodCatalog(string $provider): array
    {
        $provider = $this->normalizedProvider($provider);
        $payload = $this->cache->get($this->cacheKey($provider).':lkg', []);
        $catalog = is_array($payload) ? ($payload['catalog'] ?? null) : null;

        if (is_array($payload)) {
            $this->recordProvenance($provider, $payload);
        }

        return is_array($catalog) ? $this->record($catalog) : [];
    }

    /** @param array<string, mixed> $model */
    private function definition(ModelIdentity $identity, array $model): ?PriceDefinition
    {
        $pricingConfig = $model['pricing_config'] ?? null;

        if (! is_array($pricingConfig)) {
            return null;
        }

        $payAsYouGo = $pricingConfig['pay_as_you_go'] ?? null;

        if (! is_array($payAsYouGo)) {
            return null;
        }

        $currency = is_string($pricingConfig['currency'] ?? null) ? $pricingConfig['currency'] : 'USD';
        $rates = $this->rates($payAsYouGo, $currency);

        if ($rates === []) {
            return null;
        }

        return new PriceDefinition(
            identity: $identity,
            rates: $rates,
            source: PricingSource::Portkey,
            retrievedAt: $this->retrievedAt[$this->normalizedProvider($identity->provider)] ?? null,
            sourceReference: $this->sourceReferences[$this->normalizedProvider($identity->provider)] ?? null,
        );
    }

    private function centsPerUnitRate(string $unit, string|int $cents, string $currency): Rate
    {
        $dollars = BigDecimal::of((string) $cents)->dividedByExact(100);

        return new Rate($unit, $dollars, currency: $currency);
    }

    /** @param array<int|string, mixed> $payload */
    private function recordProvenance(string $provider, array $payload): void
    {
        $retrievedAt = $payload['retrieved_at'] ?? null;
        $sourceReference = $payload['source_reference'] ?? null;

        $this->retrievedAt[$provider] = is_string($retrievedAt) ? new DateTimeImmutable($retrievedAt) : null;
        $this->sourceReferences[$provider] = is_string($sourceReference) ? Endpoint::provenance($sourceReference) : null;
    }

    private function cacheKey(string $provider): string
    {
        $identity = $this->endpointIdentity().'|'.$this->normalizedProvider($provider);

        return 'ai-pricing:catalog:portkey:v2:'.hash('sha256', $identity);
    }

    private function sourceUrl(string $provider): string
    {
        return str_replace('{provider}', rawurlencode($this->normalizedProvider($provider)), trim($this->endpoint));
    }

    private function endpointIdentity(): string
    {
        return Endpoint::identity($this->endpoint);
    }

    private function normalizedProvider(string $provider): string
    {
        return strtolower(trim($provider));
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

    /** @param array<string, mixed> $catalog */
    private function hasUsableModel(array $catalog): bool
    {
        $usable = false;

        foreach ($catalog as $modelName => $model) {
            $config = is_array($model) ? ($model['pricing_config'] ?? null) : null;
            $payAsYouGo = is_array($config) ? ($config['pay_as_you_go'] ?? null) : null;

            if (trim($modelName) === '' || ! is_array($payAsYouGo)) {
                continue;
            }

            $currency = is_string($config['currency'] ?? null) ? $config['currency'] : 'USD';

            if ($this->rates($payAsYouGo, $currency) !== []) {
                $usable = true;
            }
        }

        return $usable;
    }

    /** @param array<int|string, mixed> $payAsYouGo
     * @return array<string, Rate>
     */
    private function rates(array $payAsYouGo, string $currency): array
    {
        $rates = [];

        foreach (self::RATE_MAPPING as $field => $unit) {
            if (! array_key_exists($field, $payAsYouGo)) {
                continue;
            }

            $rate = $payAsYouGo[$field];

            if (! is_array($rate) || ! array_key_exists('price', $rate)) {
                throw new UnexpectedValueException("Portkey pricing field [{$field}] must contain a price.");
            }

            $rates[$unit] = $this->validatedRate($unit, $rate['price'], $currency, $field);
        }

        if (array_key_exists('additional_units', $payAsYouGo)) {
            $additional = $payAsYouGo['additional_units'];

            if (! is_array($additional)) {
                throw new UnexpectedValueException('Portkey additional_units must be an associative array.');
            }

            foreach ($additional as $unit => $rate) {
                if (! is_string($unit) || trim($unit) === '' || ! is_array($rate) || ! array_key_exists('price', $rate)) {
                    throw new UnexpectedValueException('Each Portkey additional unit must have a non-empty name and price.');
                }

                $rates[$unit] = $this->validatedRate($unit, $rate['price'], $currency, "additional_units.{$unit}");
            }
        }

        return $rates;
    }

    private function validatedRate(string $unit, mixed $price, string $currency, string $field): Rate
    {
        if (! is_string($price) && ! is_int($price)) {
            throw new UnexpectedValueException("Portkey pricing field [{$field}] must be an integer or decimal string.");
        }

        try {
            return $this->centsPerUnitRate($unit, $price, $currency);
        } catch (Throwable $exception) {
            throw new UnexpectedValueException("Portkey pricing field [{$field}] is not a finite, non-negative decimal.", previous: $exception);
        }
    }
}
