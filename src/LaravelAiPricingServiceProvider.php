<?php

namespace Jkudish\LaravelAiPricing;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Jkudish\LaravelAiPricing\Commands\LaravelAiPricingCommand;

class LaravelAiPricingServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-ai-pricing')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel_ai_pricing_table')
            ->hasCommand(LaravelAiPricingCommand::class);
    }
}
