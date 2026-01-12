<?php

declare(strict_types=1);

namespace Farzai\LaravelSchema\Commands;

use Farzai\LaravelSchema\SchemaInspector;
use Illuminate\Console\Command;

class SchemaTablesCommand extends Command
{
    public $signature = 'schema:tables {table? : Show details for a specific table}';

    public $description = 'List all database tables with their column, index, and foreign key counts';

    public function handle(SchemaInspector $inspector): int
    {
        $this->line('');

        try {
            $tableName = $this->argument('table');

            if ($tableName) {
                return $this->showTableDetails($inspector, $tableName);
            }

            return $this->showTableList($inspector);
        } catch (\Exception $e) {
            $this->error('Failed to list tables: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    private function showTableList(SchemaInspector $inspector): int
    {
        $tables = $inspector->getTables();
        $count = count($tables);

        if ($count === 0) {
            $this->warn('No tables found in the database.');
            $this->line('');

            return self::SUCCESS;
        }

        $this->info("Database Tables ({$count}):");
        $this->line('');

        $rows = [];
        foreach ($tables as $table) {
            $columnCount = count($table->columns);
            $indexCount = count($table->indexes);
            $fkCount = count($table->foreignKeys);

            $rows[] = [
                $table->name,
                $columnCount.' column'.($columnCount !== 1 ? 's' : ''),
                $indexCount.' index'.($indexCount !== 1 ? 'es' : ''),
                $fkCount.' FK'.($fkCount !== 1 ? 's' : ''),
            ];
        }

        $this->table(
            ['Table', 'Columns', 'Indexes', 'Foreign Keys'],
            $rows
        );

        $this->line('');
        $this->line("Run '<fg=cyan>php artisan schema:tables {table}</>' for table details.");
        $this->line('');

        return self::SUCCESS;
    }

    private function showTableDetails(SchemaInspector $inspector, string $tableName): int
    {
        $table = $inspector->getTable($tableName);

        if (! $table) {
            $this->error("Table '{$tableName}' not found.");

            return self::FAILURE;
        }

        $this->info("Table: {$table->name}");
        $this->line('');

        // Show columns
        $this->line('<fg=white>Columns:</>');
        $columnRows = [];
        foreach ($table->columns as $column) {
            $type = $column->type->value;
            if ($column->length) {
                $type .= "({$column->length})";
            } elseif ($column->precision !== null) {
                $type .= "({$column->precision}";
                if ($column->scale !== null) {
                    $type .= ",{$column->scale}";
                }
                $type .= ')';
            }

            $attributes = [];
            if ($column->unsigned) {
                $attributes[] = 'unsigned';
            }
            if ($column->nullable) {
                $attributes[] = 'nullable';
            }
            if ($column->autoIncrement) {
                $attributes[] = 'auto_increment';
            }
            if ($column->default !== null) {
                $attributes[] = 'default: '.$this->formatValue($column->default);
            }

            $columnRows[] = [
                $column->name,
                $type,
                implode(', ', $attributes) ?: '-',
            ];
        }
        $this->table(['Name', 'Type', 'Attributes'], $columnRows);

        // Show indexes
        if (count($table->indexes) > 0) {
            $this->line('');
            $this->line('<fg=white>Indexes:</>');
            $indexRows = [];
            foreach ($table->indexes as $index) {
                $indexRows[] = [
                    $index->name,
                    $index->type->value,
                    implode(', ', $index->columns),
                ];
            }
            $this->table(['Name', 'Type', 'Columns'], $indexRows);
        }

        // Show foreign keys
        if (count($table->foreignKeys) > 0) {
            $this->line('');
            $this->line('<fg=white>Foreign Keys:</>');
            $fkRows = [];
            foreach ($table->foreignKeys as $fk) {
                $fkRows[] = [
                    $fk->name,
                    implode(', ', $fk->columns),
                    $fk->referencedTable,
                    implode(', ', $fk->referencedColumns),
                    $fk->onUpdate ?? 'RESTRICT',
                    $fk->onDelete ?? 'RESTRICT',
                ];
            }
            $this->table(['Name', 'Columns', 'References', 'On Columns', 'On Update', 'On Delete'], $fkRows);
        }

        $this->line('');

        return self::SUCCESS;
    }

    private function formatValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_null($value)) {
            return 'NULL';
        }

        if (is_string($value)) {
            return "'{$value}'";
        }

        return (string) $value;
    }
}
