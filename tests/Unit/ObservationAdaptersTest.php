<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Http\Client\Response as HttpResponse;
use Illuminate\Support\Collection;
use Jkudish\LaravelAiPricing\Adapters\AmpObservationAdapter;
use Jkudish\LaravelAiPricing\Adapters\ClaudeObservationAdapter;
use Jkudish\LaravelAiPricing\Adapters\CodexObservationAdapter;
use Jkudish\LaravelAiPricing\Adapters\GatewayObservationAdapter;
use Jkudish\LaravelAiPricing\Adapters\LaravelAiObservationAdapter;
use Jkudish\LaravelAiPricing\Adapters\LaravelAiProviderCostExtractor;
use Jkudish\LaravelAiPricing\Adapters\NormalizedObservationAdapter;
use Jkudish\LaravelAiPricing\Contracts\PricingCatalog;
use Jkudish\LaravelAiPricing\CostCalculator;
use Jkudish\LaravelAiPricing\Enums\CostCompleteness;
use Jkudish\LaravelAiPricing\Enums\PricingSource;
use Jkudish\LaravelAiPricing\PricingResolver;
use Jkudish\LaravelAiPricing\Sources\PackagePricingSource;
use Jkudish\LaravelAiPricing\ValueObjects\ModelIdentity;
use Jkudish\LaravelAiPricing\ValueObjects\PriceDefinition;
use Jkudish\LaravelAiPricing\ValueObjects\Rate;
use Jkudish\LaravelAiPricing\ValueObjects\Usage;

it('normalizes common provider usage shapes without losing custom units', function (object $adapter): void {
    $observation = $adapter->adapt([
        'provider' => 'gateway',
        'model' => 'vendor/model',
        'usage' => [
            'prompt_tokens' => 100,
            'completionTokens' => 25,
            'cachedInputTokens' => 40,
            'video_seconds' => '1.5',
        ],
        'provider_cost' => '0.0025',
    ]);

    expect($observation->usage->toArray())->toMatchArray([
        'input_tokens' => '100',
        'output_tokens' => '25',
        'cached_input_tokens' => '40',
        'video_seconds' => '1.5',
    ])->and((string) $observation->providerReportedCost?->amount)->toBe('0.0025');
})->with([
    new NormalizedObservationAdapter,
    new CodexObservationAdapter,
    new ClaudeObservationAdapter,
    new AmpObservationAdapter,
    new GatewayObservationAdapter,
]);

it('structurally adapts Laravel AI usage and meta without requiring laravel ai', function (): void {
    $value = new class
    {
        public string $provider = 'openai';

        public string $model = 'gpt-test';

        /** @var array<string, int> */
        public array $usage = ['inputTokens' => 10, 'outputTokens' => 3];
    };

    $observation = (new LaravelAiObservationAdapter)->adapt($value);

    expect($observation->identity->key())->toBe('openai:gpt-test')
        ->and($observation->usage->toArray())->toMatchArray(['input_tokens' => '10', 'output_tokens' => '3']);
});

it('does not treat a private response property as authoritative provider cost', function (): void {
    $value = new class
    {
        public string $provider = 'openai';

        public string $model = 'gpt-test';

        /** @var array<string, int> */
        public array $usage = ['inputTokens' => 10, 'outputTokens' => 3];

        private string $cost = '999.99';
    };

    $observation = (new LaravelAiObservationAdapter)->adapt($value);

    expect($observation->providerReportedCost)->toBeNull();
});

it('adapts the nested meta and usage shape returned by Laravel AI responses', function (): void {
    $response = new class
    {
        public object $usage;

        public object $meta;

        public function __construct()
        {
            $this->usage = (object) [
                'promptTokens' => 120,
                'completionTokens' => 30,
                'cacheWriteInputTokens' => 10,
                'cacheReadInputTokens' => 20,
                'reasoningTokens' => 5,
            ];
            $this->meta = (object) [
                'provider' => 'openrouter',
                'model' => 'anthropic/claude-test',
            ];
        }
    };

    $observation = (new LaravelAiObservationAdapter)->adapt($response);

    expect($observation->identity->key())->toBe('openrouter:anthropic/claude-test')
        ->and($observation->usage->toArray())->toBe([
            'input_tokens' => '90',
            'output_tokens' => '30',
            'cached_input_tokens' => '20',
            'cache_write_input_tokens' => '10',
            'reasoning_tokens' => '5',
        ]);
});

it('normalizes provider-specific harness units into the shared pricing contract', function (object $adapter, array $usage, array $expected): void {
    $observation = $adapter->adapt([
        'provider' => 'harness',
        'model' => 'model',
        'usage' => $usage,
    ]);

    expect($observation->usage->toArray())->toBe($expected);
})->with([
    'Codex' => [
        new CodexObservationAdapter,
        [
            'input_tokens' => 120,
            'cached_input_tokens' => 32,
            'output_tokens' => 14,
            'reasoning_output_tokens' => 4,
        ],
        [
            'input_tokens' => '120',
            'output_tokens' => '14',
            'cached_input_tokens' => '32',
            'reasoning_tokens' => '4',
        ],
    ],
    'Claude' => [
        new ClaudeObservationAdapter,
        [
            'input_tokens' => 100,
            'output_tokens' => 20,
            'cache_creation_input_tokens' => 15,
            'cache_read_input_tokens' => 30,
        ],
        [
            'input_tokens' => '100',
            'output_tokens' => '20',
            'cached_input_tokens' => '30',
            'cache_write_input_tokens' => '15',
        ],
    ],
    'Amp' => [
        new AmpObservationAdapter,
        [
            'inputTokens' => 100,
            'outputTokens' => 20,
            'cacheCreationInputTokens' => 15,
            'cacheReadInputTokens' => 30,
        ],
        [
            'input_tokens' => '100',
            'output_tokens' => '20',
            'cached_input_tokens' => '30',
            'cache_write_input_tokens' => '15',
        ],
    ],
]);

it('rejects incomplete normalized observations', function (): void {
    expect(fn () => (new NormalizedObservationAdapter)->adapt(['provider' => 'openai']))
        ->toThrow(InvalidArgumentException::class);
});

it('prices the effective routed model while preserving the requested identity', function (): void {
    $observation = (new NormalizedObservationAdapter)->adapt([
        'requested_provider' => 'openai',
        'requested_model' => 'gpt-requested',
        'effective_provider' => 'openrouter',
        'effective_model' => 'anthropic/claude-effective',
        'usage' => ['input_tokens' => 1],
    ]);

    expect($observation->identity->key())->toBe('openrouter:anthropic/claude-effective')
        ->and($observation->requestedIdentity?->key())->toBe('openai:gpt-requested');
});

it('rejects half-present identity pairs', function (array $value): void {
    expect(fn () => (new NormalizedObservationAdapter)->adapt($value + ['usage' => ['input_tokens' => 1]]))
        ->toThrow(InvalidArgumentException::class, 'must be supplied together');
})->with([
    [['effective_provider' => 'openrouter', 'provider' => 'openai', 'model' => 'gpt']],
    [['provider' => 'openai']],
    [['requested_model' => 'gpt']],
]);

it('requires provider cost to retain an authoritative decimal representation', function (mixed $cost, bool $valid): void {
    $adapt = fn () => (new NormalizedObservationAdapter)->adapt([
        'provider' => 'openai', 'model' => 'gpt', 'usage' => ['input_tokens' => 1], 'cost' => $cost,
    ]);

    if ($valid) {
        expect((string) $adapt()->providerReportedCost?->amount)->toBe((string) $cost);
    } else {
        expect($adapt)->toThrow(InvalidArgumentException::class, 'decimal string');
    }
})->with([
    ['0.125', true], [1, true], [0.125, false], [INF, false], [NAN, false],
]);

