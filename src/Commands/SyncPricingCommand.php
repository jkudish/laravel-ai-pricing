<?php

declare(strict_types=1);

namespace Jkudish\LaravelAiPricing\Commands;

use Illuminate\Console\Command;
use Jkudish\LaravelAiPricing\Sources\OpenRouterPricingSource;
use Jkudish\LaravelAiPricing\Sources\PortkeyPricingSource;
use Throwable;

final class SyncPricingCommand extends Command
{
    protected $signature = 'ai:pricing:sync';

    protected $description = 'Cache AI pricing catalogs for online and offline cost attribution';

    public function handle(OpenRouterPricingSource $openRouter, PortkeyPricingSource $portkey): int
    {
        $total = 0;
        $successfulSources = 0;

        $sources = ['OpenRouter' => $openRouter];

        if ($portkey->hasConfiguredProviders()) {
            $sources['Portkey'] = $portkey;
        }

        foreach ($sources as $name => $source) {
            try {
                $count = $source->sync();
                $total += $count;
                $successfulSources++;
                $this->components->info("{$name}: cached {$count} models");
            } catch (Throwable $exception) {
                $this->components->warn("{$name}: {$exception->getMessage()}");
            }
        }

        if ($successfulSources === 0) {
            $this->components->error('All remote pricing sources failed; configured and last-known-good prices remain available.');

            return self::FAILURE;
        }

        if ($total === 0) {
            $this->components->warn('Remote sources succeeded but returned no models; configured and last-known-good prices remain available.');
        }

        return self::SUCCESS;
    }
}
