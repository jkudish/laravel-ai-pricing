<?php

declare(strict_types=1);

namespace Jkudish\LaravelAiPricing\Contracts;

use Jkudish\LaravelAiPricing\ValueObjects\ModelIdentity;

interface FallbackPolicy
{
    /**
     * Whether remote catalogs (the native OpenRouter catalog and the fallback
     * catalog) may price the identity when nothing more authoritative does.
     */
    public function allowsFallback(ModelIdentity $identity): bool;
}
