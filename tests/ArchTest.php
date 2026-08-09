<?php

declare(strict_types=1);

arch('it will not use debugging functions')
    ->expect(['dd', 'dump', 'ray'])
    ->each->not->toBeUsed();

arch('value objects are final and readonly')
    ->expect('Jkudish\\LaravelAiPricing\\ValueObjects')
    ->toBeFinal()
    ->toBeReadonly();

arch('the package has no persistence or UI concerns')
    ->expect('Jkudish\\LaravelAiPricing')
    ->not->toUse(['Illuminate\\Database', 'Illuminate\\View']);