it('maps real Laravel AI cache usage without double billing provider input', function (string $provider, int $expectedInput, bool $array): void {
    $value = [
        'provider' => $provider,
        'model' => 'model',
        'usage' => [
            'promptTokens' => 100,
            'completionTokens' => 20,
            'cacheWriteInputTokens' => 10,
            'cacheReadInputTokens' => 30,
            'reasoningTokens' => 5,
            'totalTokens' => 160,
        ],
    ];

    if (! $array) {
        $value['usage'] = (object) $value['usage'];
        $value = (object) $value;
    }

    $usage = (new LaravelAiObservationAdapter)->adapt($value)->usage->toArray();

    expect($usage)->toMatchArray([
        'input_tokens' => (string) $expectedInput,
        'output_tokens' => '20',
        'cached_input_tokens' => '30',
        'cache_write_input_tokens' => '10',
        'reasoning_tokens' => '5',
    ])->not->toHaveKey('totalTokens');
})->with([
    'OpenAI object reports exclusive input' => ['openai', 100, false],
    'OpenAI array reports exclusive input' => ['openai', 100, true],
    'Bedrock object reports exclusive input' => ['bedrock', 100, false],
    'Bedrock array reports exclusive input' => ['bedrock', 100, true],
    'OpenRouter object reports inclusive prompt' => ['openrouter', 60, false],
    'OpenRouter array reports inclusive prompt' => ['openrouter', 60, true],
    'Groq object reports inclusive prompt' => ['groq', 60, false],
    'Groq array reports inclusive prompt' => ['groq', 60, true],
    'OpenAI-compatible object reports inclusive prompt' => ['openai-compatible', 60, false],
    'OpenAI-compatible array reports inclusive prompt' => ['openai-compatible', 60, true],
]);

it('uses the actual Laravel AI driver for custom provider token semantics', function (string $driverKey, bool $array): void {
    $value = [
        'provider' => 'local-llama',
        'model' => 'llama-test',
        $driverKey => 'openai-compatible',
        'usage' => [
            'promptTokens' => 100,
            'cacheWriteInputTokens' => 10,
            'cacheReadInputTokens' => 30,
        ],
    ];

    if (! $array) {
        $value['usage'] = (object) $value['usage'];
        $value = (object) $value;
    }

    expect((new LaravelAiObservationAdapter)->adapt($value)->usage->toArray())->toMatchArray([
        'input_tokens' => '60',
        'cached_input_tokens' => '30',
        'cache_write_input_tokens' => '10',
    ]);
})->with([
    'driver on array' => ['driver', true],
    'provider_driver on array' => ['provider_driver', true],
    'providerDriver on object' => ['providerDriver', false],
]);

it('supports injected Laravel AI provider-to-driver mappings', function (): void {
    $adapter = new LaravelAiObservationAdapter(providerDrivers: ['local-llama' => 'openai-compatible']);
    $observation = $adapter->adapt([
        'provider' => 'local-llama',
        'model' => 'llama-test',
        'usage' => ['promptTokens' => 100, 'cacheReadInputTokens' => 30, 'cacheWriteInputTokens' => 10],
    ]);

    expect($observation->usage->toArray()['input_tokens'])->toBe('60');
});

it('gives an explicit Laravel AI input token semantic precedence and validates it', function (): void {
    $observation = (new LaravelAiObservationAdapter)->adapt([
        'provider' => 'local-llama',
        'model' => 'llama-test',
        'driver' => 'openai-compatible',
        'input_token_semantic' => 'exclusive',
        'usage' => ['promptTokens' => 100, 'cacheReadInputTokens' => 30, 'cacheWriteInputTokens' => 10],
    ]);

    expect($observation->usage->toArray()['input_tokens'])->toBe('100')
        ->and(fn () => (new LaravelAiObservationAdapter)->adapt([
            'provider' => 'local-llama',
            'model' => 'llama-test',
            'inputTokenSemantic' => 'unknown',
            'usage' => ['promptTokens' => 100],
        ]))->toThrow(InvalidArgumentException::class, 'must be either [inclusive] or [exclusive]');
});

it('uses provider-reported OpenRouter cost from every public Laravel AI step', function (): void {
    $response = new class
    {
        public object $usage;

        public object $meta;

        /** @var list<object> */
        public array $steps;

        public function __construct()
        {
            $this->usage = (object) ['promptTokens' => 20, 'completionTokens' => 5];
            $this->meta = (object) ['provider' => 'openrouter', 'model' => 'openai/gpt-test'];
            $this->steps = [
                (object) ['raw' => new ProviderResponseFixture(['usage' => ['cost' => '0.0000012']])],
                (object) ['raw' => new ProviderResponseFixture(['usage' => ['cost' => 0.0000034]])],
            ];
        }
    };

    $observation = (new LaravelAiObservationAdapter)->adapt($response);

    expect($observation->providerReportedCost?->toArray())->toBe([
        'amount' => '0.0000046',
        'currency' => 'USD',
    ]);
});

it('reads OpenRouter cost from real Illuminate HTTP responses and collections', function (): void {
    $response = (object) [
        'usage' => (object) ['promptTokens' => 10, 'completionTokens' => 2],
        'meta' => (object) ['provider' => 'openrouter', 'model' => 'openai/gpt-test'],
        'steps' => new Collection([
            (object) ['raw' => new HttpResponse(new Psr7Response(
                body: '{"usage":{"cost":0.0000042}}',
                headers: ['Content-Type' => 'application/json'],
            ))],
        ]),
    ];

    expect((new LaravelAiObservationAdapter)->adapt($response)->providerReportedCost?->toArray())->toBe([
        'amount' => '0.0000042',
        'currency' => 'USD',
    ]);
});

it('does not misrepresent a partial multi-step provider cost as authoritative', function (): void {
    $response = (object) [
        'usage' => (object) ['promptTokens' => 20, 'completionTokens' => 5],
        'meta' => (object) ['provider' => 'openrouter', 'model' => 'openai/gpt-test'],
        'steps' => [
            (object) ['raw' => new ProviderResponseFixture(['usage' => ['cost' => '0.1']])],
            (object) ['raw' => new ProviderResponseFixture(['usage' => ['prompt_tokens' => 10]])],
        ],
    ];

    expect((new LaravelAiObservationAdapter)->adapt($response)->providerReportedCost)->toBeNull();
});

it('only extracts monetary cost from providers with an explicit response contract', function (string $provider, array $payload): void {
    $response = (object) [
        'usage' => (object) ['promptTokens' => 10, 'completionTokens' => 2],
        'meta' => (object) ['provider' => $provider, 'model' => 'model'],
        'raw' => new ProviderResponseFixture($payload),
    ];

    expect((new LaravelAiObservationAdapter)->adapt($response)->providerReportedCost)->toBeNull();
})->with([
    'OpenAI usage' => ['openai', ['usage' => ['prompt_tokens' => 10, 'completion_tokens' => 2]]],
    'Anthropic usage' => ['anthropic', ['usage' => ['input_tokens' => 10, 'output_tokens' => 2]]],
    'Gemini usage' => ['gemini', ['usageMetadata' => ['promptTokenCount' => 10, 'candidatesTokenCount' => 2]]],
    'Groq usage' => ['groq', ['usage' => ['prompt_tokens' => 10, 'completion_tokens' => 2]]],
    'DeepSeek usage' => ['deepseek', ['usage' => ['prompt_tokens' => 10, 'completion_tokens' => 2]]],
    'Mistral usage' => ['mistral', ['usage' => ['prompt_tokens' => 10, 'completion_tokens' => 2]]],
    'xAI usage' => ['xai', ['usage' => ['prompt_tokens' => 10, 'completion_tokens' => 2]]],
    'Cohere billed units' => ['cohere', ['meta' => ['billed_units' => ['input_tokens' => 10, 'output_tokens' => 2]]]],
    'Bedrock usage' => ['bedrock', ['usage' => ['inputTokens' => 10, 'outputTokens' => 2]]],
]);

