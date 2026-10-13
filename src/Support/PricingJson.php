<?php

declare(strict_types=1);

namespace Jkudish\LaravelAiPricing\Support;

use UnexpectedValueException;

final class PricingJson
{
    public static function decode(string $json): mixed
    {
        // Validate the original document first: quoting tokens must never turn
        // invalid JSON numbers (such as 01 or 1.) into accepted decimal strings.
        if (! json_validate($json)) {
            throw new UnexpectedValueException('Pricing catalog is not valid JSON.');
        }

        // Match complete strings before numbers, preserving escaped quotes,
        // backslashes and numeric text inside strings. No number passes through
        // a binary float, including exponent notation and oversized integers.
        $exact = preg_replace_callback(
            '/"(?:[^"\\\\]|\\\\.)*"|-?(?:0|[1-9][0-9]*)(?:\.[0-9]+)?(?:[eE][+-]?[0-9]+)?/s',
            static fn (array $match): string => str_starts_with($match[0], '"') ? $match[0] : '"'.$match[0].'"',
            $json,
        );

        if ($exact === null) {
            throw new UnexpectedValueException('Pricing catalog could not be decoded.');
        }

        return json_decode($exact, true, flags: JSON_THROW_ON_ERROR);
    }
}
