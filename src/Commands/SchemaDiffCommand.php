<?php

declare(strict_types=1);

namespace Farzai\LaravelSchema\Commands;

use Farzai\LaravelSchema\Diff\ColumnDiff;
use Farzai\LaravelSchema\Diff\ForeignKeyDiff;
use Farzai\LaravelSchema\Diff\IndexDiff;
use Farzai\LaravelSchema\Diff\TableDiff;
use Farzai\LaravelSchema\Schema\Enums\DiffStatus;
use Farzai\LaravelSchema\SchemaInspector;
use Illuminate\Console\Command;

class SchemaDiffCommand extends Command
{
    public $signature = 'schema:diff {table? : Show diff for a specific table}';

    public $description = 'Show detailed schema differences between database and migrations';

    public function handle(SchemaInspector $inspector): int
    {
        $this->line('');

        try {
            $diff = $inspector->compare();
            $tableName = $this->argument('table');

            if ($tableName) {
                return $this->showTableDiff($diff->getTable($tableName), $tableName);
            }

            return $this->showFullDiff($diff);
        } catch (\Exception $e) {
            $this->error('Failed to compare schema: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    private function showFullDiff(\Farzai\LaravelSchema\Diff\SchemaDiff $diff): int
    {
        if (! $diff->hasDifferences) {
            $this->info('Schema is in sync. No differences found.');
            $this->line('');

            return self::SUCCESS;
        }

        $this->warn('Schema Differences:');
        $this->line('');

        // Show added tables (in DB but not in migrations)
        foreach ($diff->getAddedTables() as $tableDiff) {
            $this->line("<fg=green>[ADDED]</> {$tableDiff->name}");
            $this->line('  <fg=gray>Not defined in migrations</>');
            $this->line('');
        }

        // Show removed tables (in migrations but not in DB)
        foreach ($diff->getRemovedTables() as $tableDiff) {
            $this->line("<fg=red>[REMOVED]</> {$tableDiff->name}");
            $this->line('  <fg=gray>Exists in migrations but not in database</>');
            $this->line('');
        }

        // Show modified tables
        foreach ($diff->getModifiedTables() as $tableDiff) {
            $this->line("<fg=yellow>[MODIFIED]</> {$tableDiff->name}");
            $this->showTableDetails($tableDiff);
            $this->line('');
        }

        return self::SUCCESS;
    }

    private function showTableDiff(?TableDiff $tableDiff, string $tableName): int
    {
        if (! $tableDiff) {
            $this->error("Table '{$tableName}' not found in schema diff.");

            return self::FAILURE;
        }

        $statusLabel = match ($tableDiff->status) {
            DiffStatus::Added => '<fg=green>[ADDED]</>',
            DiffStatus::Removed => '<fg=red>[REMOVED]</>',
            DiffStatus::Modified => '<fg=yellow>[MODIFIED]</>',
            DiffStatus::Unchanged => '<fg=gray>[UNCHANGED]</>',
        };

        $this->line("{$statusLabel} {$tableDiff->name}");

        if ($tableDiff->status === DiffStatus::Unchanged) {
            $this->line('  <fg=gray>No differences</>');
        } else {
            $this->showTableDetails($tableDiff);
        }

        $this->line('');

        return self::SUCCESS;
    }

    private function showTableDetails(TableDiff $tableDiff): void
    {
        // Show column changes
        if ($tableDiff->hasColumnDifferences()) {
            $this->line('  <fg=white>Columns:</>');

            foreach ($tableDiff->getAddedColumns() as $columnDiff) {
                $this->showColumnDiff($columnDiff, '+');
            }

            foreach ($tableDiff->getRemovedColumns() as $columnDiff) {
                $this->showColumnDiff($columnDiff, '-');
            }

            foreach ($tableDiff->getModifiedColumns() as $columnDiff) {
                $this->showColumnDiff($columnDiff, '~');
            }
        }

        // Show index changes
        if ($tableDiff->hasIndexDifferences()) {
            $this->line('  <fg=white>Indexes:</>');

            foreach ($tableDiff->getAddedIndexes() as $indexDiff) {
                $this->showIndexDiff($indexDiff, '+');
            }

            foreach ($tableDiff->getRemovedIndexes() as $indexDiff) {
                $this->showIndexDiff($indexDiff, '-');
            }

            foreach ($tableDiff->getModifiedIndexes() as $indexDiff) {
                $this->showIndexDiff($indexDiff, '~');
            }
        }

        // Show foreign key changes
        if ($tableDiff->hasForeignKeyDifferences()) {
            $this->line('  <fg=white>Foreign Keys:</>');

            $addedFks = array_filter($tableDiff->foreignKeys, fn (ForeignKeyDiff $fk) => $fk->isAdded());
            $removedFks = array_filter($tableDiff->foreignKeys, fn (ForeignKeyDiff $fk) => $fk->isRemoved());
            $modifiedFks = array_filter($tableDiff->foreignKeys, fn (ForeignKeyDiff $fk) => $fk->isModified());

            foreach ($addedFks as $fkDiff) {
                $this->showForeignKeyDiff($fkDiff, '+');
            }

            foreach ($removedFks as $fkDiff) {
                $this->showForeignKeyDiff($fkDiff, '-');
            }

            foreach ($modifiedFks as $fkDiff) {
                $this->showForeignKeyDiff($fkDiff, '~');
            }
        }
    }

    private function showColumnDiff(ColumnDiff $columnDiff, string $prefix): void
    {
        $color = match ($prefix) {
            '+' => 'green',
            '-' => 'red',
            '~' => 'yellow',
            default => 'white',
        };

        $column = $columnDiff->actual ?? $columnDiff->expected;
        $type = $column?->type->value ?? 'unknown';
        $nullable = ($column->nullable ?? false) ? '?' : '';

        if ($prefix === '~' && ! empty($columnDiff->changes)) {
            // Show what changed for modified columns
            $changeDetails = [];
            foreach ($columnDiff->changes as $property => $change) {
                $from = $this->formatValue($change['expected']);
                $to = $this->formatValue($change['actual']);
                $changeDetails[] = "{$property}: {$from} → {$to}";
            }
            $this->line("    <fg={$color}>{$prefix}</> {$columnDiff->name}: ".implode(', ', $changeDetails));
        } else {
            $this->line("    <fg={$color}>{$prefix}</> {$columnDiff->name}: {$type}{$nullable}");
        }
    }

    private function showIndexDiff(IndexDiff $indexDiff, string $prefix): void
    {
        $color = match ($prefix) {
            '+' => 'green',
            '-' => 'red',
            '~' => 'yellow',
            default => 'white',
        };

        $this->line("    <fg={$color}>{$prefix}</> {$indexDiff->name}");
    }

    private function showForeignKeyDiff(ForeignKeyDiff $fkDiff, string $prefix): void
    {
        $color = match ($prefix) {
            '+' => 'green',
            '-' => 'red',
            '~' => 'yellow',
            default => 'white',
        };

        $this->line("    <fg={$color}>{$prefix}</> {$fkDiff->name}");
    }

    private function formatValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_null($value)) {
            return 'null';
        }

        if (is_array($value)) {
            return json_encode($value) ?: '[]';
        }

        return (string) $value;
    }
}