it('maps Laravel AI embedding token usage without inventing output usage', function (): void {
    $response = (object) [
        'tokens' => 42,
        'meta' => (object) ['provider' => 'bedrock', 'model' => 'amazon.titan-embed-text-v1'],
    ];

    expect((new LaravelAiObservationAdapter)->adapt($response)->usage->toArray())->toBe([
        'input_tokens' => '42',
        'cached_input_tokens' => '0',
        'cache_write_input_tokens' => '0',
    ]);
});

it('rejects malformed Laravel AI embedding token usage', function (mixed $tokens): void {
    $response = (object) [
        'tokens' => $tokens,
        'meta' => (object) ['provider' => 'bedrock', 'model' => 'amazon.titan-embed-text-v1'],
    ];

    expect(fn () => (new LaravelAiObservationAdapter)->adapt($response))
        ->toThrow(InvalidArgumentException::class, 'embedding token usage');
})->with([
    'negative' => -1,
    'numeric string' => '42',
    'float' => 42.0,
    'null' => null,
    'boolean' => true,
]);

it('prefers explicit Laravel AI usage over an unrelated tokens field', function (): void {
    $response = (object) [
        'tokens' => 999,
        'usage' => (object) ['promptTokens' => 12, 'completionTokens' => 3],
        'meta' => (object) ['provider' => 'openai', 'model' => 'gpt-test'],
    ];

    expect((new LaravelAiObservationAdapter)->adapt($response)->usage->toArray())->toMatchArray([
        'input_tokens' => '12',
        'output_tokens' => '3',
    ])->not->toHaveKey('tokens');
});

it('supports explicit provider cost paths for custom Laravel AI drivers', function (): void {
    $extractor = new LaravelAiProviderCostExtractor([
        'private-gateway' => ['path' => 'billing.amount', 'currency' => 'CAD'],
    ]);
    $adapter = new LaravelAiObservationAdapter(providerCosts: $extractor);
    $response = (object) [
        'usage' => (object) ['promptTokens' => 10],
        'meta' => (object) ['provider' => 'private-gateway', 'model' => 'model'],
        'raw' => new ProviderResponseFixture(['billing' => ['amount' => '0.25']]),
    ];

    expect($adapter->adapt($response)->providerReportedCost?->toArray())->toBe([
        'amount' => '0.25',
        'currency' => 'CAD',
    ]);
});

it('degrades malformed provider cost to usage pricing without throwing', function (mixed $cost): void {
    $response = (object) [
        'usage' => (object) ['promptTokens' => 10],
        'meta' => (object) ['provider' => 'openrouter', 'model' => 'model'],
        'raw' => new ProviderResponseFixture(['usage' => ['cost' => $cost]]),
    ];

    expect((new LaravelAiObservationAdapter)->adapt($response)->providerReportedCost)->toBeNull();
})->with([
    'negative' => '-0.1',
    'not numeric' => 'N/A',
    'unbounded exponent' => '1e-40000000',
    'overlong decimal' => str_repeat('1', 65),
]);

it('extracts provider floats independently of the PHP serialization precision', function (): void {
    $previous = ini_set('serialize_precision', '17');

    try {
        $response = (object) [
            'usage' => (object) ['promptTokens' => 10],
            'meta' => (object) ['provider' => 'openrouter', 'model' => 'model'],
            'raw' => new ProviderResponseFixture(['usage' => ['cost' => 0.1]]),
        ];

        expect((string) (new LaravelAiObservationAdapter)->adapt($response)->providerReportedCost?->amount)->toBe('0.1');
    } finally {
        if ($previous !== false) {
            ini_set('serialize_precision', $previous);
        }
    }
});

it('requires Laravel AI observation properties to be public', function (): void {
    $response = new class
    {
        private object $usage;

        public object $meta;

        public function __construct()
        {
            $this->usage = (object) ['promptTokens' => 10];
            $this->meta = (object) ['provider' => 'openrouter', 'model' => 'model'];
        }
    };

    expect(fn () => (new LaravelAiObservationAdapter)->adapt($response))
        ->toThrow(InvalidArgumentException::class, 'does not expose usage metadata');
});

it('validates custom provider cost mappings when they are configured', function (array $providers): void {
    expect(fn () => new LaravelAiProviderCostExtractor($providers))
        ->toThrow(InvalidArgumentException::class);
})->with([
    'empty path' => [['provider' => ['path' => '']]],
    'invalid currency' => [['provider' => ['path' => 'billing.cost', 'currency' => 'US']]],
    'numeric provider' => [[0 => ['path' => 'billing.cost']]],
    'duplicate normalized provider' => [[
        'provider' => ['path' => 'billing.cost'],
        'PROVIDER' => ['path' => 'usage.cost'],
    ]],
]);

it('normalizes custom provider mapping names and supports bounded tiny costs', function (): void {
    $extractor = new LaravelAiProviderCostExtractor([
        'Private-Gateway' => ['path' => 'billing.amount', 'currency' => 'usd'],
    ]);
    $response = (object) ['raw' => new ProviderResponseFixture(['billing' => ['amount' => '1e-19']])];

    expect($extractor->extract($response, 'private-gateway')?->toArray())->toBe([
        'amount' => '0.0000000000000000001',
        'currency' => 'USD',
    ]);
});

final class ProviderResponseFixture
{
    /** @param array<string, mixed> $payload */
    public function __construct(private readonly array $payload) {}

    /** @return array<string, mixed> */
    public function json(): array
    {
        return $this->payload;
    }
}

it('maps laravel/ai 1.0 usage totals without double billing cached input tokens', function (bool $object, bool $nullCache): void {
    $usage = [
        'inputTokens' => 100,
        'outputTokens' => 20,
        'cacheReadInputTokens' => 30,
        'cacheWriteInputTokens' => 10,
        'reasoningTokens' => 5,
    ];

    $value = [
        // The openai driver is not in the historical inclusive-prompt driver list; only
        // the laravel/ai 1.0 dialect makes this input count a cache-inclusive total.
        'provider' => 'openai',
        'model' => 'gpt-test',
        'usage' => $usage,
    ];

    if ($nullCache) {
        // laravel/ai 1.0 serializes unreported cache and reasoning counts as nulls.
        $value['usage'] = [
            'input_tokens' => 100,
            'output_tokens' => 20,
            'cache_read_input_tokens' => 30,
            'cache_write_input_tokens' => null,
            'reasoning_tokens' => null,
        ];
    }

    if ($object) {
        $value['usage'] = (object) $value['usage'];
        $value = (object) $value;
    }

    $expected = [
        // inputTokens is a cache-inclusive total: 100 - 30 read - write (10 or 0 when null).
        'input_tokens' => $nullCache ? '70' : '60',
        'output_tokens' => '20',
        'cached_input_tokens' => '30',
        'cache_write_input_tokens' => $nullCache ? '0' : '10',
    ];

    if (! $nullCache) {
        $expected['reasoning_tokens'] = '5';
    }

    $units = (new LaravelAiObservationAdapter)->adapt($value)->usage->toArray();

    expect($units)->toMatchArray($expected);

    if ($nullCache) {
        // A serialized null reasoning count disappears entirely rather than
        // surviving as a zero unit.
        expect($units)->not->toHaveKey('reasoning_tokens');
    }
})->with([
    '1.0 object usage' => [true, false],
    '1.0 array usage' => [false, false],
    '1.0 serialized usage with null cache counts' => [false, true],
]);

