<?php

namespace Farzai\LaravelSchema\Commands;

use Illuminate\Console\Command;

class LaravelSchemaCommand extends Command
{
    public $signature = 'laravel-schema';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
