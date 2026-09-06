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

it('normalizes configured pricing identity keys', function (): void {
    config()->set('ai-pricing.prices', [
        ' OpenAI:GPT-TEST ' => ['input_tokens' => ['amount' => '1', 'per' => '1000']],
    ]);
    app()->forgetInstance(ConfiguredPricingSource::class);
    app()->forgetInstance(CostResolver::class);

    $quote = app(CostResolver::class)->resolve(new PricingObservation(
        new ModelIdentity('openai', 'gpt-test'),
        Usage::tokens(1000, 0),
    ));

    expect((string) $quote->cost?->amount)->toBe('1');
});

it('rejects configured identities that collide after normalization', function (): void {
    config()->set('ai-pricing.prices', [
        'OpenAI:GPT-TEST' => ['input_tokens' => ['amount' => '1']],
        'openai:gpt-test' => ['input_tokens' => ['amount' => '2']],
    ]);
    app()->forgetInstance(ConfiguredPricingSource::class);

    expect(fn (): ConfiguredPricingSource => app(ConfiguredPricingSource::class))
        ->toThrow(InvalidArgumentException::class, 'duplicates');
});

it('quotes package SKUs while preserving configured override precedence', function (): void {
    $identity = new ModelIdentity('you', 'answer');
    $usage = new Usage(['requests' => 2]);

    $packageQuote = app(CostResolver::class)->resolve(new PricingObservation($identity, $usage));

    config()->set('ai-pricing.prices', [
        'you:answer' => ['requests' => ['amount' => '9', 'per' => '1000']],
    ]);
    app()->forgetInstance(ConfiguredPricingSource::class);
    app()->forgetInstance(CostResolver::class);

    $configuredQuote = app(CostResolver::class)->resolve(new PricingObservation($identity, $usage));

    expect((string) $packageQuote->cost?->amount)->toBe('0.01')
        ->and($packageQuote->source->value)->toBe('provider_native')
        ->and((string) $configuredQuote->cost?->amount)->toBe('0.018')
        ->and($configuredQuote->source->value)->toBe('configured');
});

it('preserves configured override precedence for every DataForSEO package SKU', function (string $sku, string $packageAmount): void {
    $identity = new ModelIdentity('dataforseo', $sku);
    $usage = new Usage(['requests' => 2]);

    $packageQuote = app(CostResolver::class)->resolve(new PricingObservation($identity, $usage));

    config()->set('ai-pricing.prices', [
        "dataforseo:{$sku}" => ['requests' => ['amount' => '9', 'per' => '1']],
    ]);
    app()->forgetInstance(ConfiguredPricingSource::class);
    app()->forgetInstance(CostResolver::class);

    $configuredQuote = app(CostResolver::class)->resolve(new PricingObservation($identity, $usage));

    expect((string) $packageQuote->cost?->amount)->toBe($packageAmount)
        ->and($packageQuote->source->value)->toBe('provider_native')
        ->and((string) $configuredQuote->cost?->amount)->toBe('18')
        ->and($configuredQuote->source->value)->toBe('configured');
})->with([
    'ChatGPT LLM Scraper Standard' => ['chatgpt-llm-scraper-standard', '0.0024'],
    'ChatGPT LLM Scraper Live' => ['chatgpt-llm-scraper-live', '0.008'],
    'Gemini LLM Scraper Standard' => ['gemini-llm-scraper-standard', '0.0024'],
    'Gemini LLM Scraper Live' => ['gemini-llm-scraper-live', '0.008'],
    'Google AI Mode Standard' => ['google-ai-mode-standard', '0.0024'],
    'Google AI Mode Live' => ['google-ai-mode-live', '0.008'],
]);

it('requires configured SearchAPI account pricing before returning a complete quote', function (): void {
    $identity = new ModelIdentity('searchapi', 'search');
    $usage = new Usage(['successful_search_request' => 2]);

    $unconfigured = app(CostResolver::class)->resolve(new PricingObservation($identity, $usage));

    config()->set('ai-pricing.prices', [
        'searchapi:search' => [
            'successful_search_request' => ['amount' => '4', 'per' => '1000'],
        ],
    ]);
    app()->forgetInstance(ConfiguredPricingSource::class);
    app()->forgetInstance(CostResolver::class);

    $configured = app(CostResolver::class)->resolve(new PricingObservation($identity, $usage));

    expect($unconfigured->cost)->toBeNull()
        ->and($unconfigured->completeness->value)->toBe('unavailable')
        ->and((string) $configured->cost?->amount)->toBe('0.008')
        ->and($configured->completeness->value)->toBe('complete')
        ->and($configured->source->value)->toBe('configured');
});
