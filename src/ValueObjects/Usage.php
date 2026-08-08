<?php

declare(strict_types=1);

namespace Jkudish\LaravelAiPricing\ValueObjects;

use Brick\Math\BigDecimal;
use InvalidArgumentException;

final readonly class Usage
{
    /** @var array<string, BigDecimal> */
    private array $units;

    /** @param array<string, string|int|BigDecimal> $units */
    public function __construct(array $units)
    {
        $normalized = [];

        foreach ($units as $unit => $quantity) {
            if (trim($unit) === '') {
                throw new InvalidArgumentException('Usage unit names must not be empty.');
            }

            $decimal = BigDecimal::of($quantity);

            if ($decimal->isNegative()) {
                throw new InvalidArgumentException('Usage quantities cannot be negative.');
            }

            $normalized[$unit] = $decimal;
        }

        $this->units = $normalized;
    }

    public static function tokens(int $input, int $output, int $cachedInput = 0): self
    {
        return new self([
            'input_tokens' => $input,
            'output_tokens' => $output,
            'cached_input_tokens' => $cachedInput,
        ]);
    }

    public function quantity(string $unit): BigDecimal
    {
        return $this->units[$unit] ?? BigDecimal::zero();
    }

    /** @return array<string, string> */
    public function toArray(): array
    {
        return array_map(static fn (BigDecimal $quantity): string => (string) $quantity, $this->units);
    }
}
