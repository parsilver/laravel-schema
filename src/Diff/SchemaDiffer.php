<?php

declare(strict_types=1);

namespace Farzai\LaravelSchema\Diff;

use Farzai\LaravelSchema\Contracts\SchemaDifferInterface;
use Farzai\LaravelSchema\Schema\Column;
use Farzai\LaravelSchema\Schema\DatabaseSchema;
use Farzai\LaravelSchema\Schema\Enums\DiffStatus;
use Farzai\LaravelSchema\Schema\ForeignKey;
use Farzai\LaravelSchema\Schema\Index;
use Farzai\LaravelSchema\Schema\Table;

/**
 * Schema differ implementation for comparing database schemas.
 */
final class SchemaDiffer implements SchemaDifferInterface
{
    /**
     * Compare two schemas and return the differences.
     */
    public function diff(DatabaseSchema $expected, DatabaseSchema $actual): SchemaDiff
    {
        $tableDiffs = [];
        $hasDifferences = false;

        // Find tables in expected but not in actual (REMOVED from DB perspective)
        // These are tables defined in migrations but missing from database
        foreach ($expected->tables as $tableName => $expectedTable) {
            if (! isset($actual->tables[$tableName])) {
                $tableDiffs[$tableName] = new TableDiff(
                    name: $tableName,
                    status: DiffStatus::Removed,
                    expected: $expectedTable,
                    actual: null,
                    columns: $this->createRemovedColumnDiffs($expectedTable->columns),
                    indexes: $this->createRemovedIndexDiffs($expectedTable->indexes),
                    foreignKeys: $this->createRemovedForeignKeyDiffs($expectedTable->foreignKeys),
                );
                $hasDifferences = true;

                continue;
            }

            $tableDiff = $this->diffTables($expectedTable, $actual->tables[$tableName]);
            $tableDiffs[$tableName] = $tableDiff;

            if ($tableDiff->status !== DiffStatus::Unchanged) {
                $hasDifferences = true;
            }
        }

        // Find tables in actual but not in expected (ADDED from DB perspective)
        // These are tables in database but not in migrations
        foreach ($actual->tables as $tableName => $actualTable) {
            if (! isset($expected->tables[$tableName])) {
                $tableDiffs[$tableName] = new TableDiff(
                    name: $tableName,
                    status: DiffStatus::Added,
                    expected: null,
                    actual: $actualTable,
                    columns: $this->createAddedColumnDiffs($actualTable->columns),
                    indexes: $this->createAddedIndexDiffs($actualTable->indexes),
                    foreignKeys: $this->createAddedForeignKeyDiffs($actualTable->foreignKeys),
                );
                $hasDifferences = true;
            }
        }

        // Sort tables alphabetically for consistent output
        ksort($tableDiffs);

        return new SchemaDiff(
            tables: $tableDiffs,
            hasDifferences: $hasDifferences,
        );
    }

    /**
     * Compare two tables and return the differences.
     */
    public function diffTables(Table $expected, Table $actual): TableDiff
    {
        $columnDiffs = $this->diffColumns($expected->columns, $actual->columns);
        $indexDiffs = $this->diffIndexes($expected->indexes, $actual->indexes);
        $foreignKeyDiffs = $this->diffForeignKeys($expected->foreignKeys, $actual->foreignKeys);

        $tableChanges = $this->compareTableProperties($expected, $actual);

        $hasChanges = $this->hasAnyDifferences($columnDiffs)
            || $this->hasAnyDifferences($indexDiffs)
            || $this->hasAnyDifferences($foreignKeyDiffs)
            || ! empty($tableChanges);

        return new TableDiff(
            name: $expected->name,
            status: $hasChanges ? DiffStatus::Modified : DiffStatus::Unchanged,
            expected: $expected,
            actual: $actual,
            columns: $columnDiffs,
            indexes: $indexDiffs,
            foreignKeys: $foreignKeyDiffs,
            changes: $tableChanges,
        );
    }

    /**
     * Compare columns between two tables.
     *
     * @param  array<string, Column>  $expected
     * @param  array<string, Column>  $actual
     * @return array<string, ColumnDiff>
     */
    private function diffColumns(array $expected, array $actual): array
    {
        $diffs = [];

        // Columns in expected but not in actual (removed)
        foreach ($expected as $name => $expectedColumn) {
            if (! isset($actual[$name])) {
                $diffs[$name] = new ColumnDiff(
                    name: $name,
                    status: DiffStatus::Removed,
                    expected: $expectedColumn,
                    actual: null,
                );

                continue;
            }

            $changes = $this->compareColumns($expectedColumn, $actual[$name]);
            $diffs[$name] = new ColumnDiff(
                name: $name,
                status: empty($changes) ? DiffStatus::Unchanged : DiffStatus::Modified,
                expected: $expectedColumn,
                actual: $actual[$name],
                changes: $changes,
            );
        }

        // Columns in actual but not in expected (added)
        foreach ($actual as $name => $actualColumn) {
            if (! isset($expected[$name])) {
                $diffs[$name] = new ColumnDiff(
                    name: $name,
                    status: DiffStatus::Added,
                    expected: null,
                    actual: $actualColumn,
                );
            }
        }

        return $diffs;
    }