it('bills laravel/ai 1.0 input totals once at each cache rate', function (bool $object): void {
    $usage = [
        'inputTokens' => 100,
        'outputTokens' => 20,
        'cacheReadInputTokens' => 30,
        'cacheWriteInputTokens' => 10,
        'reasoningTokens' => 5,
    ];

    $value = [
        'provider' => 'openai',
        'model' => 'gpt-test',
        'usage' => $object ? (object) $usage : $usage,
    ];

    // Catalog rates mirror OpenAI GPT-5 mini class pricing: $0.60/M uncached input,
    // $0.075/M cached input, $4.80/M output, and a $0/M cache write surcharge baseline.
    // Expected cost, computed by hand from the 1.0 semantics (input includes cache;
    // output includes reasoning, and OpenAI bills reasoning as output tokens, so the
    // OpenAI-mirroring catalog deliberately omits a reasoning_tokens rate and the
    // whole inclusive output count settles at the output rate):
    //   uncached input  = 100 - 30 - 10 = 60 tokens -> 60 * 0.60   / 1M = 0.000036
    //   cached input    = 30 tokens                   -> 30 * 0.075 / 1M = 0.00000225
    //   cache write     = 10 tokens                   -> 10 * 0     / 1M = 0
    //   output          = 20 tokens (includes the 5 reasoning)    -> 20 * 4.8 / 1M = 0.000096
    //   total           = 0.000036 + 0.00000225 + 0 + 0.000096 = 0.00013425
    // The pre-fix exclusive interpretation would bill 100 uncached input tokens
    // (0.00006) on top of the 30 cached and 10 written tokens: 0.00015825. A
    // reasoning_tokens rate of 0 would instead bill OpenAI reasoning for free,
    // so catalogs that bill reasoning within the output rate must omit the rate.
    $definition = new PriceDefinition(
        new ModelIdentity('openai', 'gpt-test'),
        [
            'input_tokens' => new Rate('input_tokens', '0.60', '1000000'),
            'cached_input_tokens' => new Rate('cached_input_tokens', '0.075', '1000000'),
            'cache_write_input_tokens' => new Rate('cache_write_input_tokens', '0', '1000000'),
            'output_tokens' => new Rate('output_tokens', '4.8', '1000000'),
        ],
        PricingSource::Configured,
    );
    $catalog = new class($definition) implements PricingCatalog
    {
        public function __construct(private readonly PriceDefinition $definition) {}

        public function find(ModelIdentity $identity): ?PriceDefinition
        {
            return $this->definition;
        }

        public function sync(): int
        {
            return 0;
        }
    };

    $observation = (new LaravelAiObservationAdapter)->adapt($value);
    $quote = (new PricingResolver($catalog, $catalog, $catalog))->resolve($observation);

    expect((string) $quote->cost?->amount)->toBe('0.00013425')
        ->and($quote->missingUnits)->toBe([]);
})->with([
    '1.0 object usage' => [true],
    '1.0 array usage' => [false],
]);

it('bills reasoning once as a partition of the inclusive output count', function (): void {
    // laravel/ai 1.0 reports outputTokens as a total that includes reasoning tokens,
    // exactly as the 0.x completionTokens did for SDK-parsed responses, and OpenAI,
    // Anthropic, Gemini and OpenRouter all bill reasoning as part of that inclusive
    // count. The calculator therefore prices the family as a partition instead of
    // additive units: (output - reasoning) at the output rate plus reasoning at the
    // reasoning rate when the catalog publishes one.
    $observation = (new LaravelAiObservationAdapter)->adapt([
        'provider' => 'openrouter',
        'model' => 'reasoning-test',
        'usage' => [
            'inputTokens' => 10,
            'outputTokens' => 20,
            'reasoningTokens' => 5,
        ],
    ]);

    $definition = new PriceDefinition(
        new ModelIdentity('openrouter', 'reasoning-test'),
        [
            'input_tokens' => new Rate('input_tokens', '1', '1000000'),
            'output_tokens' => new Rate('output_tokens', '2', '1000000'),
            'reasoning_tokens' => new Rate('reasoning_tokens', '3', '1000000'),
        ],
        PricingSource::Configured,
    );

    // Hand-computed: 10 * 1/1M + (20 - 5) * 2/1M + 5 * 3/1M = 0.00001 + 0.00003 + 0.000015.
    // The previous additive reading billed the inclusive 20 output tokens AND the
    // 5 reasoning tokens they contain: 0.000065, charging the reasoning twice.
    $cost = (new CostCalculator)->calculate($observation->usage, $definition)->cost;

    expect((string) $cost?->amount)->toBe('0.000055');
});

it('bills the reasoning subset exactly once across the output token family', function (array $rates, array $units, string $cost, array $missing): void {
    $definition = new PriceDefinition(
        new ModelIdentity('openrouter', 'reasoning-test'),
        $rates,
        PricingSource::Configured,
    );

    $quote = (new CostCalculator)->calculate(new Usage($units), $definition);

    expect((string) $quote->cost?->amount)->toBe($cost)
        ->and($quote->missingUnits)->toBe($missing);
})->with([
    // 10 * 1/1M + (20 - 5) * 2/1M + 5 * 3/1M = 0.00001 + 0.00003 + 0.000015.
    // The additive reading re-bills the 5 reasoning tokens the output count
    // already contains: 0.000065.
    'non-zero reasoning rate partitions the inclusive output count' => [
        [
            'input_tokens' => new Rate('input_tokens', '1', '1000000'),
            'output_tokens' => new Rate('output_tokens', '2', '1000000'),
            'reasoning_tokens' => new Rate('reasoning_tokens', '3', '1000000'),
        ],
        ['input_tokens' => 10, 'output_tokens' => 20, 'reasoning_tokens' => 5],
        '0.000055',
        [],
    ],
    // 10 * 1/1M + (20 - 5) * 4.8/1M + 5 * 0/1M = 0.00001 + 0.000072.
    // A published zero reasoning rate is a deliberate claim that reasoning is
    // free and replaces the output rate for those tokens. Under the old
    // additive semantics a zero rate was the natural way to spell "no extra
    // charge" for reasoning billed within the output rate — such catalogs must
    // drop the rate, or their reasoning now bills at $0.
    'zero reasoning rate deliberately bills reasoning as free' => [
        [
            'input_tokens' => new Rate('input_tokens', '1', '1000000'),
            'output_tokens' => new Rate('output_tokens', '4.8', '1000000'),
            'reasoning_tokens' => new Rate('reasoning_tokens', '0', '1000000'),
        ],
        ['input_tokens' => 10, 'output_tokens' => 20, 'reasoning_tokens' => 5],
        '0.000082',
        [],
    ],
    // 10 * 1/1M + 20 * 2/1M = 0.00005. Without a reasoning rate the whole
    // inclusive output count bills at the output rate and reasoning is not
    // reported missing, so a catalog omission cannot make reasoning free.
    // Before the fix this quote was Partial with missingUnits [reasoning_tokens].
    'missing reasoning rate falls back to the output rate' => [
        [
            'input_tokens' => new Rate('input_tokens', '1', '1000000'),
            'output_tokens' => new Rate('output_tokens', '2', '1000000'),
        ],
        ['input_tokens' => 10, 'output_tokens' => 20, 'reasoning_tokens' => 5],
        '0.00005',
        [],
    ],
    // 10 * 1/1M + 20 * 2/1M = 0.00005. Without a reported reasoning count the
    // output count bills whole, unchanged from before.
    'payload without reported reasoning bills the whole output count' => [
        [
            'input_tokens' => new Rate('input_tokens', '1', '1000000'),
            'output_tokens' => new Rate('output_tokens', '2', '1000000'),
            'reasoning_tokens' => new Rate('reasoning_tokens', '3', '1000000'),
        ],
        ['input_tokens' => 10, 'output_tokens' => 20],
        '0.00005',
        [],
    ],
]);

