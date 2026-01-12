<?php

declare(strict_types=1);

namespace Farzai\LaravelSchema\Diff;

use Farzai\LaravelSchema\Diff\Concerns\HasDiffStatus;
use Farzai\LaravelSchema\Schema\Enums\DiffStatus;
use Farzai\LaravelSchema\Schema\Table;

/**
 * Immutable value object representing the difference between two tables.
 */
final readonly class TableDiff
{
    use HasDiffStatus;

    /**
     * @param  array<string, ColumnDiff>  $columns
     * @param  array<string, IndexDiff>  $indexes
     * @param  array<string, ForeignKeyDiff>  $foreignKeys
     * @param  array<string, array{expected: mixed, actual: mixed}>  $changes  Table-level changes
     */
    public function __construct(
        public string $name,
        public DiffStatus $status,
        public ?Table $expected = null,
        public ?Table $actual = null,
        public array $columns = [],
        public array $indexes = [],
        public array $foreignKeys = [],
        public array $changes = [],
    ) {}

    /**
     * Check if there are column differences.
     */
    public function hasColumnDifferences(): bool
    {
        foreach ($this->columns as $columnDiff) {
            if ($columnDiff->hasDifference()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if there are index differences.
     */
    public function hasIndexDifferences(): bool
    {
        foreach ($this->indexes as $indexDiff) {
            if ($indexDiff->hasDifference()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if there are foreign key differences.
     */
    public function hasForeignKeyDifferences(): bool
    {
        foreach ($this->foreignKeys as $fkDiff) {
            if ($fkDiff->hasDifference()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get added columns.
     *
     * @return array<string, ColumnDiff>
     */
    public function getAddedColumns(): array
    {
        return array_filter(
            $this->columns,
            fn (ColumnDiff $diff) => $diff->isAdded()
        );
    }

    /**
     * Get removed columns.
     *
     * @return array<string, ColumnDiff>
     */
    public function getRemovedColumns(): array
    {
        return array_filter(
            $this->columns,
            fn (ColumnDiff $diff) => $diff->isRemoved()
        );
    }

    /**
     * Get modified columns.
     *
     * @return array<string, ColumnDiff>
     */
    public function getModifiedColumns(): array
    {
        return array_filter(
            $this->columns,
            fn (ColumnDiff $diff) => $diff->isModified()
        );
    }

    /**
     * Get added indexes.
     *
     * @return array<string, IndexDiff>
     */
    public function getAddedIndexes(): array
    {
        return array_filter(
            $this->indexes,
            fn (IndexDiff $diff) => $diff->isAdded()
        );
    }

    /**
     * Get removed indexes.
     *
     * @return array<string, IndexDiff>
     */
    public function getRemovedIndexes(): array
    {
        return array_filter(
            $this->indexes,
            fn (IndexDiff $diff) => $diff->isRemoved()
        );
    }

    /**
     * Get modified indexes.
     *
     * @return array<string, IndexDiff>
     */
    public function getModifiedIndexes(): array
    {
        return array_filter(
            $this->indexes,
            fn (IndexDiff $diff) => $diff->isModified()
        );
    }

    /**
     * Convert to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'status' => $this->status->value,
            'expected' => $this->expected?->toArray(),
            'actual' => $this->actual?->toArray(),
            'columns' => array_values(array_map(
                fn (ColumnDiff $diff) => $diff->toArray(),
                $this->columns
            )),
            'indexes' => array_values(array_map(
                fn (IndexDiff $diff) => $diff->toArray(),
                $this->indexes
            )),
            'foreignKeys' => array_values(array_map(
                fn (ForeignKeyDiff $diff) => $diff->toArray(),
                $this->foreignKeys
            )),
            'changes' => $this->changes,
        ];
    }
}
