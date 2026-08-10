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

function openRouterSource(bool $offline = false, string $endpoint = 'https://openrouter.test/api/v1/models'): OpenRouterPricingSource
{
    return new OpenRouterPricingSource(
        app(Factory::class),
        Cache::store(),
        $endpoint,
        3600,
        $offline,
    );
}

/** @param list<string> $providers */
function portkeySource(
    bool $offline = false,
    array $providers = [],
    string $endpoint = 'https://portkey.test/pricing/{provider}.json',
): PortkeyPricingSource {
    return new PortkeyPricingSource(
        app(Factory::class),
        Cache::store(),
        $endpoint,
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

it('does not apply OpenRouter pricing to models invoked through another provider', function (): void {
    Http::fake([
        'https://openrouter.test/api/v1/models' => Http::response([
            'data' => [[
                'id' => 'anthropic/claude-test',
                'pricing' => ['prompt' => '0.000001'],
            ]],
        ]),
    ]);

    $source = openRouterSource();

    expect($source->find(new ModelIdentity('anthropic', 'anthropic/claude-test')))->toBeNull();
    Http::assertNothingSent();

    expect($source->find(new ModelIdentity(' OpenRouter ', 'anthropic/claude-test')))->not->toBeNull();
    Http::assertSentCount(1);
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

it('matches Bedrock usage to its Portkey model catalog', function (): void {
    Http::fake([
        'https://portkey.test/pricing/bedrock.json' => Http::response([
            'amazon.titan-text-lite-v1' => [
                'pricing_config' => [
                    'pay_as_you_go' => [
                        'request_token' => ['price' => '0.000015'],
                        'response_token' => ['price' => '0.00002'],
                    ],
                    'currency' => 'USD',
                ],
            ],
        ]),
    ]);

    $definition = portkeySource()->find(new ModelIdentity('bedrock', 'amazon.titan-text-lite-v1'));

    expect($definition?->source)->toBe(PricingSource::Portkey)
        ->and((string) $definition?->rates['input_tokens']->amount)->toBe('0.00000015')
        ->and((string) $definition?->rates['output_tokens']->amount)->toBe('0.0000002');

    Http::assertSent(fn ($request): bool => $request->url() === 'https://portkey.test/pricing/bedrock.json');
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

it('isolates OpenRouter caches by normalized endpoint and preserves cached source provenance', function (): void {
    $sourceEndpoint = 'https://openrouter.test/api/v1/models';
    Http::fake([$sourceEndpoint => Http::response(['data' => [[
        'id' => 'cached/model', 'pricing' => ['prompt' => '0.1'],
    ]]])]);
    openRouterSource(endpoint: $sourceEndpoint)->find(new ModelIdentity('openrouter', 'cached/model'));
    Http::preventStrayRequests();

    $cached = openRouterSource(
        offline: true,
        endpoint: 'HTTPS://OPENROUTER.TEST/api/v1/models',
    )->find(new ModelIdentity('openrouter', 'cached/model'));
    $unrelated = openRouterSource(
        offline: true,
        endpoint: 'https://other-openrouter.test/api/v1/models',
    )->find(new ModelIdentity('openrouter', 'cached/model'));

    expect($cached?->sourceReference)->toBe($sourceEndpoint)
        ->and($unrelated)->toBeNull();
    Http::assertSentCount(1);
});

it('uses the full OpenRouter URL for requests without exposing endpoint credentials in provenance', function (): void {
    $endpoint = 'https://catalog-user:catalog-pass@openrouter.test/api/v1/models?api-key=super-secret&region=ca';
    Http::fake(['*' => Http::response(['data' => [[
        'id' => 'secure/model', 'pricing' => ['prompt' => '0.1'],
    ]]])]);

    $definition = openRouterSource(endpoint: $endpoint)
        ->find(new ModelIdentity('openrouter', 'secure/model'));
    Http::preventStrayRequests();
    $cached = openRouterSource(offline: true, endpoint: $endpoint)
        ->find(new ModelIdentity('openrouter', 'secure/model'));

    Http::assertSent(fn ($request): bool => $request->url() === $endpoint);
    expect($definition?->sourceReference)
        ->toBe('https://openrouter.test/api/v1/models?api-key=%5BREDACTED%5D&region=ca')
        ->and($cached?->sourceReference)->toBe($definition?->sourceReference)
        ->not->toContain('catalog-user')
        ->not->toContain('catalog-pass')
        ->not->toContain('super-secret');
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

it('isolates Portkey caches by normalized endpoint and preserves cached source provenance', function (): void {
    $sourceEndpoint = 'https://portkey.test/pricing/{provider}.json';
    $sourceUrl = 'https://portkey.test/pricing/anthropic.json';
    Http::fake([$sourceUrl => Http::response([
        'claude' => ['pricing_config' => ['pay_as_you_go' => ['request_token' => ['price' => '0.1']]]],
    ])]);
    portkeySource(endpoint: $sourceEndpoint)->find(new ModelIdentity('Anthropic', 'claude'));
    Http::preventStrayRequests();

    $cached = portkeySource(
        offline: true,
        endpoint: 'HTTPS://PORTKEY.TEST/pricing/{provider}.json',
    )->find(new ModelIdentity(' anthropic ', 'claude'));
    $unrelated = portkeySource(
        offline: true,
        endpoint: 'https://other-portkey.test/pricing/{provider}.json',
    )->find(new ModelIdentity('anthropic', 'claude'));

    expect($cached?->sourceReference)->toBe($sourceUrl)
        ->and($unrelated)->toBeNull();
    Http::assertSentCount(1);
});

it('uses the full Portkey URL for requests without exposing endpoint credentials in provenance', function (): void {
    $endpoint = 'https://catalog-user:catalog-pass@portkey.test/pricing/{provider}.json?access_token=super-secret&region=ca';
    $requestUrl = 'https://catalog-user:catalog-pass@portkey.test/pricing/anthropic.json?access_token=super-secret&region=ca';
    Http::fake(['*' => Http::response([
        'claude' => ['pricing_config' => ['pay_as_you_go' => ['request_token' => ['price' => '0.1']]]],
    ])]);

    $definition = portkeySource(endpoint: $endpoint)
        ->find(new ModelIdentity('anthropic', 'claude'));
    Http::preventStrayRequests();
    $cached = portkeySource(offline: true, endpoint: $endpoint)
        ->find(new ModelIdentity('anthropic', 'claude'));

    Http::assertSent(fn ($request): bool => $request->url() === $requestUrl);
    expect($definition?->sourceReference)
        ->toBe('https://portkey.test/pricing/anthropic.json?access_token=%5BREDACTED%5D&region=ca')
        ->and($cached?->sourceReference)->toBe($definition?->sourceReference)
        ->not->toContain('catalog-user')
        ->not->toContain('catalog-pass')
        ->not->toContain('super-secret');
});

it('returns no definition on network failure so missing pricing cannot block', function (): void {
    Http::fake([
        'https://openrouter.test/*' => Http::response([], 503),
    ]);

    expect(openRouterSource()->find(new ModelIdentity('openrouter', 'missing')))->toBeNull();
});
