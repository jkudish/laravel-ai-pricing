<?php

declare(strict_types=1);

namespace Jkudish\LaravelAiPricing;

use Brick\Math\BigDecimal;
use Jkudish\LaravelAiPricing\Enums\CostCompleteness;
use Jkudish\LaravelAiPricing\ValueObjects\CostQuote;
use Jkudish\LaravelAiPricing\ValueObjects\Money;
use Jkudish\LaravelAiPricing\ValueObjects\PriceDefinition;
use Jkudish\LaravelAiPricing\ValueObjects\PricingProvenance;
use Jkudish\LaravelAiPricing\ValueObjects\PricingSnapshot;
use Jkudish\LaravelAiPricing\ValueObjects\Usage;

final class CostCalculator
{
    public function calculate(Usage $usage, PriceDefinition $pricing): CostQuote
    {
        $total = null;
        $missing = [];
        $outputFamilySettled = false;

        foreach ($usage->toArray() as $unit => $quantity) {
            if ($usage->quantity($unit)->isZero()) {
                continue;
            }

            if ($unit === 'output_tokens' || $unit === 'reasoning_tokens') {
                if ($outputFamilySettled) {
                    continue;
                }

                $outputFamilySettled = true;

                [$lines, $missingUnits] = $this->settleOutputFamily($usage, $pricing);

                foreach ($lines as $line) {
                    $total = $total instanceof Money ? $total->plus($line) : $line;
                }

                foreach ($missingUnits as $missingUnit) {
                    $missing[] = $missingUnit;
                }

                continue;
            }

            $rate = $pricing->rates[$unit] ?? null;

            if ($rate === null) {
                $missing[] = $unit;

                continue;
            }

            $line = $rate->cost($usage->quantity($unit));
            $total = $total instanceof Money ? $total->plus($line) : $line;
        }

        if (! $total instanceof Money) {
            return new CostQuote(
                cost: null,
                completeness: CostCompleteness::Unavailable,
                source: $pricing->source,
                snapshot: new PricingSnapshot($pricing),
                missingUnits: $missing,
                provenance: new PricingProvenance($pricing->source, $pricing->effectiveAt, $pricing->retrievedAt, $pricing->sourceReference),
            );
        }

        return new CostQuote(
            cost: $total,
            completeness: $missing === [] ? CostCompleteness::Complete : CostCompleteness::Partial,
            source: $pricing->source,
            snapshot: new PricingSnapshot($pricing),
            missingUnits: $missing,
            provenance: new PricingProvenance($pricing->source, $pricing->effectiveAt, $pricing->retrievedAt, $pricing->sourceReference),
        );
    }

    /**
     * Settle the output token family exactly once.
     *
     * Every supported provider reports the output count inclusive of its
     * reasoning subset (OpenAI and OpenRouter completion_tokens, Anthropic
     * output_tokens, Gemini candidatesTokenCount), and the laravel/ai
     * adapters preserve that semantics for the output_tokens unit. The
     * family therefore prices a partition instead of additive units: the
     * non-reasoning remainder settles at the output rate and the reasoning
     * subset at the reasoning rate, so a published reasoning rate can never
     * double-bill reasoning that the output count already contains, and a
     * missing reasoning rate falls back to the output rate instead of
     * making reasoning free.
     *
     * The meaning of a reasoning_tokens rate is therefore exact: it is the
     * price for reasoning tokens and replaces the output rate for them. A
     * published zero rate is a deliberate claim that reasoning is free; an
     * omitted rate means the output rate applies. Catalogs that bill
     * reasoning within the output rate must omit the rate rather than
     * publish zero.
     *
     * @return array{0: list<Money>, 1: list<string>}
     */
    private function settleOutputFamily(Usage $usage, PriceDefinition $pricing): array
    {
        $output = $usage->quantity('output_tokens');
        $reasoning = $usage->quantity('reasoning_tokens');
        $outputRate = $pricing->rates['output_tokens'] ?? null;
        $reasoningRate = $pricing->rates['reasoning_tokens'] ?? null;

        if ($reasoning->isZero()) {
            $segments = [
                [$output, $outputRate, 'output_tokens'],
            ];
        } elseif ($reasoningRate !== null) {
            $visible = $output->minus($reasoning);

            if ($visible->isNegative()) {
                $visible = BigDecimal::zero();
            }

            $segments = [
                [$visible, $outputRate, 'output_tokens'],
                [$reasoning, $reasoningRate, 'reasoning_tokens'],
            ];
        } elseif ($outputRate !== null) {
            // Without a published reasoning rate the whole inclusive output
            // count settles at the output rate. When no output count (or a
            // smaller one than the reasoning subset) is reported, the
            // reasoning count stands in as the output-family total so that
            // reasoning is never left unpriced.
            $outputTotal = $output->compareTo($reasoning) >= 0 ? $output : $reasoning;

            $segments = [
                [$outputTotal, $outputRate, 'output_tokens'],
            ];
        } else {
            // Neither family rate is published: the billable output-family
            // quantity is the output count, which subsumes the reasoning
            // subset, so reasoning never appears as its own missing unit.
            $segments = [
                [$output, $outputRate, 'output_tokens'],
            ];
        }

        $lines = [];
        $missing = [];

        foreach ($segments as [$quantity, $rate, $unit]) {
            if ($quantity->isZero()) {
                continue;
            }

            if ($rate === null) {
                $missing[] = $unit;

                continue;
            }

            $lines[] = $rate->cost($quantity);
        }

        return [$lines, $missing];
    }
}
