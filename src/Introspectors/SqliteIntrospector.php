<?php

declare(strict_types=1);

namespace Farzai\LaravelSchema\Introspectors;

use Farzai\LaravelSchema\Schema\Column;
use Farzai\LaravelSchema\Schema\Enums\ColumnType;
use Farzai\LaravelSchema\Schema\Enums\IndexType;
use Farzai\LaravelSchema\Schema\ForeignKey;
use Farzai\LaravelSchema\Schema\Index;

/**
 * SQLite schema introspector.
 */
final class SqliteIntrospector extends AbstractIntrospector
{
    /**
     * Get all table names.
     *
     * @return array<int, string>
     */
    protected function getTableNames(): array
    {
        $results = $this->connection->select(
            "SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'"
        );

        return array_map(fn ($row) => $row->name, $results);
    }

    /**
     * Check if a table exists.
     */
    public function hasTable(string $tableName): bool
    {
        $result = $this->connection->selectOne(
            "SELECT name FROM sqlite_master WHERE type='table' AND name = ?",
            [$tableName]
        );

        return $result !== null;
    }

    /**
     * Get all columns for a table.
     *
     * @return array<string, Column>
     */
    public function getColumns(string $tableName): array
    {
        $columns = [];
        $results = $this->connection->select("PRAGMA table_info(`{$tableName}`)");

        foreach ($results as $row) {
            $parsed = $this->parseTypeString($row->type);

            $metadata = [
                'type' => $row->type,
                'nullable' => ! $row->notnull,
                'default' => $this->parseDefault($row->dflt_value),
                'autoIncrement' => (bool) $row->pk && strtoupper($parsed['type']) === 'INTEGER',
                'unsigned' => $parsed['unsigned'],
                'length' => $parsed['length'],
                'precision' => $parsed['precision'],
                'scale' => $parsed['scale'],
            ];

            $columns[$row->name] = $this->createColumn($row->name, $metadata);
        }

        return $columns;
    }

    /**
     * Get all indexes for a table.
     *
     * @return array<string, Index>
     */
    public function getIndexes(string $tableName): array
    {
        $indexes = [];

        // Get regular indexes
        $indexList = $this->connection->select("PRAGMA index_list(`{$tableName}`)");

        foreach ($indexList as $indexRow) {
            $indexInfo = $this->connection->select("PRAGMA index_info(`{$indexRow->name}`)");

            $columns = array_map(fn ($col) => $col->name, $indexInfo);

            $type = match (true) {
                $indexRow->origin === 'pk' => IndexType::Primary,
                (bool) $indexRow->unique => IndexType::Unique,
                default => IndexType::Index,
            };

            $indexes[$indexRow->name] = new Index(
                name: $indexRow->name,
                type: $type,
                columns: $columns,
            );
        }

        // Check for primary key that might not be in index_list
        $tableInfo = $this->connection->select("PRAGMA table_info(`{$tableName}`)");
        $pkColumns = [];
        foreach ($tableInfo as $col) {
            if ($col->pk > 0) {
                $pkColumns[$col->pk] = $col->name;
            }
        }

        if (! empty($pkColumns) && ! isset($indexes['PRIMARY'])) {
            ksort($pkColumns);
            $indexes['PRIMARY'] = new Index(
                name: 'PRIMARY',
                type: IndexType::Primary,
                columns: array_values($pkColumns),
            );
        }

        return $indexes;
    }

    /**
     * Get all foreign keys for a table.
     *
     * @return array<string, ForeignKey>
     */
    public function getForeignKeys(string $tableName): array
    {
        $foreignKeys = [];
        $results = $this->connection->select("PRAGMA foreign_key_list(`{$tableName}`)");

        $grouped = [];
        foreach ($results as $row) {
            $fkName = "fk_{$tableName}_{$row->table}_{$row->id}";
            if (! isset($grouped[$fkName])) {
                $grouped[$fkName] = [
                    'columns' => [],
                    'referencedTable' => $row->table,
                    'referencedColumns' => [],
                    'onUpdate' => $row->on_update,
                    'onDelete' => $row->on_delete,
                ];
            }
            $grouped[$fkName]['columns'][$row->seq] = $row->from;
            $grouped[$fkName]['referencedColumns'][$row->seq] = $row->to;
        }

        foreach ($grouped as $fkName => $data) {
            ksort($data['columns']);
            ksort($data['referencedColumns']);

            $foreignKeys[$fkName] = new ForeignKey(
                name: $fkName,
                columns: array_values($data['columns']),
                referencedTable: $data['referencedTable'],
                referencedColumns: array_values($data['referencedColumns']),
                onUpdate: $data['onUpdate'],
                onDelete: $data['onDelete'],
            );
        }

        return $foreignKeys;
    }

    /**
     * Get the table engine (not applicable for SQLite).
     */
    protected function getTableEngine(string $tableName): ?string
    {
        return null;
    }

    /**
     * Get the table charset (not applicable for SQLite).
     */
    protected function getTableCharset(string $tableName): ?string
    {
        return null;
    }

    /**
     * Get the table collation (not applicable for SQLite).
     */
    protected function getTableCollation(string $tableName): ?string
    {
        return null;
    }

    /**
     * Get the table comment (not applicable for SQLite).
     */
    protected function getTableComment(string $tableName): ?string
    {
        return null;
    }

    /**
     * Map a SQLite type to a ColumnType enum.
     *
     * @param  array<string, mixed>  $metadata
     */
    protected function mapDatabaseType(string $type, array $metadata): ColumnType
    {
        $parsed = $this->parseTypeString($type);
        $baseType = strtoupper($parsed['type']);
        $autoIncrement = $metadata['autoIncrement'] ?? false;

        // SQLite type affinity
        return match (true) {
            $baseType === 'INTEGER' && $autoIncrement => ColumnType::Increments,
            str_contains($baseType, 'INT') => ColumnType::Integer,
            str_contains($baseType, 'CHAR') || str_contains($baseType, 'CLOB') || $baseType === 'TEXT' => match ($parsed['length']) {
                36 => ColumnType::Uuid,
                26 => ColumnType::Ulid,
                default => $baseType === 'TEXT' ? ColumnType::Text : ColumnType::String,
            },
            str_contains($baseType, 'BLOB') || $baseType === '' => ColumnType::Binary,
            str_contains($baseType, 'REAL') || str_contains($baseType, 'FLOA') || str_contains($baseType, 'DOUB') => ColumnType::Float,
            str_contains($baseType, 'BOOL') => ColumnType::Boolean,
            str_contains($baseType, 'DATE') => ColumnType::Date,
            str_contains($baseType, 'TIME') => ColumnType::DateTime,
            str_contains($baseType, 'DECIMAL') || str_contains($baseType, 'NUMERIC') => ColumnType::Decimal,
            $baseType === 'JSON' => ColumnType::Json,
            default => ColumnType::Unknown,
        };
    }

    /**
     * Parse a SQLite default value.
     */
    private function parseDefault(?string $default): mixed
    {
        if ($default === null) {
            return null;
        }

        // Handle NULL
        if (strtoupper($default) === 'NULL') {
            return null;
        }

        // Handle string literals
        if (preg_match("/^'(.*)'$/", $default, $matches)) {
            return $matches[1];
        }

        // Handle numeric values
        if (is_numeric($default)) {
            return str_contains($default, '.') ? (float) $default : (int) $default;
        }

        return $default;
    }
}