it('prices an unreported output count by standing reasoning in at the output rate', function (): void {
    // A payload that reports only a reasoning count must not become unpriceable
    // when the catalog has no reasoning rate: the reasoning count is the
    // output-family total. 7 * 2/1M = 0.000014.
    $definition = new PriceDefinition(
        new ModelIdentity('openrouter', 'reasoning-test'),
        ['output_tokens' => new Rate('output_tokens', '2', '1000000')],
        PricingSource::Configured,
    );

    $quote = (new CostCalculator)->calculate(new Usage(['reasoning_tokens' => 7]), $definition);

    expect((string) $quote->cost?->amount)->toBe('0.000014')
        ->and($quote->missingUnits)->toBe([]);
});

it('caps the partition remainder at zero when reasoning exceeds the output count', function (): void {
    // A payload whose reasoning count exceeds its output count cannot bill a
    // negative remainder: max(0, 3 - 5) * 2/1M + 5 * 3/1M = 0.000015, where the
    // additive reading would have billed 0.000021.
    $definition = new PriceDefinition(
        new ModelIdentity('openrouter', 'reasoning-test'),
        [
            'output_tokens' => new Rate('output_tokens', '2', '1000000'),
            'reasoning_tokens' => new Rate('reasoning_tokens', '3', '1000000'),
        ],
        PricingSource::Configured,
    );

    $quote = (new CostCalculator)->calculate(new Usage(['output_tokens' => 3, 'reasoning_tokens' => 5]), $definition);

    expect((string) $quote->cost?->amount)->toBe('0.000015');
});

it('folds an explicit exclusive reasoning semantic into the reported output count', function (string $semanticKey): void {
    // A payload whose output count excludes reasoning folds the reasoning count
    // into whichever output alias it reports, so the calculator still prices a
    // single inclusive partition: 10 * 1/1M + (20 - 5) * 2/1M + 5 * 3/1M = 0.000055.
    $observation = (new LaravelAiObservationAdapter)->adapt([
        'provider' => 'openrouter',
        'model' => 'reasoning-test',
        $semanticKey => 'exclusive',
        'usage' => [
            'inputTokens' => 10,
            'outputTokens' => 15,
            'reasoningTokens' => 5,
        ],
    ]);

    expect($observation->usage->toArray())->toMatchArray([
        'input_tokens' => '10',
        'output_tokens' => '20',
        'reasoning_tokens' => '5',
    ]);

    $definition = new PriceDefinition(
        new ModelIdentity('openrouter', 'reasoning-test'),
        [
            'input_tokens' => new Rate('input_tokens', '1', '1000000'),
            'output_tokens' => new Rate('output_tokens', '2', '1000000'),
            'reasoning_tokens' => new Rate('reasoning_tokens', '3', '1000000'),
        ],
        PricingSource::Configured,
    );

    $cost = (new CostCalculator)->calculate($observation->usage, $definition)->cost;

    expect((string) $cost?->amount)->toBe('0.000055');
})->with([
    ['reasoning_token_semantic'],
    ['reasoningTokenSemantic'],
]);

it('keeps the inclusive reasoning semantic the default', function (): void {
    // Without a semantic the reported output count is trusted as inclusive:
    // 10 * 1/1M + (15 - 5) * 2/1M + 5 * 3/1M = 0.000045.
    $observation = (new LaravelAiObservationAdapter)->adapt([
        'provider' => 'openrouter',
        'model' => 'reasoning-test',
        'reasoning_token_semantic' => 'inclusive',
        'usage' => [
            'inputTokens' => 10,
            'outputTokens' => 15,
            'reasoningTokens' => 5,
        ],
    ]);

    $definition = new PriceDefinition(
        new ModelIdentity('openrouter', 'reasoning-test'),
        [
            'input_tokens' => new Rate('input_tokens', '1', '1000000'),
            'output_tokens' => new Rate('output_tokens', '2', '1000000'),
            'reasoning_tokens' => new Rate('reasoning_tokens', '3', '1000000'),
        ],
        PricingSource::Configured,
    );

    $cost = (new CostCalculator)->calculate($observation->usage, $definition)->cost;

    expect((string) $cost?->amount)->toBe('0.000045');
});

it('rejects an invalid reasoning token semantic', function (): void {
    expect(fn () => (new LaravelAiObservationAdapter)->adapt([
        'provider' => 'openrouter',
        'model' => 'reasoning-test',
        'reasoning_token_semantic' => 'sometimes',
        'usage' => [
            'inputTokens' => 10,
            'outputTokens' => 20,
        ],
    ]))->toThrow(InvalidArgumentException::class, 'must be either [inclusive] or [exclusive]');
});

it('folds reasoning into the output count for laravel/ai 0.x gemini and xai drivers by default', function (string $provider): void {
    // The laravel/ai 0.x Gemini and xAI drivers kept the thought or reasoning
    // count outside the completion count, so the adapter folds it back in.
    // Without the fold the partition would silently under-bill: the payload
    // would look like 15 inclusive output tokens when the true total is 20.
    $observation = (new LaravelAiObservationAdapter)->adapt([
        'provider' => $provider,
        'model' => 'reasoning-test',
        'usage' => [
            'promptTokens' => 10,
            'completionTokens' => 15,
            'reasoningTokens' => 5,
        ],
    ]);

    expect($observation->usage->toArray())->toEqual([
        'input_tokens' => '10',
        'output_tokens' => '20',
        'cached_input_tokens' => '0',
        'cache_write_input_tokens' => '0',
        'reasoning_tokens' => '5',
    ]);

    // Hand-computed with an input rate of $1/M, an output rate of $10/M, and
    // no reasoning rate: 10 * 1/1M + 20 * 10/1M = 0.00001 + 0.0002 = 0.00021.
    // The unfolded reading bills 15 * 10/1M, a 25% output under-bill.
    $definition = new PriceDefinition(
        new ModelIdentity($provider, 'reasoning-test'),
        [
            'input_tokens' => new Rate('input_tokens', '1', '1000000'),
            'output_tokens' => new Rate('output_tokens', '10', '1000000'),
        ],
        PricingSource::Configured,
    );

    $quote = (new CostCalculator)->calculate($observation->usage, $definition);

    expect((string) $quote->cost?->amount)->toBe('0.00021')
        ->and($quote->missingUnits)->toBe([]);
})->with([['gemini'], ['xai']]);

it('keeps an explicit inclusive semantic unfolded for a 0.x exclusive driver', function (): void {
    // The gemini driver folds by default because its 0.x dialect reports the
    // completion count exclusive of reasoning. A caller that knows better —
    // for example a payload whose counts already include reasoning — can say
    // so, and the explicit semantic must beat the driver default.
    $observation = (new LaravelAiObservationAdapter)->adapt([
        'provider' => 'gemini',
        'model' => 'reasoning-test',
        'reasoning_token_semantic' => 'inclusive',
        'usage' => [
            'promptTokens' => 10,
            'completionTokens' => 15,
            'reasoningTokens' => 5,
        ],
    ]);

    expect($observation->usage->toArray())->toEqual([
        'input_tokens' => '10',
        'output_tokens' => '15',
        'cached_input_tokens' => '0',
        'cache_write_input_tokens' => '0',
        'reasoning_tokens' => '5',
    ]);
});