    /**
     * Compare two columns and return list of differences.
     *
     * @return array<string, array{expected: mixed, actual: mixed}>
     */
    private function compareColumns(Column $expected, Column $actual): array
    {
        $changes = [];

        if ($expected->type !== $actual->type) {
            $changes['type'] = ['expected' => $expected->type->value, 'actual' => $actual->type->value];
        }

        if ($expected->nullable !== $actual->nullable) {
            $changes['nullable'] = ['expected' => $expected->nullable, 'actual' => $actual->nullable];
        }

        if ($expected->default !== $actual->default) {
            $changes['default'] = ['expected' => $expected->default, 'actual' => $actual->default];
        }

        if ($expected->length !== $actual->length) {
            $changes['length'] = ['expected' => $expected->length, 'actual' => $actual->length];
        }

        if ($expected->unsigned !== $actual->unsigned) {
            $changes['unsigned'] = ['expected' => $expected->unsigned, 'actual' => $actual->unsigned];
        }

        if ($expected->autoIncrement !== $actual->autoIncrement) {
            $changes['autoIncrement'] = ['expected' => $expected->autoIncrement, 'actual' => $actual->autoIncrement];
        }

        if ($expected->precision !== $actual->precision) {
            $changes['precision'] = ['expected' => $expected->precision, 'actual' => $actual->precision];
        }

        if ($expected->scale !== $actual->scale) {
            $changes['scale'] = ['expected' => $expected->scale, 'actual' => $actual->scale];
        }

        if ($expected->allowedValues !== $actual->allowedValues) {
            $changes['allowedValues'] = ['expected' => $expected->allowedValues, 'actual' => $actual->allowedValues];
        }

        return $changes;
    }

    /**
     * Compare indexes between two tables.
     *
     * @param  array<string, Index>  $expected
     * @param  array<string, Index>  $actual
     * @return array<string, IndexDiff>
     */
    private function diffIndexes(array $expected, array $actual): array
    {
        $diffs = [];

        foreach ($expected as $name => $expectedIndex) {
            if (! isset($actual[$name])) {
                $diffs[$name] = new IndexDiff(
                    name: $name,
                    status: DiffStatus::Removed,
                    expected: $expectedIndex,
                    actual: null,
                );

                continue;
            }

            $changes = $this->compareIndexes($expectedIndex, $actual[$name]);
            $diffs[$name] = new IndexDiff(
                name: $name,
                status: empty($changes) ? DiffStatus::Unchanged : DiffStatus::Modified,
                expected: $expectedIndex,
                actual: $actual[$name],
                changes: $changes,
            );
        }

        foreach ($actual as $name => $actualIndex) {
            if (! isset($expected[$name])) {
                $diffs[$name] = new IndexDiff(
                    name: $name,
                    status: DiffStatus::Added,
                    expected: null,
                    actual: $actualIndex,
                );
            }
        }

        return $diffs;
    }

    /**
     * Compare two indexes.
     *
     * @return array<string, array{expected: mixed, actual: mixed}>
     */
    private function compareIndexes(Index $expected, Index $actual): array
    {
        $changes = [];

        if ($expected->type !== $actual->type) {
            $changes['type'] = ['expected' => $expected->type->value, 'actual' => $actual->type->value];
        }

        if ($expected->columns !== $actual->columns) {
            $changes['columns'] = ['expected' => $expected->columns, 'actual' => $actual->columns];
        }

        if ($expected->algorithm !== $actual->algorithm) {
            $changes['algorithm'] = ['expected' => $expected->algorithm, 'actual' => $actual->algorithm];
        }

        return $changes;
    }

    /**
     * Compare foreign keys between two tables.
     *
     * @param  array<string, ForeignKey>  $expected
     * @param  array<string, ForeignKey>  $actual
     * @return array<string, ForeignKeyDiff>
     */
    private function diffForeignKeys(array $expected, array $actual): array
    {
        $diffs = [];

        foreach ($expected as $name => $expectedFk) {
            if (! isset($actual[$name])) {
                $diffs[$name] = new ForeignKeyDiff(
                    name: $name,
                    status: DiffStatus::Removed,
                    expected: $expectedFk,
                    actual: null,
                );

                continue;
            }

            $changes = $this->compareForeignKeys($expectedFk, $actual[$name]);
            $diffs[$name] = new ForeignKeyDiff(
                name: $name,
                status: empty($changes) ? DiffStatus::Unchanged : DiffStatus::Modified,
                expected: $expectedFk,
                actual: $actual[$name],
                changes: $changes,
            );
        }

        foreach ($actual as $name => $actualFk) {
            if (! isset($expected[$name])) {
                $diffs[$name] = new ForeignKeyDiff(
                    name: $name,
                    status: DiffStatus::Added,
                    expected: null,
                    actual: $actualFk,
                );
            }
        }

        return $diffs;
    }

