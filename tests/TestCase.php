<?php

declare(strict_types=1);

namespace Jkudish\LaravelAiPricing\Tests;

use Illuminate\Foundation\Application;
use Jkudish\LaravelAiPricing\LaravelAiPricingServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * @param  Application  $app
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [LaravelAiPricingServiceProvider::class];
    }
}
