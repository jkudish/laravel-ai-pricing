<?php

declare(strict_types=1);

namespace Jkudish\LaravelAiPricing\ValueObjects;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use InvalidArgumentException;

final readonly class Rate
{
    private const int CALCULATION_SCALE = 24;

    public BigDecimal $amount;

    public BigDecimal $per;

    public string $currency;

    public function __construct(
        public string $unit,
        string|int|BigDecimal $amount,
        string|int|BigDecimal $per = 1,
        string $currency = 'USD',
    ) {
        $this->amount = BigDecimal::of($amount);
        $this->per = BigDecimal::of($per);
        $this->currency = strtoupper($currency);

        if ($this->amount->isNegative() || $this->per->isLessThanOrEqualTo(0)) {
            throw new InvalidArgumentException('A rate requires a non-negative amount and a positive divisor.');
        }

        if (trim($unit) === '') {
            throw new InvalidArgumentException('A rate unit must not be empty.');
        }
    }

    public function cost(BigDecimal $quantity): Money
    {
        $amount = $quantity
            ->multipliedBy($this->amount)
            ->dividedBy($this->per, self::CALCULATION_SCALE, RoundingMode::HalfEven);

        $normalized = rtrim(rtrim((string) $amount, '0'), '.');

        return new Money($normalized === '' ? '0' : $normalized, $this->currency);
    }

    /** @return array{unit: string, amount: string, per: string, currency: string} */
    public function toArray(): array
    {
        return [
            'unit' => $this->unit,
            'amount' => (string) $this->amount,
            'per' => (string) $this->per,
            'currency' => strtoupper($this->currency),
        ];
    }
}