    /**
     * Compare two foreign keys.
     *
     * @return array<string, array{expected: mixed, actual: mixed}>
     */
    private function compareForeignKeys(ForeignKey $expected, ForeignKey $actual): array
    {
        $changes = [];

        if ($expected->columns !== $actual->columns) {
            $changes['columns'] = ['expected' => $expected->columns, 'actual' => $actual->columns];
        }

        if ($expected->referencedTable !== $actual->referencedTable) {
            $changes['referencedTable'] = ['expected' => $expected->referencedTable, 'actual' => $actual->referencedTable];
        }

        if ($expected->referencedColumns !== $actual->referencedColumns) {
            $changes['referencedColumns'] = ['expected' => $expected->referencedColumns, 'actual' => $actual->referencedColumns];
        }

        if (strtoupper($expected->onUpdate) !== strtoupper($actual->onUpdate)) {
            $changes['onUpdate'] = ['expected' => $expected->onUpdate, 'actual' => $actual->onUpdate];
        }

        if (strtoupper($expected->onDelete) !== strtoupper($actual->onDelete)) {
            $changes['onDelete'] = ['expected' => $expected->onDelete, 'actual' => $actual->onDelete];
        }

        return $changes;
    }

    /**
     * Compare table-level properties.
     *
     * @return array<string, array{expected: mixed, actual: mixed}>
     */
    private function compareTableProperties(Table $expected, Table $actual): array
    {
        $changes = [];

        if ($expected->engine !== null && $actual->engine !== null && $expected->engine !== $actual->engine) {
            $changes['engine'] = ['expected' => $expected->engine, 'actual' => $actual->engine];
        }

        if ($expected->charset !== null && $actual->charset !== null && $expected->charset !== $actual->charset) {
            $changes['charset'] = ['expected' => $expected->charset, 'actual' => $actual->charset];
        }

        if ($expected->collation !== null && $actual->collation !== null && $expected->collation !== $actual->collation) {
            $changes['collation'] = ['expected' => $expected->collation, 'actual' => $actual->collation];
        }

        return $changes;
    }

    /**
     * Check if any diffs have differences.
     *
     * @param  array<string, ColumnDiff|IndexDiff|ForeignKeyDiff>  $diffs
     */
    private function hasAnyDifferences(array $diffs): bool
    {
        foreach ($diffs as $diff) {
            if ($diff->hasDifference()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create removed column diffs.
     *
     * @param  array<string, Column>  $columns
     * @return array<string, ColumnDiff>
     */
    private function createRemovedColumnDiffs(array $columns): array
    {
        $diffs = [];
        foreach ($columns as $name => $column) {
            $diffs[$name] = new ColumnDiff(
                name: $name,
                status: DiffStatus::Removed,
                expected: $column,
                actual: null,
            );
        }

        return $diffs;
    }

    /**
     * Create added column diffs.
     *
     * @param  array<string, Column>  $columns
     * @return array<string, ColumnDiff>
     */
    private function createAddedColumnDiffs(array $columns): array
    {
        $diffs = [];
        foreach ($columns as $name => $column) {
            $diffs[$name] = new ColumnDiff(
                name: $name,
                status: DiffStatus::Added,
                expected: null,
                actual: $column,
            );
        }

        return $diffs;
    }

    /**
     * Create removed index diffs.
     *
     * @param  array<string, Index>  $indexes
     * @return array<string, IndexDiff>
     */
    private function createRemovedIndexDiffs(array $indexes): array
    {
        $diffs = [];
        foreach ($indexes as $name => $index) {
            $diffs[$name] = new IndexDiff(
                name: $name,
                status: DiffStatus::Removed,
                expected: $index,
                actual: null,
            );
        }

        return $diffs;
    }

    /**
     * Create added index diffs.
     *
     * @param  array<string, Index>  $indexes
     * @return array<string, IndexDiff>
     */
    private function createAddedIndexDiffs(array $indexes): array
    {
        $diffs = [];
        foreach ($indexes as $name => $index) {
            $diffs[$name] = new IndexDiff(
                name: $name,
                status: DiffStatus::Added,
                expected: null,
                actual: $index,
            );
        }

        return $diffs;
    }

    /**
     * Create removed foreign key diffs.
     *
     * @param  array<string, ForeignKey>  $foreignKeys
     * @return array<string, ForeignKeyDiff>
     */
    private function createRemovedForeignKeyDiffs(array $foreignKeys): array
    {
        $diffs = [];
        foreach ($foreignKeys as $name => $foreignKey) {
            $diffs[$name] = new ForeignKeyDiff(
                name: $name,
                status: DiffStatus::Removed,
                expected: $foreignKey,
                actual: null,
            );
        }

        return $diffs;
    }

    /**
     * Create added foreign key diffs.
     *
     * @param  array<string, ForeignKey>  $foreignKeys
     * @return array<string, ForeignKeyDiff>
     */
    private function createAddedForeignKeyDiffs(array $foreignKeys): array
    {
        $diffs = [];
        foreach ($foreignKeys as $name => $foreignKey) {
            $diffs[$name] = new ForeignKeyDiff(
                name: $name,
                status: DiffStatus::Added,
                expected: null,
                actual: $foreignKey,
            );
        }

        return $diffs;
    }
}
