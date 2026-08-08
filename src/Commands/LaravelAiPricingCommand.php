<?php

namespace Jkudish\LaravelAiPricing\Commands;

use Illuminate\Console\Command;

class LaravelAiPricingCommand extends Command
{
    public $signature = 'laravel-ai-pricing';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
