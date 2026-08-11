<?php

declare(strict_types=1);

use Jkudish\LaravelAiPricing\Contracts\CostResolver;
use Jkudish\LaravelAiPricing\Facades\AiPricing;
use Jkudish\LaravelAiPricing\ResponseCostResolver;
use Jkudish\LaravelAiPricing\Sources\ConfiguredPricingSource;
use Jkudish\LaravelAiPricing\ValueObjects\Usage;

it('resolves a completed Laravel AI response through the public cost API', function (): void {
    config()->set('ai-pricing.prices', [
        'openai:gpt-test' => [
            'input_tokens' => ['amount' => '1', 'per' => '1000'],
            'output_tokens' => ['amount' => '2', 'per' => '1000'],
        ],
    ]);
    app()->forgetInstance(ConfiguredPricingSource::class);
    app()->forgetInstance(CostResolver::class);
    app()->forgetInstance(ResponseCostResolver::class);

    $response = new class
    {
        public object $meta;

        public object $usage;

        public function __construct()
        {
            $this->meta = (object) ['provider' => 'openai', 'model' => 'gpt-test'];
            $this->usage = (object) ['inputTokens' => 1_000, 'outputTokens' => 250];
        }
    };

    $cost = AiPricing::cost($response);

    expect((string) $cost->amount)->toBe('1.5')
        ->and($cost->currency)->toBe('USD')
        ->and($cost->source->value)->toBe('configured')
        ->and($cost->completeness->value)->toBe('complete');
});

it('keeps provider-reported response cost ahead of catalog pricing', function (): void {
    $response = [
        'provider' => 'openrouter',
        'model' => 'google/gemini-3-flash',
        'usage' => ['inputTokens' => 1_000, 'outputTokens' => 250],
        'cost' => '0.0042',
        'currency' => 'USD',
    ];

    $cost = AiPricing::cost($response);

    expect((string) $cost->amount)->toBe('0.0042')
        ->and($cost->source->value)->toBe('provider_reported')
        ->and($cost->completeness->value)->toBe('complete');
});

it('uses quote for a pre-request estimate', function (): void {
    config()->set('ai-pricing.prices', [
        'openai:gpt-test' => [
            'input_tokens' => ['amount' => '1', 'per' => '1000'],
        ],
    ]);
    app()->forgetInstance(ConfiguredPricingSource::class);
    app()->forgetInstance(CostResolver::class);
    app()->forgetInstance(ResponseCostResolver::class);

    $quote = AiPricing::quote(
        provider: 'openai',
        model: 'gpt-test',
        usage: new Usage(['input_tokens' => 500]),
    );

    expect((string) $quote->amount)->toBe('0.5')
        ->and($quote->currency)->toBe('USD')
        ->and($quote->source->value)->toBe('configured');
});

it('exposes an unavailable cost without representing it as zero', function (): void {
    $quote = AiPricing::quote(
        provider: 'unknown',
        model: 'model',
        usage: new Usage(['input_tokens' => 1]),
    );

    expect($quote->amount)->toBeNull()
        ->and($quote->currency)->toBeNull()
        ->and($quote->completeness->value)->toBe('unavailable');
});
