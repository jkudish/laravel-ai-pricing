<?php

declare(strict_types=1);

namespace Jkudish\LaravelAiPricing\ValueObjects;

use InvalidArgumentException;

final readonly class ModelIdentity
{
    public function __construct(public string $provider, public string $model)
    {
        if (trim($provider) === '' || trim($model) === '') {
            throw new InvalidArgumentException('Provider and model must not be empty.');
        }
    }

    public function key(): string
    {
        return strtolower(trim($this->provider)).':'.strtolower(trim($this->model));
    }

    /** @return array{provider: string, model: string} */
    public function toArray(): array
    {
        return ['provider' => $this->provider, 'model' => $this->model];
    }
}
