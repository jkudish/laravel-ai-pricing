<?php

declare(strict_types=1);

namespace Jkudish\LaravelAiPricing\Contracts;

use Jkudish\LaravelAiPricing\ValueObjects\ModelIdentity;
use Jkudish\LaravelAiPricing\ValueObjects\PriceDefinition;

interface PricingCatalog
{
    public function find(ModelIdentity $identity): ?PriceDefinition;

    public function sync(): int;
}
