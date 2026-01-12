<?php

declare(strict_types=1);

namespace Farzai\LaravelSchema\Diff;

use Farzai\LaravelSchema\Schema\Enums\DiffStatus;

/**
 * Immutable value object representing the difference between two schemas.
 */
final readonly class SchemaDiff
{
    /**
     * @param  array<string, TableDiff>  $tables
     */
    public function __construct(
        public array $tables = [],
        public bool $hasDifferences = false,
    ) {}

    /**
     * Get a table diff by name.
     */
    public function getTable(string $name): ?TableDiff
    {
        return $this->tables[$name] ?? null;
    }

    /**
     * Check if a table exists in the diff.
     */
    public function hasTable(string $name): bool
    {
        return isset($this->tables[$name]);
    }

    /**
     * Get added tables.
     *
     * @return array<string, TableDiff>
     */
    public function getAddedTables(): array
    {
        return array_filter(
            $this->tables,
            fn (TableDiff $diff) => $diff->isAdded()
        );
    }

    /**
     * Get removed tables.
     *
     * @return array<string, TableDiff>
     */
    public function getRemovedTables(): array
    {
        return array_filter(
            $this->tables,
            fn (TableDiff $diff) => $diff->isRemoved()
        );
    }

    /**
     * Get modified tables.
     *
     * @return array<string, TableDiff>
     */
    public function getModifiedTables(): array
    {
        return array_filter(
            $this->tables,
            fn (TableDiff $diff) => $diff->isModified()
        );
    }

    /**
     * Get unchanged tables.
     *
     * @return array<string, TableDiff>
     */
    public function getUnchangedTables(): array
    {
        return array_filter(
            $this->tables,
            fn (TableDiff $diff) => $diff->isUnchanged()
        );
    }

    /**
     * Get tables with differences.
     *
     * @return array<string, TableDiff>
     */
    public function getTablesWithDifferences(): array
    {
        return array_filter(
            $this->tables,
            fn (TableDiff $diff) => $diff->hasDifference()
        );
    }

    /**
     * Get a summary of differences.
     *
     * @return array<string, int>
     */
    public function getSummary(): array
    {
        $summary = [
            'total_tables' => count($this->tables),
            'added_tables' => 0,
            'removed_tables' => 0,
            'modified_tables' => 0,
            'unchanged_tables' => 0,
            'added_columns' => 0,
            'removed_columns' => 0,
            'modified_columns' => 0,
            'added_indexes' => 0,
            'removed_indexes' => 0,
            'modified_indexes' => 0,
            'added_foreign_keys' => 0,
            'removed_foreign_keys' => 0,
            'modified_foreign_keys' => 0,
        ];

        foreach ($this->tables as $tableDiff) {
            match ($tableDiff->status) {
                DiffStatus::Added => $summary['added_tables']++,
                DiffStatus::Removed => $summary['removed_tables']++,
                DiffStatus::Modified => $summary['modified_tables']++,
                DiffStatus::Unchanged => $summary['unchanged_tables']++,
            };

            foreach ($tableDiff->columns as $columnDiff) {
                match ($columnDiff->status) {
                    DiffStatus::Added => $summary['added_columns']++,
                    DiffStatus::Removed => $summary['removed_columns']++,
                    DiffStatus::Modified => $summary['modified_columns']++,
                    default => null,
                };
            }

            foreach ($tableDiff->indexes as $indexDiff) {
                match ($indexDiff->status) {
                    DiffStatus::Added => $summary['added_indexes']++,
                    DiffStatus::Removed => $summary['removed_indexes']++,
                    DiffStatus::Modified => $summary['modified_indexes']++,
                    default => null,
                };
            }

            foreach ($tableDiff->foreignKeys as $fkDiff) {
                match ($fkDiff->status) {
                    DiffStatus::Added => $summary['added_foreign_keys']++,
                    DiffStatus::Removed => $summary['removed_foreign_keys']++,
                    DiffStatus::Modified => $summary['modified_foreign_keys']++,
                    default => null,
                };
            }
        }

        return $summary;
    }

    /**
     * Convert to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'hasDifferences' => $this->hasDifferences,
            'summary' => $this->getSummary(),
            'tables' => array_values(array_map(
                fn (TableDiff $diff) => $diff->toArray(),
                $this->tables
            )),
        ];
    }
}
