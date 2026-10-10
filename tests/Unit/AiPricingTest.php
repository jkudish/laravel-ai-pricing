<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Http\Client\Response as HttpResponse;
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
            $this->usage = (object) ['promptTokens' => 1_000, 'completionTokens' => 250];
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

/**
 * Mirror the public shape of laravel/ai 1.0's ClassificationResponse: readonly
 * answers, a TextUsage whose cache and reasoning counts are null, and a Meta
 * carrying the provider, the model the API reported, and citations.
 */
function typesafeClassificationResponse(string $model, int $inputTokens, int $outputTokens): object
{
    return new class($model, $inputTokens, $outputTokens)
    {
        /** @var array<string, object> */
        public readonly array $answers;

        public readonly object $usage;

        public readonly object $meta;

        public function __construct(string $model, int $inputTokens, int $outputTokens)
        {
            $this->answers = ['is_urgent' => (object) ['type' => 'noul', 'noul' => 0.97]];
            $this->usage = (object) [
                'inputTokens' => $inputTokens,
                'outputTokens' => $outputTokens,
                'cacheReadInputTokens' => null,
                'cacheWriteInputTokens' => null,
                'reasoningTokens' => null,
            ];
            $this->meta = (object) ['provider' => 'typesafe', 'model' => $model, 'citations' => collect()];
        }
    };
}

it('prices a completed TypeSafe Jev classification response from its reported versioned model', function (): void {
    $cost = AiPricing::cost(typesafeClassificationResponse('jev-1.13.0', 312, 48));

    // 312 x 0.042 / 1_000_000; the 48 output tokens are free, not missing.
    expect((string) $cost->amount)->toBe('0.000013104')
        ->and($cost->currency)->toBe('USD')
        ->and($cost->source->value)->toBe('provider_native')
        ->and($cost->completeness->value)->toBe('complete')
        ->and($cost->missingUnits)->toBe([])
        ->and($cost->snapshot?->definition->identity->toArray())->toBe(['provider' => 'typesafe', 'model' => 'jev-1.13.0']);
});

it('prices a Jev response that echoes the requested alias when the API omitted its model', function (): void {
    $cost = AiPricing::cost(typesafeClassificationResponse('jev-latest', 1_000_000, 10));

    expect((string) $cost->amount)->toBe('0.042')
        ->and($cost->completeness->value)->toBe('complete')
        ->and($cost->snapshot?->definition->identity->toArray())->toBe(['provider' => 'typesafe', 'model' => 'jev-latest']);
});

it('quotes a Jev request before dispatch for the alias laravel/ai sends by default', function (): void {
    $alias = AiPricing::quote('typesafe', 'jev-latest', new Usage(['input_tokens' => 312, 'output_tokens' => 48]));
    $pinned = AiPricing::quote('typesafe', 'jev-1.13.0', new Usage(['input_tokens' => 312]));

    expect((string) $alias->amount)->toBe('0.000013104')
        ->and($alias->completeness->value)->toBe('complete')
        ->and((string) $pinned->amount)->toBe('0.000013104')
        ->and($pinned->completeness->value)->toBe('complete');
});

it('keeps an unreviewed Jev version unavailable rather than free', function (): void {
    $cost = AiPricing::cost(typesafeClassificationResponse('jev-2.0.0', 312, 48));
    $quote = AiPricing::quote('typesafe', 'jev-2.0.0', new Usage(['input_tokens' => 312]));

    expect($cost->amount)->toBeNull()
        ->and($cost->completeness->value)->toBe('unavailable')
        ->and($quote->amount)->toBeNull()
        ->and($quote->completeness->value)->toBe('unavailable');
});

it('lets a configured Jev account rate override the reviewed snapshot', function (): void {
    config()->set('ai-pricing.prices', [
        'typesafe:jev-1.13.0' => [
            'input_tokens' => ['amount' => '0.03', 'per' => '1000000'],
            'output_tokens' => ['amount' => '0', 'per' => '1000000'],
        ],
    ]);
    app()->forgetInstance(ConfiguredPricingSource::class);
    app()->forgetInstance(CostResolver::class);
    app()->forgetInstance(ResponseCostResolver::class);

    $cost = AiPricing::cost(typesafeClassificationResponse('jev-1.13.0', 1_000_000, 48));

    expect((string) $cost->amount)->toBe('0.03')
        ->and($cost->source->value)->toBe('configured');
});

it('costs an Anthropic response under the billing basis the caller enforced, not its generic model', function (): void {
    // laravel/ai 1.0 Anthropic response shape: inclusive inputTokens, the
    // cacheWriteInputTokens aggregate, and the provider's raw HTTP response.
    $response = (object) [
        'usage' => (object) [
            'inputTokens' => 4096,
            'outputTokens' => 503,
            'cacheReadInputTokens' => 1800,
            'cacheWriteInputTokens' => 248,
            'reasoningTokens' => null,
        ],
        'meta' => (object) ['provider' => 'anthropic', 'model' => 'claude-haiku-5-5'],
        'raw' => new HttpResponse(new Psr7Response(
            body: '{"usage":{"input_tokens":2048,"cache_read_input_tokens":1800,"cache_creation_input_tokens":248,"cache_creation":{"ephemeral_5m_input_tokens":148,"ephemeral_1h_input_tokens":100},"output_tokens":503}}',
            headers: ['Content-Type' => 'application/json'],
        )),
        'steps' => null,
    ];

    $generic = AiPricing::cost($response);
    $basis = AiPricing::cost([
        'provider' => 'anthropic',
        'model' => 'claude-haiku-5-5-global-short',
        'usage' => $response->usage,
        'raw' => $response->raw,
        'steps' => $response->steps,
    ]);

    // Haiku 5.5 global short, USD per million: 2048 x 0.10 + 1800 x 0.01 +
    // 148 x 0.125 + 100 x 0.20 + 503 x 0.50 = 0.0005128.
    expect($generic->amount)->toBeNull()
        ->and($generic->completeness->value)->toBe('unavailable')
        ->and((string) $basis->amount)->toBe('0.0005128')
        ->and($basis->completeness->value)->toBe('complete')
        ->and($basis->missingUnits)->toBe([]);
});
