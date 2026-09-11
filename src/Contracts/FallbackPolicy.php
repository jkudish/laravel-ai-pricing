<?php

declare(strict_types=1);

namespace Jkudish\LaravelAiPricing\Contracts;

use Jkudish\LaravelAiPricing\ValueObjects\ModelIdentity;

interface FallbackPolicy
{
    public function allowsFallback(ModelIdentity $identity): bool;
}
