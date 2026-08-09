<?php

declare(strict_types=1);

namespace Jkudish\LaravelAiPricing\ValueObjects;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use InvalidArgumentException;
use Jkudish\LaravelAiPricing\Enums\RoundingBoundary;

final readonly class Money
{
    public BigDecimal $amount;

    public string $currency;

    public function __construct(string|int|BigDecimal $amount, string $currency = 'USD')
    {
        $currency = strtoupper(trim($currency));

        if (! preg_match('/^[A-Z]{3}$/', $currency)) {
            throw new InvalidArgumentException('Currency must be a three-letter ISO code.');
        }

        $decimal = BigDecimal::of($amount);

        if ($decimal->isNegative()) {
            throw new InvalidArgumentException('Money cannot be negative.');
        }

        $this->amount = $decimal;
        $this->currency = $currency;
    }

    public function plus(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amount->plus($other->amount), $this->currency);
    }

    public function rounded(int $scale = 6, RoundingMode $mode = RoundingMode::HalfUp): self
    {
        return new self($this->amount->toScale($scale, $mode), $this->currency);
    }

    public function at(RoundingBoundary $boundary, RoundingMode $mode = RoundingMode::HalfUp): self
    {
        return $this->rounded($boundary->value, $mode);
    }

    /** @return array{amount: string, currency: string} */
    public function toArray(): array
    {
        return ['amount' => (string) $this->amount, 'currency' => $this->currency];
    }

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException("Cannot combine {$this->currency} and {$other->currency}; FX conversion is not supported.");
        }
    }
}