it('folds the serialized 0.x usage form the 0.11.2 SDK emits', function (): void {
    // laravel/ai 0.11.2's Usage::toArray() serializes to the snake_case
    // spellings, so a persisted or forwarded observation carries
    // prompt_tokens/completion_tokens/reasoning_tokens. The xAI driver kept
    // reasoning outside the completion count, so that form folds too.
    $observation = (new LaravelAiObservationAdapter)->adapt([
        'provider' => 'xai',
        'model' => 'reasoning-test',
        'usage' => [
            'prompt_tokens' => 10,
            'completion_tokens' => 15,
            'reasoning_tokens' => 5,
        ],
    ]);

    expect($observation->usage->toArray())->toEqual([
        'input_tokens' => '10',
        'output_tokens' => '20',
        'cached_input_tokens' => '0',
        'cache_write_input_tokens' => '0',
        'reasoning_tokens' => '5',
    ]);
});

it('does not fold reasoning again for laravel/ai 1.0 gemini usage', function (): void {
    // The 1.0 SDK already folds thought tokens into the output total, so the
    // 1.0 dialect never folds regardless of driver.
    $observation = (new LaravelAiObservationAdapter)->adapt([
        'provider' => 'gemini',
        'model' => 'reasoning-test',
        'usage' => [
            'inputTokens' => 10,
            'outputTokens' => 15,
            'reasoningTokens' => 5,
        ],
    ]);

    expect($observation->usage->toArray())->toEqual([
        'input_tokens' => '10',
        'output_tokens' => '15',
        'cached_input_tokens' => '0',
        'cache_write_input_tokens' => '0',
        'reasoning_tokens' => '5',
    ]);
});

it('resolves the exclusive fold with the same aliases and order as normalization', function (array $usage): void {
    // Numeric strings and mixed spellings must fold exactly like integer
    // camelCase payloads: the fold reads the value normalization would choose
    // (alias order output_tokens, completion_tokens, outputTokens,
    // completionTokens) and rewrites it canonically as output_tokens.
    $observation = (new LaravelAiObservationAdapter)->adapt([
        'provider' => 'openrouter',
        'model' => 'reasoning-test',
        'reasoning_token_semantic' => 'exclusive',
        'usage' => $usage,
    ]);

    expect($observation->usage->toArray())->toMatchArray([
        'output_tokens' => '20',
        'reasoning_tokens' => '5',
    ]);

    // Hand-computed: input 10 * 1/1M + 15 * 2/1M + 5 * 3/1M = 0.000055. A fold
    // that silently skipped these spellings would partition an unfolded 15
    // into 10 + 5 and bill the same total here, but a payload whose reasoning
    // rides outside the output count would lose 5 * output rate.
    $definition = new PriceDefinition(
        new ModelIdentity('openrouter', 'reasoning-test'),
        [
            'input_tokens' => new Rate('input_tokens', '1', '1000000'),
            'output_tokens' => new Rate('output_tokens', '2', '1000000'),
            'reasoning_tokens' => new Rate('reasoning_tokens', '3', '1000000'),
        ],
        PricingSource::Configured,
    );

    $cost = (new CostCalculator)->calculate($observation->usage, $definition)->cost;

    expect((string) $cost?->amount)->toBe('0.000055');
})->with([
    'snake_case numeric strings' => [['input_tokens' => 10, 'output_tokens' => '15', 'reasoning_tokens' => '5']],
    'mixed camel and snake aliases' => [['promptTokens' => 10, 'outputTokens' => '15', 'completionTokens' => 9, 'reasoningOutputTokens' => '5']],
]);

it('refuses to fold exclusive reasoning without a reported output count', function (): void {
    expect(fn () => (new LaravelAiObservationAdapter)->adapt([
        'provider' => 'openrouter',
        'model' => 'reasoning-test',
        'reasoning_token_semantic' => 'exclusive',
        'usage' => ['input_tokens' => 10, 'reasoning_tokens' => 5],
    ]))->toThrow(InvalidArgumentException::class, 'require a reported output token count');
});

it('keeps the legacy dialect when a payload mixes old and new usage keys', function (string $provider, string $expectedOutput): void {
    // A payload that reports both spellings speaks the 0.x dialect: the prompt
    // key wins for input, the outputTokens alias wins for output (matching
    // normalization's alias order, not the completion spelling), and the gemini
    // driver default folds reasoning into that same resolved output count.
    $observation = (new LaravelAiObservationAdapter)->adapt([
        'provider' => $provider,
        'model' => 'reasoning-test',
        'usage' => [
            'promptTokens' => 100,
            'inputTokens' => 80,
            'completionTokens' => 20,
            'outputTokens' => 25,
            'reasoningTokens' => 5,
        ],
    ]);

    expect($observation->usage->toArray())->toMatchArray([
        'input_tokens' => '100',
        'output_tokens' => $expectedOutput,
        'reasoning_tokens' => '5',
    ]);
})->with([
    'inclusive driver keeps the reported output count' => ['openrouter', '25'],
    'gemini folds reasoning into the resolved output count' => ['gemini', '30'],
]);

it('validates an explicit driver even when the dialect needs no driver heuristic', function (): void {
    expect(fn () => (new LaravelAiObservationAdapter)->adapt([
        'provider' => 'openai',
        'model' => 'driver-test',
        'driver' => 123,
        'usage' => ['inputTokens' => 10, 'outputTokens' => 20],
    ]))->toThrow(InvalidArgumentException::class, 'provider driver must be a non-empty string');
});

it('stands reasoning in as the output total when it exceeds the reported output count without a reasoning rate', function (): void {
    // No reasoning rate is published, so the output-family total is the larger
    // of the two counts: max(3, 5) * 2/1M = 0.00001.
    $definition = new PriceDefinition(
        new ModelIdentity('openrouter', 'reasoning-test'),
        ['output_tokens' => new Rate('output_tokens', '2', '1000000')],
        PricingSource::Configured,
    );

    $quote = (new CostCalculator)->calculate(new Usage(['output_tokens' => 3, 'reasoning_tokens' => 5]), $definition);

    expect((string) $quote->cost?->amount)->toBe('0.00001')
        ->and($quote->missingUnits)->toBe([]);
});

it('prices a reasoning-only payload at its own rate when one is published', function (): void {
    // No output count is reported, so the partition remainder clamps to zero
    // and the 7 reasoning tokens bill exactly once at their own rate:
    // max(0, 0 - 7) * 2/1M + 7 * 3/1M = 0.000021.
    $definition = new PriceDefinition(
        new ModelIdentity('openrouter', 'reasoning-test'),
        [
            'output_tokens' => new Rate('output_tokens', '2', '1000000'),
            'reasoning_tokens' => new Rate('reasoning_tokens', '3', '1000000'),
        ],
        PricingSource::Configured,
    );

    $quote = (new CostCalculator)->calculate(new Usage(['reasoning_tokens' => 7]), $definition);

    expect((string) $quote->cost?->amount)->toBe('0.000021')
        ->and($quote->missingUnits)->toBe([]);
});

