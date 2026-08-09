<?php

declare(strict_types=1);

namespace Jkudish\LaravelAiPricing\Enums;

enum CostCompleteness: string
{
    case Complete = 'complete';
    case Partial = 'partial';
    case Unavailable = 'unavailable';
}
