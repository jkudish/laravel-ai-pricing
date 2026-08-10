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
    ]);
})->with([
    'OpenAI object reports exclusive input' => ['openai', 100, false],
    'OpenAI array reports exclusive input' => ['openai', 100, true],
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
]);

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
]);

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
