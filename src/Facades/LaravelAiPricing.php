<?php

namespace Jkudish\LaravelAiPricing\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Jkudish\LaravelAiPricing\LaravelAiPricing
 */
class LaravelAiPricing extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Jkudish\LaravelAiPricing\LaravelAiPricing::class;
    }
}
