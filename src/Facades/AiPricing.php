<?php

declare(strict_types=1);

namespace Jkudish\LaravelAiPricing\Facades;

use Illuminate\Support\Facades\Facade;
use Jkudish\LaravelAiPricing\ResponseCostResolver;

/** @see ResponseCostResolver */
final class AiPricing extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ResponseCostResolver::class;
    }
}