it('lets an explicit input token semantic override the laravel/ai 1.0 dialect', function (): void {
    $observation = (new LaravelAiObservationAdapter)->adapt([
        'provider' => 'bedrock',
        'model' => 'anthropic.test',
        'input_token_semantic' => 'exclusive',
        'usage' => [
            'inputTokens' => 100,
            'outputTokens' => 20,
            'cacheReadInputTokens' => 30,
            'cacheWriteInputTokens' => 10,
        ],
    ]);

    expect($observation->usage->toArray()['input_tokens'])->toBe('100');
});

it('keeps raw Anthropic-shaped usage exclusive of cache tokens', function (): void {
    // Provider-native Anthropic payloads report input_tokens that excludes cache
    // reads and use the cache_creation_input_tokens spelling, so they must not be
    // confused with the laravel/ai 1.0 serialized dialect.
    $observation = (new LaravelAiObservationAdapter)->adapt([
        'provider' => 'anthropic',
        'model' => 'claude-test',
        'usage' => [
            'input_tokens' => 100,
            'output_tokens' => 20,
            'cache_read_input_tokens' => 30,
            'cache_creation_input_tokens' => 10,
        ],
    ]);

    expect($observation->usage->toArray())->toMatchArray([
        'input_tokens' => '100',
        'cached_input_tokens' => '30',
        'cache_write_input_tokens' => '10',
    ]);
});

/**
 * The 1-hour cache duration example usage from
 * https://platform.claude.com/docs/en/build-with-claude/prompt-caching, where
 * cache_creation_input_tokens equals the sum of the cache_creation split.
 *
 * @return array<string, mixed>
 */
function anthropicTtlUsage(bool $nested = true): array
{
    $usage = [
        'input_tokens' => 2048,
        'cache_read_input_tokens' => 1800,
        'cache_creation_input_tokens' => 248,
        'output_tokens' => 503,
    ];

    if ($nested) {
        $usage['cache_creation'] = ['ephemeral_5m_input_tokens' => 148, 'ephemeral_1h_input_tokens' => 100];
    }

    return $usage;
}

function anthropicTtlBasis(): PriceDefinition
{
    /** @var array{version: int, retrieved_at: string, effective_at: string|null, currency: string, fallback_blocked?: list<string>, aliases?: array<string, string>, prices: array<string, array{source: string, notes?: string, rates: array<string, array{amount: string|int, per: string|int}>}>} $snapshot */
    $snapshot = require __DIR__.'/../../resources/pricing/provider-skus.php';
    $definition = (new PackagePricingSource($snapshot))->find(new ModelIdentity('anthropic', 'claude-sonnet-5-5-global-standard'));
    assert($definition !== null);

    return $definition;
}

function genericCacheWriteDefinition(): PriceDefinition
{
    return new PriceDefinition(
        new ModelIdentity('anthropic', 'claude-catalog-test'),
        [
            'input_tokens' => new Rate('input_tokens', '2', '1000000'),
            'output_tokens' => new Rate('output_tokens', '10', '1000000'),
            'cached_input_tokens' => new Rate('cached_input_tokens', '0.10', '1000000'),
            'cache_write_input_tokens' => new Rate('cache_write_input_tokens', '2.50', '1000000'),
        ],
        PricingSource::Portkey,
    );
}

it('maps the nested Anthropic cache_creation TTL split next to the aggregate cache write count', function (object $adapter, bool $objectBreakdown): void {
    $usage = anthropicTtlUsage();

    if ($objectBreakdown) {
        $usage['cache_creation'] = (object) $usage['cache_creation'];
    }

    $observation = $adapter->adapt(['provider' => 'anthropic', 'model' => 'claude-sonnet-5-5-global-standard', 'usage' => $usage]);

    expect($observation->usage->toArray())->toBe([
        'input_tokens' => '2048',
        'output_tokens' => '503',
        'cached_input_tokens' => '1800',
        'cache_write_input_tokens' => '248',
        'cache_write_input_tokens_5m' => '148',
        'cache_write_input_tokens_1h' => '100',
    ]);
})->with([
    'normalized, array split' => [new NormalizedObservationAdapter, false],
    'normalized, object split' => [new NormalizedObservationAdapter, true],
    'Claude harness, array split' => [new ClaudeObservationAdapter, false],
    'Laravel AI raw Anthropic usage, array split' => [new LaravelAiObservationAdapter, false],
    'Laravel AI raw Anthropic usage, object split' => [new LaravelAiObservationAdapter, true],
]);

it('keeps the Anthropic usage mapping unchanged when no TTL split is reported', function (object $adapter): void {
    $observation = $adapter->adapt(['provider' => 'anthropic', 'model' => 'claude-test', 'usage' => anthropicTtlUsage(nested: false)]);

    expect($observation->usage->toArray())->toBe([
        'input_tokens' => '2048',
        'output_tokens' => '503',
        'cached_input_tokens' => '1800',
        'cache_write_input_tokens' => '248',
    ]);
})->with([
    'normalized' => [new NormalizedObservationAdapter],
    'Claude harness' => [new ClaudeObservationAdapter],
    'Laravel AI' => [new LaravelAiObservationAdapter],
]);

it('lets explicit flat TTL cache-write units win over the nested split', function (): void {
    $observation = (new NormalizedObservationAdapter)->adapt([
        'provider' => 'anthropic',
        'model' => 'claude-test',
        'usage' => [...anthropicTtlUsage(), 'cache_write_input_tokens_5m' => 48],
    ]);

    expect($observation->usage->toArray())->toMatchArray([
        'cache_write_input_tokens' => '248',
        'cache_write_input_tokens_5m' => '48',
        'cache_write_input_tokens_1h' => '100',
    ]);
});

it('quotes an Anthropic 5.5 response with TTL cache writes as complete on its TTL-only basis', function (object $adapter): void {
    $observation = $adapter->adapt(['provider' => 'anthropic', 'model' => 'claude-sonnet-5-5-global-standard', 'usage' => anthropicTtlUsage()]);
    $quote = (new CostCalculator)->calculate($observation->usage, anthropicTtlBasis());

    // Sonnet 5.5 global standard, USD per million: 2048 x 2 input + 1800 x 0.10
    // cache read + 148 x 2.50 five-minute write + 100 x 4 one-hour write + 503 x 10
    // output = 0.004096 + 0.00018 + 0.00037 + 0.0004 + 0.00503 = 0.010076.
    expect((string) $quote->cost?->amount)->toBe('0.010076')
        ->and($quote->completeness)->toBe(CostCompleteness::Complete)
        ->and($quote->missingUnits)->toBe([]);
})->with([
    'normalized' => [new NormalizedObservationAdapter],
    'Claude harness' => [new ClaudeObservationAdapter],
    'Laravel AI' => [new LaravelAiObservationAdapter],
]);

it('keeps a generic cache_write_input_tokens count partial on a TTL-only basis', function (object $adapter, array $usage): void {
    $observation = $adapter->adapt(['provider' => 'anthropic', 'model' => 'claude-sonnet-5-5-global-standard', 'usage' => $usage]);
    $quote = (new CostCalculator)->calculate($observation->usage, anthropicTtlBasis());

    // Without a TTL split the 248 written tokens cannot be priced: 0.004096
    // input + 0.00018 cache read + 0.00503 output, and the write stays missing.
    expect((string) $quote->cost?->amount)->toBe('0.009306')
        ->and($quote->completeness)->toBe(CostCompleteness::Partial)
        ->and($quote->missingUnits)->toBe(['cache_write_input_tokens']);
})->with([
    'Laravel AI raw Anthropic aggregate only' => [new LaravelAiObservationAdapter, anthropicTtlUsage(nested: false)],
    'Claude harness aggregate only' => [new ClaudeObservationAdapter, anthropicTtlUsage(nested: false)],
    'normalized generic unit' => [new NormalizedObservationAdapter, ['input_tokens' => 2048, 'cached_input_tokens' => 1800, 'cache_write_input_tokens' => 248, 'output_tokens' => 503]],
]);

