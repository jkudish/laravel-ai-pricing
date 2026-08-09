<?php

declare(strict_types=1);

return [
    'currency' => 'USD',
    'offline' => false,
    'cache_store' => null,
    'cache_ttl' => 86_400,
    'prices' => [],
    'openrouter' => [
        'endpoint' => 'https://openrouter.ai/api/v1/models',
    ],
    'portkey' => [
        'endpoint' => 'https://configs.portkey.ai/pricing/{provider}.json',
        'providers' => [],
    ],
];
