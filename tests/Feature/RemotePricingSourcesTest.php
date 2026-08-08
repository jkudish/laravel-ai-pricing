<?php

declare(strict_types=1);

use Illuminate\Http\Client\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Jkudish\LaravelAiPricing\Enums\PricingSource;
use Jkudish\LaravelAiPricing\Sources\OpenRouterPricingSource;
use Jkudish\LaravelAiPricing\Sources\PortkeyPricingSource;
use Jkudish\LaravelAiPricing\ValueObjects\ModelIdentity;

function openRouterSource(bool $offline = false): OpenRouterPricingSource
{
    return new OpenRouterPricingSource(
        app(Factory::class),
        Cache::store(),
        'https://openrouter.test/api/v1/models',
        3600,
        $offline,
    );
}

/** @param list<string> $providers */
function portkeySource(bool $offline = false, array $providers = []): PortkeyPricingSource
{
    return new PortkeyPricingSource(
        app(Factory::class),
        Cache::store(),
        'https://portkey.test/pricing/{provider}.json',
        3600,
        $offline,
        $providers,
    );
}

beforeEach(function (): void {
    Cache::flush();
    Http::preventStrayRequests();
});

afterEach(fn () => Carbon::setTestNow());

it('reads and caches the official OpenRouter models response', function (): void {
    Http::fake([
        'https://openrouter.test/api/v1/models' => Http::response([
            'data' => [[
                'id' => 'google/gemini-test',
                'pricing' => [
                    'prompt' => '0.000001',
                    'completion' => '0.000003',
                    'input_cache_read' => '0.0000001',
                ],
            ]],
        ]),
    ]);

    $source = openRouterSource();
    $definition = $source->find(new ModelIdentity('openrouter', 'google/gemini-test'));

    expect($definition?->source)->toBe(PricingSource::ProviderNative)
        ->and((string) $definition?->rates['input_tokens']->amount)->toBe('0.000001')
        ->and($source->sync())->toBe(1);

    Http::assertSentCount(2);
    Http::assertSent(fn ($request): bool => $request->url() === 'https://openrouter.test/api/v1/models'
        && $request->data() === []
        && ! $request->hasHeader('Authorization'));
});

it('ignores OpenRouter sentinel-priced router models without rejecting valid prices', function (): void {
    Http::fake([
        'https://openrouter.test/api/v1/models' => Http::response([
            'data' => [
                [
                    'id' => 'openrouter/auto',
                    'pricing' => ['prompt' => '-1', 'completion' => '-1'],
                ],
                [
                    'id' => 'google/gemini-test',
                    'pricing' => ['prompt' => '0.000001', 'completion' => '0.000003'],
                ],
            ],
        ]),
    ]);

    $source = openRouterSource();
    $definition = $source->find(new ModelIdentity('openrouter', 'google/gemini-test'));

    expect($definition)->not->toBeNull()
        ->and((string) $definition?->rates['input_tokens']->amount)->toBe('0.000001')
        ->and($source->find(new ModelIdentity('openrouter', 'openrouter/auto')))->toBeNull()
        ->and($source->sync())->toBe(1);
});

it('reads Portkey fallback pricing from a fixture-compatible catalog', function (): void {
    Http::fake([
        'https://portkey.test/pricing/anthropic.json' => Http::response([
            'claude-test' => [
                'pricing_config' => [
                    'pay_as_you_go' => [
                        'request_token' => ['price' => '0.0002'],
                        'response_token' => ['price' => '0.0008'],
                        'cache_read_input_token' => ['price' => '0.0001'],
                        'additional_units' => [
                            'web_search' => ['price' => '1'],
                        ],
                    ],
                    'currency' => 'USD',
                ],
            ],
        ]),
    ]);

    $definition = portkeySource()->find(new ModelIdentity('anthropic', 'claude-test'));

    expect($definition?->source)->toBe(PricingSource::Portkey)
        ->and((string) $definition?->rates['output_tokens']->amount)->toBe('0.000008')
        ->and((string) $definition?->rates['web_search']->amount)->toBe('0.01');

    Http::assertSent(fn ($request): bool => $request->url() === 'https://portkey.test/pricing/anthropic.json'
        && $request->data() === []
        && ! $request->hasHeader('Authorization'));
});

it('syncs only explicitly configured Portkey providers', function (): void {
    Http::fake([
        'https://portkey.test/pricing/openai.json' => Http::response(['gpt-test' => ['pricing_config' => ['pay_as_you_go' => ['request_token' => ['price' => '0.1']]]]]),
        'https://portkey.test/pricing/anthropic.json' => Http::response(['claude-test' => ['pricing_config' => ['pay_as_you_go' => ['request_token' => ['price' => '0.1']]]]]),
    ]);

    expect(portkeySource(providers: ['openai', 'anthropic'])->sync())->toBe(2);
    Http::assertSentCount(2);
});