it('bills the aggregate at a generic cache-write rate exactly as before when the split is mapped', function (): void {
    $calculator = new CostCalculator;
    $adapter = new NormalizedObservationAdapter;
    $withSplit = $calculator->calculate(
        $adapter->adapt(['provider' => 'anthropic', 'model' => 'claude-catalog-test', 'usage' => anthropicTtlUsage()])->usage,
        genericCacheWriteDefinition(),
    );
    $withoutSplit = $calculator->calculate(
        $adapter->adapt(['provider' => 'anthropic', 'model' => 'claude-catalog-test', 'usage' => anthropicTtlUsage(nested: false)])->usage,
        genericCacheWriteDefinition(),
    );

    // 0.004096 input + 0.00018 cache read + 248 x 2.50 = 0.00062 write + 0.00503 output.
    expect((string) $withSplit->cost?->amount)->toBe('0.009926')
        ->and($withSplit->completeness)->toBe(CostCompleteness::Complete)
        ->and($withSplit->missingUnits)->toBe([])
        ->and($withSplit->cost?->toArray())->toBe($withoutSplit->cost?->toArray())
        ->and($withoutSplit->completeness)->toBe(CostCompleteness::Complete);
});

it('settles the cache-write family once without double billing the split or guessing a TTL', function (array $units, ?string $amount, array $missing): void {
    $definition = new PriceDefinition(
        new ModelIdentity('anthropic', 'claude-family-test'),
        [
            'cache_write_input_tokens_5m' => new Rate('cache_write_input_tokens_5m', '5', '1000000'),
            'cache_write_input_tokens_1h' => new Rate('cache_write_input_tokens_1h', '8', '1000000'),
        ],
        PricingSource::Configured,
    );
    $quote = (new CostCalculator)->calculate(new Usage($units), $definition);

    expect($quote->cost === null ? null : (string) $quote->cost->amount)->toBe($amount)
        ->and($quote->missingUnits)->toBe($missing);
})->with([
    'split covering the aggregate' => [['cache_write_input_tokens' => 300, 'cache_write_input_tokens_5m' => 200, 'cache_write_input_tokens_1h' => 100], '0.0018', []],
    'split without an aggregate' => [['cache_write_input_tokens_5m' => 200], '0.001', []],
    'aggregate larger than the split leaves an unknown-TTL remainder' => [['cache_write_input_tokens' => 350, 'cache_write_input_tokens_5m' => 200, 'cache_write_input_tokens_1h' => 100], '0.0018', ['cache_write_input_tokens']],
    'aggregate smaller than the split bills the split' => [['cache_write_input_tokens' => 100, 'cache_write_input_tokens_5m' => 200, 'cache_write_input_tokens_1h' => 100], '0.0018', []],
    'aggregate only' => [['cache_write_input_tokens' => 300], null, ['cache_write_input_tokens']],
]);

it('does not price a TTL subset at a generic rate unless the aggregate was reported', function (): void {
    $quote = (new CostCalculator)->calculate(
        new Usage(['cache_write_input_tokens_1h' => 100]),
        genericCacheWriteDefinition(),
    );

    expect($quote->cost)->toBeNull()
        ->and($quote->completeness)->toBe(CostCompleteness::Unavailable)
        ->and($quote->missingUnits)->toBe(['cache_write_input_tokens_1h']);
});

/**
 * A laravel/ai 1.0 Anthropic TextUsage: the inclusive input total and the
 * cacheWriteInputTokens aggregate, with the TTL split dropped.
 */
function laravelAiAnthropicResponse(mixed $raw = null, mixed $steps = null): object
{
    return (object) [
        'usage' => (object) [
            'inputTokens' => 4096,
            'outputTokens' => 503,
            'cacheReadInputTokens' => 1800,
            'cacheWriteInputTokens' => 248,
            'reasoningTokens' => null,
        ],
        'meta' => (object) ['provider' => 'anthropic', 'model' => 'claude-sonnet-5-5-global-standard'],
        'raw' => $raw,
        'steps' => $steps,
    ];
}

it('recovers the TTL split from a non-streamed laravel/ai Anthropic raw response', function (): void {
    $raw = new HttpResponse(new Psr7Response(
        body: (string) json_encode(['usage' => anthropicTtlUsage()]),
        headers: ['Content-Type' => 'application/json'],
    ));
    $observation = (new LaravelAiObservationAdapter)->adapt(laravelAiAnthropicResponse(raw: $raw));
    $quote = (new CostCalculator)->calculate($observation->usage, anthropicTtlBasis());

    expect($observation->usage->toArray())->toMatchArray([
        'input_tokens' => '2048',
        'cached_input_tokens' => '1800',
        'cache_write_input_tokens' => '248',
        'cache_write_input_tokens_5m' => '148',
        'cache_write_input_tokens_1h' => '100',
    ])
        ->and((string) $quote->cost?->amount)->toBe('0.010076')
        ->and($quote->completeness)->toBe(CostCompleteness::Complete);
});

it('sums the TTL split across every laravel/ai step raw response', function (): void {
    $steps = new Collection([
        (object) ['raw' => new ProviderResponseFixture(['usage' => ['cache_creation' => ['ephemeral_5m_input_tokens' => 148, 'ephemeral_1h_input_tokens' => 0]]])],
        (object) ['raw' => new ProviderResponseFixture(['usage' => ['cache_creation' => ['ephemeral_5m_input_tokens' => 0, 'ephemeral_1h_input_tokens' => 100]]])],
    ]);
    $finalStepOnly = new ProviderResponseFixture(['usage' => ['cache_creation' => ['ephemeral_5m_input_tokens' => 0, 'ephemeral_1h_input_tokens' => 100]]]);

    $usage = (new LaravelAiObservationAdapter)->adapt(laravelAiAnthropicResponse(raw: $finalStepOnly, steps: $steps))->usage->toArray();

    expect($usage)->toMatchArray([
        'cache_write_input_tokens' => '248',
        'cache_write_input_tokens_5m' => '148',
        'cache_write_input_tokens_1h' => '100',
    ]);
});

it('ignores a raw TTL split that cannot be attributed to the reported aggregate', function (mixed $raw, mixed $steps): void {
    $usage = (new LaravelAiObservationAdapter)->adapt(laravelAiAnthropicResponse(raw: $raw, steps: $steps))->usage->toArray();

    expect($usage)->toMatchArray(['cache_write_input_tokens' => '248'])
        ->not->toHaveKey('cache_write_input_tokens_5m')
        ->not->toHaveKey('cache_write_input_tokens_1h');
})->with([
    'streamed response without raw' => [null, null],
    'final step raw only covers part of the aggregate' => [new ProviderResponseFixture(['usage' => ['cache_creation' => ['ephemeral_5m_input_tokens' => 0, 'ephemeral_1h_input_tokens' => 100]]]), null],
    'one step without raw' => [null, [
        (object) ['raw' => new ProviderResponseFixture(['usage' => ['cache_creation' => ['ephemeral_5m_input_tokens' => 148, 'ephemeral_1h_input_tokens' => 100]]])],
        (object) ['raw' => null],
    ]],
    'non-integer split' => [new ProviderResponseFixture(['usage' => ['cache_creation' => ['ephemeral_5m_input_tokens' => '148', 'ephemeral_1h_input_tokens' => 100]]]), null],
    'raw without a split' => [new ProviderResponseFixture(['usage' => ['cache_creation_input_tokens' => 248]]), null],
]);
