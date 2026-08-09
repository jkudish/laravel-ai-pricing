<?php

declare(strict_types=1);

namespace Jkudish\LaravelAiPricing\Enums;

enum RoundingBoundary: int
{
    case Evidence = 12;
    case Display = 6;
}