it('preserves OpenRouter LKG when refresh is unusable', function (mixed $response): void {
    Http::fake(['https://openrouter.test/*' => Http::sequence()
        ->push(['data' => [['id' => 'good/model', 'pricing' => ['prompt' => '0.1']]]])
        ->push($response)]);
    $source = openRouterSource();
    $source->sync();

    expect(fn () => $source->sync())->toThrow(UnexpectedValueException::class);
    $definition = openRouterSource(offline: true)->find(new ModelIdentity('openrouter', 'good/model'));

    expect((string) $definition?->rates['input_tokens']->amount)->toBe('0.1');
})->with([
    'empty' => [['data' => []]],
    'non-object payload' => ['invalid'],
    'malformed envelope' => [['unexpected' => true]],
    'unknown-only pricing' => [['data' => [['id' => 'bad/model', 'pricing' => ['unknown' => '0.1']]]]],
    'invalid decimal' => [['data' => [['id' => 'bad/model', 'pricing' => ['prompt' => 'not-a-number']]]]],
    'NaN-like decimal' => [['data' => [['id' => 'bad/model', 'pricing' => ['prompt' => 'NaN']]]]],
    'infinite-like decimal' => [['data' => [['id' => 'bad/model', 'pricing' => ['prompt' => 'INF']]]]],
    'negative decimal' => [['data' => [['id' => 'bad/model', 'pricing' => ['prompt' => '-0.1']]]]],
    'unsafe float' => [['data' => [['id' => 'bad/model', 'pricing' => ['prompt' => 0.1]]]]],
]);

it('preserves Portkey LKG when refresh is unusable', function (array $response): void {
    Http::fake(['https://portkey.test/pricing/anthropic.json' => Http::sequence()
        ->push(['claude' => ['pricing_config' => ['pay_as_you_go' => ['request_token' => ['price' => '0.1']]]]])
        ->push($response)]);
    $source = portkeySource(providers: ['anthropic']);
    $source->sync();

    expect(fn () => $source->sync())->toThrow(UnexpectedValueException::class);
    $definition = portkeySource(offline: true)->find(new ModelIdentity('anthropic', 'claude'));

    expect((string) $definition?->rates['input_tokens']->amount)->toBe('0.001');
})->with([
    'empty' => [[]],
    'malformed model' => [['claude' => ['unexpected' => true]]],
    'unknown-only pricing' => [['claude' => ['pricing_config' => ['pay_as_you_go' => ['unknown' => ['price' => '0.1']]]]]],
    'invalid decimal' => [['claude' => ['pricing_config' => ['pay_as_you_go' => ['request_token' => ['price' => 'not-a-number']]]]]],
    'NaN-like decimal' => [['claude' => ['pricing_config' => ['pay_as_you_go' => ['request_token' => ['price' => 'NaN']]]]]],
    'infinite-like decimal' => [['claude' => ['pricing_config' => ['pay_as_you_go' => ['request_token' => ['price' => 'INF']]]]]],
    'negative decimal' => [['claude' => ['pricing_config' => ['pay_as_you_go' => ['request_token' => ['price' => '-0.1']]]]]],
    'unsafe float' => [['claude' => ['pricing_config' => ['pay_as_you_go' => ['request_token' => ['price' => 0.1]]]]]],
    'malformed additional units' => [['claude' => ['pricing_config' => ['pay_as_you_go' => [
        'request_token' => ['price' => '0.1'],
        'additional_units' => ['web_search' => ['price' => []]],
    ]]]]],
]);

it('uses a cached catalog offline without making a request', function (): void {
    Carbon::setTestNow('2026-08-07 12:00:00');
    Http::fake(['https://openrouter.test/*' => Http::response(['data' => [[
        'id' => 'cached/model', 'pricing' => ['prompt' => '0.1'],
    ]]])]);
    openRouterSource()->find(new ModelIdentity('openrouter', 'cached/model'));
    Carbon::setTestNow('2026-08-07 14:00:00');
    Http::preventStrayRequests();

    $definition = openRouterSource(offline: true)->find(new ModelIdentity('openrouter', 'cached/model'));

    expect($definition)->not->toBeNull();
    Http::assertSentCount(1);
});

it('uses expired Portkey last-known-good pricing offline', function (): void {
    Carbon::setTestNow('2026-08-07 12:00:00');
    Http::fake(['https://portkey.test/pricing/anthropic.json' => Http::response([
        'claude' => ['pricing_config' => ['pay_as_you_go' => ['request_token' => ['price' => '0.1']], 'currency' => 'USD']],
    ])]);
    portkeySource()->find(new ModelIdentity('anthropic', 'claude'));
    Carbon::setTestNow('2026-08-07 14:00:00');
    Http::preventStrayRequests();

    expect(portkeySource(offline: true)->find(new ModelIdentity('anthropic', 'claude')))->not->toBeNull();
    Http::assertSentCount(1);
});

it('returns no definition on network failure so missing pricing cannot block', function (): void {
    Http::fake([
        'https://openrouter.test/*' => Http::response([], 503),
    ]);

    expect(openRouterSource()->find(new ModelIdentity('openrouter', 'missing')))->toBeNull();
});
