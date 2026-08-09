<?php

declare(strict_types=1);

use Jkudish\LaravelAiPricing\Adapters\AmpObservationAdapter;
use Jkudish\LaravelAiPricing\Adapters\ClaudeObservationAdapter;
use Jkudish\LaravelAiPricing\Adapters\CodexObservationAdapter;
use Jkudish\LaravelAiPricing\Adapters\GatewayObservationAdapter;
use Jkudish\LaravelAiPricing\Adapters\LaravelAiObservationAdapter;
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
