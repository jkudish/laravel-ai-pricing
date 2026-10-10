<?php

declare(strict_types=1);

use Illuminate\Cache\NullStore;
use Illuminate\Cache\Repository;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Jkudish\LaravelAiPricing\CostCalculator;
use Jkudish\LaravelAiPricing\Enums\CostCompleteness;
use Jkudish\LaravelAiPricing\Enums\PricingSource;
use Jkudish\LaravelAiPricing\Facades\AiPricing;
use Jkudish\LaravelAiPricing\PricingResolver;
use Jkudish\LaravelAiPricing\Sources\ConfiguredPricingSource;
use Jkudish\LaravelAiPricing\Sources\OpenRouterPricingSource;
use Jkudish\LaravelAiPricing\Sources\PortkeyPricingSource;
use Jkudish\LaravelAiPricing\ValueObjects\ModelIdentity;
use Jkudish\LaravelAiPricing\ValueObjects\PricingObservation;
use Jkudish\LaravelAiPricing\ValueObjects\Usage;

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

it('maps a non-zero internal_reasoning price to a reasoning rate and skips a zero one', function (): void {
    Http::fake([
        'https://openrouter.test/api/v1/models' => Http::response([
            'data' => [
                [
                    'id' => 'perplexity/sonar-deep-research',
                    'pricing' => [
                        'prompt' => '0.000001',
                        'completion' => '0.000008',
                        'internal_reasoning' => '0.000003',
                    ],
                ],
                [
                    'id' => 'openai/gpt-test',
                    'pricing' => [
                        'prompt' => '0.000001',
                        'completion' => '0.000008',
                        'internal_reasoning' => '0',
                    ],
                ],
            ],
        ]),
    ]);

    $source = openRouterSource();
    $partitioned = $source->find(new ModelIdentity('openrouter', 'perplexity/sonar-deep-research'));
    $zeroed = $source->find(new ModelIdentity('openrouter', 'openai/gpt-test'));

    // A non-zero internal_reasoning price is the model's own rate for the
    // reasoning subset, so it maps to reasoning_tokens and the calculator
    // prices the output family as a partition. A zero internal_reasoning price
    // means reasoning bills inside the completion price — not that reasoning
    // is free — so it must not become a published zero reasoning_tokens rate,
    // which the partition would honor as deliberately free reasoning.
    expect((string) $partitioned?->rates['reasoning_tokens']->amount)->toBe('0.000003')
        ->and($partitioned?->rates)->toHaveKeys(['output_tokens', 'reasoning_tokens'])
        ->and($zeroed?->rates)->not->toHaveKey('reasoning_tokens')
        ->and((string) $zeroed?->rates['output_tokens']->amount)->toBe('0.000008');
});

it('excludes a model whose internal_reasoning amount is unusable from the catalog', function (string $amount): void {
    Http::fake([
        'https://openrouter.test/api/v1/models' => Http::response([
            'data' => [
                [
                    'id' => 'openai/gpt-broken',
                    'pricing' => [
                        'prompt' => '0.000001',
                        'completion' => '0.000008',
                        'internal_reasoning' => $amount,
                    ],
                ],
                [
                    'id' => 'openai/gpt-test',
                    'pricing' => [
                        'prompt' => '0.000001',
                        'completion' => '0.000008',
                    ],
                ],
            ],
        ]),
    ]);

    $source = openRouterSource();

    // An empty or negative amount is a broken catalog entry, not a zero
    // price. The zero-skip check no longer parses the raw amount itself, so
    // the wrapped UnexpectedValueException — not brick's NumberFormatException
    // — reaches usableModels' per-model guard and the entry is skipped the
    // same way as any other malformed price: the model fails closed as
    // missing pricing rather than being quoted or crashing the retrieval.
    expect($source->find(new ModelIdentity('openrouter', 'openai/gpt-broken')))->toBeNull()
        ->and((string) $source->find(new ModelIdentity('openrouter', 'openai/gpt-test'))?->rates['output_tokens']->amount)->toBe('0.000008');
})->with([[''], ['-1']]);

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

it('adapts Laravel provider names only inside Portkey lookup', function (string $observed, string $catalogProvider): void {
    Http::fake([
        "https://portkey.test/pricing/{$catalogProvider}.json" => Http::response([
            'matching-model' => [
                'pricing_config' => [
                    'pay_as_you_go' => ['request_token' => ['price' => '0.1']],
                    'currency' => 'USD',
                ],
            ],
        ]),
    ]);

    $identity = new ModelIdentity($observed, 'matching-model');
    $definition = portkeySource()->find($identity);

    expect($definition?->identity)->toBe($identity)
        ->and($definition?->identity->provider)->toBe($observed)
        ->and($definition?->sourceReference)->toBe("https://portkey.test/pricing/{$catalogProvider}.json");
    Http::assertSent(fn ($request): bool => $request->url() === "https://portkey.test/pricing/{$catalogProvider}.json");
})->with([
    'Gemini' => ['gemini', 'google'],
    'xAI' => ['xai', 'x-ai'],
    'Perplexity exact model' => ['perplexity', 'perplexity-ai'],
]);

