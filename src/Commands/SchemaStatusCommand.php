<?php

declare(strict_types=1);

namespace Farzai\LaravelSchema\Commands;

use Farzai\LaravelSchema\SchemaInspector;
use Illuminate\Console\Command;

class SchemaStatusCommand extends Command
{
    public $signature = 'schema:status';

    public $description = 'Show the current schema synchronization status';

    public function handle(SchemaInspector $inspector): int
    {
        $this->line('');

        try {
            $diff = $inspector->compare();
            $summary = $diff->getSummary();

            if (! $diff->hasDifferences) {
                $this->info('Schema Status: IN SYNC ✓');
                $this->line('');
                $this->line("Tables: {$summary['total_tables']} total");
                $this->line('  All tables match their migration definitions.');
            } else {
                $this->warn('Schema Status: OUT OF SYNC ✗');
                $this->line('');
                $this->line("Tables: {$summary['total_tables']} total");
                $this->line("  - <fg=green>{$summary['added_tables']} added</> (in DB, not in migrations)");
                $this->line("  - <fg=red>{$summary['removed_tables']} removed</> (in migrations, not in DB)");
                $this->line("  - <fg=yellow>{$summary['modified_tables']} modified</>");
                $this->line('');
                $this->line("Run '<fg=cyan>php artisan schema:diff</>' for details.");
            }

            $this->line('');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to check schema status: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
