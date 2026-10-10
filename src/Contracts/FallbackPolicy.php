<?php

declare(strict_types=1);

namespace Jkudish\LaravelAiPricing\Contracts;

use Jkudish\LaravelAiPricing\ValueObjects\ModelIdentity;

interface FallbackPolicy
{
    /**
     * Whether less authoritative catalogs may price the identity.
     *
     * The package snapshot answers for both remote catalogs (the native
     * OpenRouter catalog and the fallback catalog). A native catalog answers
     * for the fallback catalog only, refusing an identity it lists but
     * cannot price.
     */
    public function allowsFallback(ModelIdentity $identity): bool;
}
