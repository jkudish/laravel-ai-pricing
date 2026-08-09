<?php

declare(strict_types=1);

namespace Jkudish\LaravelAiPricing\Enums;

enum PricingSource: string
{
    case ProviderReported = 'provider_reported';
    case Configured = 'configured';
    case ProviderNative = 'provider_native';
    case Portkey = 'portkey';
    case Unavailable = 'unavailable';
}
