<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Jkudish\LaravelAiPricing\Contracts\CostResolver;
use Jkudish\LaravelAiPricing\PricingResolver;
use Jkudish\LaravelAiPricing\Sources\ConfiguredPricingSource;
use Jkudish\LaravelAiPricing\ValueObjects\ModelIdentity;
use Jkudish\LaravelAiPricing\ValueObjects\PricingObservation;
use Jkudish\LaravelAiPricing\ValueObjects\Usage;

it('binds the pricing resolver and registers its sync command', function (): void {
    expect(app(CostResolver::class))->toBeInstanceOf(PricingResolver::class);

    Http::fake([
        '*' => Http::response(['data' => [['id' => 'model', 'pricing' => ['prompt' => '0.1']]]]),
    ]);

    $this->artisan('ai:pricing:sync')
        ->expectsOutputToContain('OpenRouter: cached 1 models')
        ->assertSuccessful();
});

it('fails sync when every attempted remote catalog fails while runtime lookup remains nonblocking', function (): void {
    Http::fake([
        '*' => Http::response([], 500),
    ]);

    $this->artisan('ai:pricing:sync')
        ->expectsOutputToContain('OpenRouter:')
        ->expectsOutputToContain('All remote pricing sources failed')
        ->assertFailed();
});

it('succeeds sync on partial source success', function (): void {
    config()->set('ai-pricing.portkey.providers', ['anthropic']);
    Http::fake([
        'https://openrouter.ai/*' => Http::response([], 500),
        'https://configs.portkey.ai/pricing/anthropic.json' => Http::response([
            'claude' => ['pricing_config' => ['pay_as_you_go' => ['request_token' => ['price' => '0.1']]]],
        ]),
    ]);

    $this->artisan('ai:pricing:sync')
        ->expectsOutputToContain('OpenRouter:')
        ->expectsOutputToContain('Portkey: cached 1 models')
        ->assertSuccessful();
});

it('applies the package currency to configured rates that omit currency', function (): void {
    config()->set('ai-pricing.currency', 'CAD');
    config()->set('ai-pricing.prices', [
        'openai:gpt' => ['input_tokens' => ['amount' => '1', 'per' => '1000']],
    ]);
    app()->forgetInstance(ConfiguredPricingSource::class);
    app()->forgetInstance(CostResolver::class);

    $quote = app(CostResolver::class)->resolve(new PricingObservation(
        new ModelIdentity('openai', 'gpt'),
        Usage::tokens(1000, 0),
    ));

    expect($quote->completeness->value)->toBe('complete')
        ->and($quote->cost?->currency)->toBe('CAD');
});