it('canonicalizes Portkey aliases before cache lookup', function (): void {
    Http::fake([
        'https://portkey.test/pricing/google.json' => Http::response([
            'gemini-test' => ['pricing_config' => ['pay_as_you_go' => ['request_token' => ['price' => '0.1']]]],
        ]),
    ]);

    portkeySource()->find(new ModelIdentity('gemini', 'gemini-test'));
    Http::preventStrayRequests();

    $definition = portkeySource(offline: true)->find(new ModelIdentity('google', 'gemini-test'));

    expect($definition)->not->toBeNull()
        ->and($definition?->identity->provider)->toBe('google')
        ->and($definition?->sourceReference)->toBe('https://portkey.test/pricing/google.json');
    Http::assertSentCount(1);
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

it('does not let the live OpenRouter catalog price the fallback-blocked generic Gemini 3.1 Flash Lite identity', function (): void {
    Http::fake([
        'https://openrouter.ai/*' => Http::response(['data' => [
            [
                'id' => 'google/gemini-3.1-flash-lite',
                'pricing' => [
                    'prompt' => '0.00000025',
                    'completion' => '0.0000015',
                    'input_cache_read' => '0.000000025',
                    'input_cache_write' => '0.0000000833333333333333',
                    'web_search' => '0.014',
                ],
            ],
            [
                'id' => 'google/gemini-3.1-flash-lite-preview',
                'pricing' => ['prompt' => '0.00000025', 'completion' => '0.0000015'],
            ],
        ]]),
    ]);

    $usage = new Usage(['input_tokens' => 1_000_000, 'output_tokens' => 100_000]);
    $generic = AiPricing::quote('openrouter', 'google/gemini-3.1-flash-lite', $usage);
    $basis = AiPricing::quote('openrouter', 'google/gemini-3.1-flash-lite-standard-max', $usage);
    $unblocked = AiPricing::quote('openrouter', 'google/gemini-3.1-flash-lite-preview', $usage);

    // Basis: 1M x 0.275/M + 0.1M x 1.65/M = 0.44. Live catalog for the
    // unblocked preview: 1M x 0.25/M + 0.1M x 1.50/M = 0.4.
    expect($generic->amount)->toBeNull()
        ->and($generic->completeness->value)->toBe('unavailable')
        ->and($basis->amount?->isEqualTo('0.44'))->toBeTrue()
        ->and($basis->completeness->value)->toBe('complete')
        ->and($unblocked->amount?->isEqualTo('0.4'))->toBeTrue()
        ->and($unblocked->source->value)->toBe('provider_native');
});

it('prices OpenRouter cache writes by TTL when the catalog publishes a 1-hour rate', function (): void {
    Http::fake([
        'https://openrouter.test/api/v1/models' => Http::response(['data' => [
            [
                'id' => 'anthropic/claude-test',
                'pricing' => [
                    'prompt' => '0.000002',
                    'completion' => '0.00001',
                    'input_cache_write' => '0.0000025',
                    'input_cache_write_1h' => '0.000004',
                ],
            ],
            [
                'id' => 'openai/gpt-test',
                'pricing' => ['prompt' => '0.000002', 'input_cache_write' => '0.0000025'],
            ],
        ]]),
    ]);

    $source = openRouterSource();
    $ttl = $source->find(new ModelIdentity('openrouter', 'anthropic/claude-test'));
    $generic = $source->find(new ModelIdentity('openrouter', 'openai/gpt-test'));
    assert($ttl !== null && $generic !== null);

    $split = (new CostCalculator)->calculate(new Usage([
        'cache_write_input_tokens' => 1_000_000,
        'cache_write_input_tokens_5m' => 400_000,
        'cache_write_input_tokens_1h' => 600_000,
    ]), $ttl);
    $aggregateOnly = (new CostCalculator)->calculate(new Usage([
        'input_tokens' => 1_000_000,
        'cache_write_input_tokens' => 1_000_000,
    ]), $ttl);
    $single = (new CostCalculator)->calculate(new Usage(['cache_write_input_tokens' => 1_000_000]), $generic);

    // 0.4M x 2.5/M + 0.6M x 4/M = 1 + 2.4 = 3.4. Billing the whole aggregate at
    // the 5-minute rate would quote 2.5 and understate the 1-hour writes.
    expect(array_keys($ttl->rates))->toBe(['input_tokens', 'output_tokens', 'cache_write_input_tokens_5m', 'cache_write_input_tokens_1h'])
        ->and((string) $split->cost?->amount)->toBe('3.4')
        ->and($split->completeness)->toBe(CostCompleteness::Complete)
        // An aggregate with no TTL split cannot pick a rate: input 1M x 2/M = 2 only.
        ->and((string) $aggregateOnly->cost?->amount)->toBe('2')
        ->and($aggregateOnly->completeness)->toBe(CostCompleteness::Partial)
        ->and($aggregateOnly->missingUnits)->toBe(['cache_write_input_tokens'])
        // A catalog with one cache-write rate keeps billing the aggregate at it.
        ->and((string) $single->cost?->amount)->toBe('2.5')
        ->and($single->completeness)->toBe(CostCompleteness::Complete);
});

it('leaves OpenRouter Google cache writes unpriced', function (string $model): void {
    Http::fake([
        'https://openrouter.test/api/v1/models' => Http::response(['data' => [[
            'id' => $model,
            'pricing' => [
                'prompt' => '0.00000075',
                'completion' => '0.00000375',
                'input_cache_read' => '0.000000075',
                'input_cache_write' => '0.0000000416666666666667',
            ],
        ]]]),
    ]);

    $definition = openRouterSource()->find(new ModelIdentity('openrouter', $model));
    assert($definition !== null);
    $quote = (new CostCalculator)->calculate(new Usage([
        'input_tokens' => 1_000_000,
        'cache_write_input_tokens' => 1_000_000,
    ]), $definition);

    // The catalog's write rate is five minutes of storage only, while
    // OpenRouter's caching guide also bills the input price, so the write stays
    // missing instead of quoting 0.75 + 0.0416... as complete.
    expect($definition->rates)->not->toHaveKey('cache_write_input_tokens')
        ->and((string) $quote->cost?->amount)->toBe('0.75')
        ->and($quote->completeness)->toBe(CostCompleteness::Partial)
        ->and($quote->missingUnits)->toBe(['cache_write_input_tokens']);
})->with(['google/gemini-test', '~google/gemini-flash-latest']);

it('does not price image or audio counts at OpenRouter per-token modality rates', function (): void {
    Http::fake([
        'https://openrouter.test/api/v1/models' => Http::response(['data' => [[
            'id' => 'google/gemini-test',
            'pricing' => [
                'prompt' => '0.00000075',
                'completion' => '0.00000375',
                'image' => '0.00000075',
                'audio' => '0.0000015',
            ],
        ]]]),
    ]);

    $definition = openRouterSource()->find(new ModelIdentity('openrouter', 'google/gemini-test'));
    assert($definition !== null);
    $quote = (new CostCalculator)->calculate(new Usage([
        'input_tokens' => 1_000,
        'images' => 2,
        'audio_seconds' => 30,
    ]), $definition);

    // 1,000 x 0.00000075 = 0.00075. The image and audio fields price tokens of
    // that modality, so two images and 30 seconds of audio stay missing rather
    // than costing 2 x 0.00000075 and 30 x 0.0000015.
    expect(array_keys($definition->rates))->toBe(['input_tokens', 'output_tokens'])
        ->and((string) $quote->cost?->amount)->toBe('0.00075')
        ->and($quote->completeness)->toBe(CostCompleteness::Partial)
        ->and($quote->missingUnits)->toBe(['images', 'audio_seconds']);
});

it('fails closed on OpenRouter pricing overrides instead of quoting the base tier or the fallback catalog', function (mixed $overrides): void {
    Http::fake([
        'https://openrouter.ai/*' => Http::response(['data' => [
            [
                'id' => 'google/gemini-tiered',
                'pricing' => [
                    'prompt' => '0.000002',
                    'completion' => '0.000012',
                    'overrides' => $overrides,
                ],
            ],
            [
                'id' => 'google/gemini-flat',
                'pricing' => ['prompt' => '0.000002', 'completion' => '0.000012', 'overrides' => []],
            ],
        ]]),
        'https://configs.portkey.ai/pricing/openrouter.json' => Http::response([
            'google/gemini-tiered' => ['pricing_config' => ['pay_as_you_go' => [
                'request_token' => ['price' => '0.0002'],
                'response_token' => ['price' => '0.0012'],
            ], 'currency' => 'USD']],
        ]),
    ]);

    $usage = new Usage(['input_tokens' => 300_000, 'output_tokens' => 1_000]);
    $tiered = AiPricing::quote('openrouter', 'google/gemini-tiered', $usage);
    $flat = AiPricing::quote('openrouter', 'google/gemini-flat', $usage);
    $source = app(OpenRouterPricingSource::class);

    // Base tier: 300,000 x 0.000002 + 1,000 x 0.000012 = 0.612, which would
    // understate a prompt above the 200,000-token override. Portkey lists the
    // same flat rate, so neither catalog may quote it.
    expect($tiered->amount)->toBeNull()
        ->and($tiered->completeness)->toBe(CostCompleteness::Unavailable)
        ->and($source->allowsFallback(new ModelIdentity('openrouter', 'google/gemini-tiered')))->toBeFalse()
        ->and($flat->amount?->isEqualTo('0.612'))->toBeTrue()
        ->and($flat->source)->toBe(PricingSource::ProviderNative)
        ->and($source->allowsFallback(new ModelIdentity('openrouter', 'google/gemini-flat')))->toBeTrue()
        ->and($source->allowsFallback(new ModelIdentity('openrouter', 'google/gemini-unlisted')))->toBeTrue();
})->with([
    'prompt-length tier' => [[['min_prompt_tokens' => 200000, 'prompt' => '0.000004', 'completion' => '0.000018']]],
    'time-of-day tier' => [[['utc_start' => '01:00', 'utc_end' => '04:00', 'prompt' => '0.000001']]],
    'malformed overrides' => ['unexpected'],
]);

function tieredOpenRouterResolver(OpenRouterPricingSource $openRouter): PricingResolver
{
    return new PricingResolver(
        configured: new ConfiguredPricingSource([]),
        native: $openRouter,
        fallback: portkeySource(endpoint: 'https://portkey.test/pricing/{provider}.json'),
    );
}

function portkeyPricesTieredModelFlat(): void
{
    Http::fake([
        'https://portkey.test/pricing/openrouter.json' => Http::response([
            'google/gemini-tiered' => ['pricing_config' => ['pay_as_you_go' => [
                'request_token' => ['price' => '0.0002'],
                'response_token' => ['price' => '0.0012'],
            ], 'currency' => 'USD']],
        ]),
    ]);
}

it('keeps refusing an OpenRouter model with overrides when its base rates are unusable, online and offline', function (array $pricing): void {
    Http::fake([
        'https://openrouter.test/api/v1/models' => Http::response(['data' => [
            ['id' => 'google/gemini-tiered', 'pricing' => [...$pricing, 'overrides' => [['min_prompt_tokens' => 200000, 'prompt' => '0.000004']]]],
            ['id' => 'google/gemini-flat', 'pricing' => ['prompt' => '0.000002']],
        ]]),
    ]);
    portkeyPricesTieredModelFlat();

    $observation = new PricingObservation(new ModelIdentity('openrouter', 'google/gemini-tiered'), new Usage(['input_tokens' => 1_000]));
    $online = tieredOpenRouterResolver(openRouterSource())->resolve($observation);
    // The last-known-good catalog written by the online read must keep the refusal.
    $offline = tieredOpenRouterResolver(openRouterSource(offline: true))->resolve($observation);

    expect($online->completeness)->toBe(CostCompleteness::Unavailable)
        ->and($online->amount)->toBeNull()
        ->and($offline->completeness)->toBe(CostCompleteness::Unavailable)
        ->and($offline->amount)->toBeNull();
})->with([
    'malformed base rate' => [['prompt' => 'free']],
    'no base rates' => [[]],
]);

it('keeps the refusal from the lookup when the fallback decision cannot reread the catalog', function (): void {
    Http::fake([
        'https://openrouter.test/api/v1/models' => Http::sequence()
            ->push(['data' => [['id' => 'google/gemini-tiered', 'pricing' => [
                'prompt' => '0.000002',
                'overrides' => [['min_prompt_tokens' => 200000, 'prompt' => '0.000004']],
            ]]]])
            ->push([], 503),
    ]);
    portkeyPricesTieredModelFlat();

    // A cache that retains nothing forces every catalog read back to the network.
    $openRouter = new OpenRouterPricingSource(app(Factory::class), new Repository(new NullStore), 'https://openrouter.test/api/v1/models', 3600, false);
    $quote = tieredOpenRouterResolver($openRouter)->resolve(new PricingObservation(
        new ModelIdentity('openrouter', 'google/gemini-tiered'),
        new Usage(['input_tokens' => 1_000]),
    ));

    expect($quote->completeness)->toBe(CostCompleteness::Unavailable)
        ->and($quote->amount)->toBeNull();
});

it('lets the fallback catalog price an OpenRouter model when no refusal is known', function (): void {
    Http::fake(['https://openrouter.test/*' => Http::response([], 503)]);
    portkeyPricesTieredModelFlat();

    $quote = tieredOpenRouterResolver(openRouterSource())->resolve(new PricingObservation(
        new ModelIdentity('openrouter', 'google/gemini-tiered'),
        new Usage(['input_tokens' => 1_000]),
    ));

    // Portkey cents per token: 1,000 x 0.0002 / 100 = 0.002.
    expect($quote->amount?->isEqualTo('0.002'))->toBeTrue()
        ->and($quote->source)->toBe(PricingSource::Portkey);
});
