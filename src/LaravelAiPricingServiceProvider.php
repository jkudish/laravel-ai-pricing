<?php

declare(strict_types=1);

namespace Jkudish\LaravelAiPricing;

use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Client\Factory as HttpFactory;
use Jkudish\LaravelAiPricing\Adapters\LaravelAiObservationAdapter;
use Jkudish\LaravelAiPricing\Commands\SyncPricingCommand;
use Jkudish\LaravelAiPricing\Contracts\CostResolver;
use Jkudish\LaravelAiPricing\Sources\ConfiguredPricingSource;
use Jkudish\LaravelAiPricing\Sources\OpenRouterPricingSource;
use Jkudish\LaravelAiPricing\Sources\PortkeyPricingSource;
use LogicException;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

final class LaravelAiPricingServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-ai-pricing')
            ->hasConfigFile()
            ->hasCommand(SyncPricingCommand::class);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(OpenRouterPricingSource::class, function (): OpenRouterPricingSource {
            return new OpenRouterPricingSource(
                http: $this->http(),
                cache: $this->cache($this->cacheManager()),
                endpoint: $this->stringConfig('ai-pricing.openrouter.endpoint'),
                ttlSeconds: $this->intConfig('ai-pricing.cache_ttl'),
                offline: $this->boolConfig('ai-pricing.offline'),
            );
        });

        $this->app->singleton(PortkeyPricingSource::class, function (): PortkeyPricingSource {
            return new PortkeyPricingSource(
                http: $this->http(),
                cache: $this->cache($this->cacheManager()),
                endpoint: $this->stringConfig('ai-pricing.portkey.endpoint'),
                ttlSeconds: $this->intConfig('ai-pricing.cache_ttl'),
                offline: $this->boolConfig('ai-pricing.offline'),
                providers: $this->stringListConfig('ai-pricing.portkey.providers'),
            );
        });

        $this->app->singleton(ConfiguredPricingSource::class, fn (): ConfiguredPricingSource => new ConfiguredPricingSource(
            prices: $this->prices(),
            currency: $this->stringConfig('ai-pricing.currency'),
        ));

        $this->app->singleton(CostResolver::class, function (): PricingResolver {
            return new PricingResolver(
                configured: $this->configured(),
                native: $this->openRouter(),
                fallback: $this->portkey(),
                currency: $this->stringConfig('ai-pricing.currency'),
            );
        });

        $this->app->singleton(LaravelAiObservationAdapter::class);

        $this->app->singleton(ResponseCostResolver::class, fn (): ResponseCostResolver => new ResponseCostResolver(
            resolver: $this->app->make(CostResolver::class),
            laravelAi: $this->app->make(LaravelAiObservationAdapter::class),
        ));
    }

    private function cache(CacheManager $manager): CacheRepository
    {
        $store = config('ai-pricing.cache_store');

        return $manager->store(is_string($store) ? $store : null);
    }

    private function http(): HttpFactory
    {
        return $this->app->make(HttpFactory::class);
    }

    private function cacheManager(): CacheManager
    {
        return $this->app->make(CacheManager::class);
    }

    private function configured(): ConfiguredPricingSource
    {
        return $this->app->make(ConfiguredPricingSource::class);
    }

    private function openRouter(): OpenRouterPricingSource
    {
        return $this->app->make(OpenRouterPricingSource::class);
    }

    private function portkey(): PortkeyPricingSource
    {
        return $this->app->make(PortkeyPricingSource::class);
    }

    private function stringConfig(string $key): string
    {
        $value = config($key);

        if (! is_string($value)) {
            throw new LogicException("Configuration [{$key}] must be a string.");
        }

        return $value;
    }

    private function intConfig(string $key): int
    {
        $value = config($key);

        if (! is_int($value)) {
            throw new LogicException("Configuration [{$key}] must be an integer.");
        }

        return $value;
    }

    private function boolConfig(string $key): bool
    {
        $value = config($key);

        if (! is_bool($value)) {
            throw new LogicException("Configuration [{$key}] must be a boolean.");
        }

        return $value;
    }

    /** @return list<string> */
    private function stringListConfig(string $key): array
    {
        $value = config($key, []);

        if (! is_array($value)) {
            throw new LogicException("Configuration [{$key}] must be an array.");
        }

        $items = [];

        foreach ($value as $item) {
            if (! is_string($item) || trim($item) === '') {
                throw new LogicException("Configuration [{$key}] must contain non-empty strings.");
            }

            $items[] = $item;
        }

        return $items;
    }

    /** @return array<string, array<string, array{amount: string|int, per?: string|int, currency?: string}>> */
    private function prices(): array
    {
        $value = config('ai-pricing.prices', []);

        if (! is_array($value)) {
            throw new LogicException('Configuration [ai-pricing.prices] must be an array.');
        }

        $prices = [];

        foreach ($value as $identity => $configuredRates) {
            if (! is_string($identity) || ! is_array($configuredRates)) {
                throw new LogicException('Configured price identities and rates must be associative arrays.');
            }

            $rates = [];

            foreach ($configuredRates as $unit => $configuredRate) {
                if (! is_string($unit) || ! is_array($configuredRate)) {
                    throw new LogicException('Configured rates must be keyed by usage unit.');
                }

                $amount = $configuredRate['amount'] ?? null;
                $per = $configuredRate['per'] ?? 1;
                $currency = $configuredRate['currency'] ?? null;

                if ((! is_string($amount) && ! is_int($amount))
                    || (! is_string($per) && ! is_int($per))
                    || ($currency !== null && ! is_string($currency))) {
                    throw new LogicException('Configured rate fields have invalid types.');
                }

                $rates[$unit] = ['amount' => $amount, 'per' => $per];

                if ($currency !== null) {
                    $rates[$unit]['currency'] = $currency;
                }
            }

            $prices[$identity] = $rates;
        }

        return $prices;
    }
}
