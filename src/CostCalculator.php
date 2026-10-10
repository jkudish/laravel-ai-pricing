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
use Jkudish\LaravelAiPricing\ValueObjects\Rate;
use Jkudish\LaravelAiPricing\ValueObjects\Usage;

final class CostCalculator
{
    private const array CACHE_WRITE_FAMILY = [
        'cache_write_input_tokens',
        'cache_write_input_tokens_5m',
        'cache_write_input_tokens_1h',
    ];

    public function calculate(Usage $usage, PriceDefinition $pricing): CostQuote
    {
        $total = null;
        $missing = [];
        $outputFamilySettled = false;
        $cacheWriteFamilySettled = false;

        foreach ($usage->toArray() as $unit => $quantity) {
            if ($usage->quantity($unit)->isZero()) {
                continue;
            }

            if (in_array($unit, self::CACHE_WRITE_FAMILY, true)) {
                if ($cacheWriteFamilySettled) {
                    continue;
                }

                $cacheWriteFamilySettled = true;

                [$lines, $missingUnits] = $this->settleCacheWriteFamily($usage, $pricing);

                foreach ($lines as $line) {
                    $total = $total instanceof Money ? $total->plus($line) : $line;
                }

                foreach ($missingUnits as $missingUnit) {
                    $missing[] = $missingUnit;
                }

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

        return $this->priceSegments($segments);
    }

    /**
     * Settle the cache-write token family exactly once.
     *
     * cache_write_input_tokens is the aggregate count of input tokens written
     * to a prompt cache. Anthropic additionally reports the same writes split
     * by cache TTL, which the adapters map to cache_write_input_tokens_5m and
     * cache_write_input_tokens_1h; the split is a partition of the aggregate,
     * not additional usage, so the family never bills both.
     *
     * - When every reported TTL subset has a TTL-specific rate, the subsets
     *   bill at those rates. Any aggregate remainder the split does not
     *   account for has an unknown TTL and bills at the generic rate, or is
     *   missing when no generic rate is published.
     * - When a TTL subset has no rate but the observation also reported the
     *   aggregate and the price publishes a generic cache-write rate, the
     *   aggregate bills at that generic rate, exactly as it did before the
     *   split was mapped.
     * - Otherwise each subset bills at its own rate or is reported missing.
     *
     * A reported aggregate (including zero) smaller than its split is
     * contradictory: every reported family unit is missing and nothing in
     * the family is billed.
     *
     * A generic aggregate with no split therefore never satisfies a price
     * that only publishes TTL-specific rates: it stays a missing unit, so the
     * quote is partial instead of guessing which TTL was written.
     *
     * @return array{0: list<Money>, 1: list<string>}
     */
    private function settleCacheWriteFamily(Usage $usage, PriceDefinition $pricing): array
    {
        $aggregate = $usage->quantity('cache_write_input_tokens');
        $fiveMinute = $usage->quantity('cache_write_input_tokens_5m');
        $oneHour = $usage->quantity('cache_write_input_tokens_1h');
        $split = $fiveMinute->plus($oneHour);
        $genericRate = $pricing->rates['cache_write_input_tokens'] ?? null;
        $fiveMinuteRate = $pricing->rates['cache_write_input_tokens_5m'] ?? null;
        $oneHourRate = $pricing->rates['cache_write_input_tokens_1h'] ?? null;

        if ($split->isZero()) {
            return $this->priceSegments([
                [$aggregate, $genericRate, 'cache_write_input_tokens'],
            ]);
        }

        // A reported aggregate (even zero) smaller than its own split is
        // contradictory usage. Neither count is trustworthy, so the whole
        // family stays missing and the quote is partial instead of guessing.
        if (array_key_exists('cache_write_input_tokens', $usage->toArray()) && $aggregate->compareTo($split) < 0) {
            return [[], array_values(array_filter(
                self::CACHE_WRITE_FAMILY,
                static fn (string $unit): bool => array_key_exists($unit, $usage->toArray()),
            ))];
        }

        $splitPriced = ($fiveMinute->isZero() || $fiveMinuteRate !== null)
            && ($oneHour->isZero() || $oneHourRate !== null);

        if (! $splitPriced && ! $aggregate->isZero() && $genericRate !== null) {
            return $this->priceSegments([
                [$aggregate, $genericRate, 'cache_write_input_tokens'],
            ]);
        }

        $remainder = $aggregate->minus($split);

        return $this->priceSegments([
            [$fiveMinute, $fiveMinuteRate, 'cache_write_input_tokens_5m'],
            [$oneHour, $oneHourRate, 'cache_write_input_tokens_1h'],
            // Only an absent aggregate can leave a negative remainder here.
            [$remainder->isNegative() ? BigDecimal::zero() : $remainder, $genericRate, 'cache_write_input_tokens'],
        ]);
    }

    /**
     * Price a family's segments, reporting each unpriced non-zero segment as missing.
     *
     * @param  list<array{0: BigDecimal, 1: Rate|null, 2: string}>  $segments
     * @return array{0: list<Money>, 1: list<string>}
     */
    private function priceSegments(array $segments): array
    {
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
