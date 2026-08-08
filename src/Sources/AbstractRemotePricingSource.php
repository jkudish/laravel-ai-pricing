<?php

declare(strict_types=1);

namespace Jkudish\LaravelAiPricing\Sources;

use DateTimeImmutable;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Client\Factory as HttpFactory;
use Jkudish\LaravelAiPricing\Contracts\PricingCatalog;
use Jkudish\LaravelAiPricing\ValueObjects\ModelIdentity;
use Jkudish\LaravelAiPricing\ValueObjects\PriceDefinition;
use Override;
use Throwable;

abstract class AbstractRemotePricingSource implements PricingCatalog
{
    protected ?DateTimeImmutable $retrievedAt = null;

    public function __construct(
        protected HttpFactory $http,
        protected CacheRepository $cache,
        protected string $endpoint,
        protected int $ttlSeconds,
        protected bool $offline,
    ) {}

    #[Override]
    final public function find(ModelIdentity $identity): ?PriceDefinition
    {
        $catalog = $this->catalog();
        $model = $this->findModel($catalog, $identity);

        return $model === null ? null : $this->definition($identity, $model);
    }

    #[Override]
    final public function sync(): int
    {
        if ($this->offline) {
            return count($this->lastKnownGoodCatalog());
        }

        $catalog = $this->retrieve();
        $this->retrievedAt = new DateTimeImmutable;
        $this->store($catalog);

        return count($catalog);
    }

    /** @return array<int|string, mixed> */
    final protected function catalog(): array
    {
        $cached = $this->offline ? $this->lastKnownGoodCatalog() : $this->cachedCatalog();

        if ($cached !== [] || $this->offline) {
            return $cached;
        }

        try {
            $catalog = $this->retrieve();
            $this->retrievedAt = new DateTimeImmutable;
            $this->store($catalog);

            return $catalog;
        } catch (Throwable) {
            return $this->lastKnownGoodCatalog();
        }
    }

    /** @return array<int|string, mixed> */
    final protected function cachedCatalog(): array
    {
        $value = $this->cache->get($this->cacheKey(), []);

        if (is_array($value) && is_array($value['catalog'] ?? null)) {
            $retrievedAt = $value['retrieved_at'] ?? null;
            $this->retrievedAt = is_string($retrievedAt) ? new DateTimeImmutable($retrievedAt) : null;

            return $value['catalog'];
        }

        return is_array($value) ? $value : [];
    }

    /** @return array<int|string, mixed> */
    final protected function lastKnownGoodCatalog(): array
    {
        $value = $this->cache->get($this->cacheKey().':lkg', []);

        if (is_array($value) && is_array($value['catalog'] ?? null)) {
            $retrievedAt = $value['retrieved_at'] ?? null;
            $this->retrievedAt = is_string($retrievedAt) ? new DateTimeImmutable($retrievedAt) : null;

            return $value['catalog'];
        }

        return [];
    }

    /** @param array<int|string, mixed> $catalog */
    private function store(array $catalog): void
    {
        $payload = $this->cachePayload($catalog);
        $this->cache->put($this->cacheKey(), $payload, $this->ttlSeconds);
        $this->cache->forever($this->cacheKey().':lkg', $payload);
    }

    /** @param array<int|string, mixed> $catalog
     * @return array{retrieved_at: string, catalog: array<int|string, mixed>}
     */
    private function cachePayload(array $catalog): array
    {
        return [
            'retrieved_at' => ($this->retrievedAt ?? new DateTimeImmutable)->format(DATE_ATOM),
            'catalog' => $catalog,
        ];
    }

    /** @return array<int|string, mixed> */
    abstract protected function retrieve(): array;

    /** @param array<int|string, mixed> $catalog
     * @return array<string, mixed>|null
     */
    abstract protected function findModel(array $catalog, ModelIdentity $identity): ?array;

    /** @param array<string, mixed> $model */
    abstract protected function definition(ModelIdentity $identity, array $model): ?PriceDefinition;

    abstract protected function cacheKey(): string;
}
