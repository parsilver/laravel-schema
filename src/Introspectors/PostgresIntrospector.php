<?php

declare(strict_types=1);

namespace Farzai\LaravelSchema\Introspectors;

use Farzai\LaravelSchema\Schema\Column;
use Farzai\LaravelSchema\Schema\Enums\ColumnType;
use Farzai\LaravelSchema\Schema\Enums\IndexType;
use Farzai\LaravelSchema\Schema\ForeignKey;
use Farzai\LaravelSchema\Schema\Index;

/**
 * PostgreSQL schema introspector.
 */
final class PostgresIntrospector extends AbstractIntrospector
{
    /**
     * Get all table names.
     *
     * @return array<int, string>
     */
    protected function getTableNames(): array
    {
        $results = $this->connection->select(
            "SELECT tablename FROM pg_tables WHERE schemaname = 'public'"
        );

        return array_map(fn ($row) => $row->tablename, $results);
    }

    /**
     * Check if a table exists.
     */
    public function hasTable(string $tableName): bool
    {
        $result = $this->connection->selectOne(
            "SELECT EXISTS (SELECT 1 FROM pg_tables WHERE schemaname = 'public' AND tablename = ?) AS exists",
            [$tableName]
        );

        return (bool) $result->exists;
    }

    /**
     * Get all columns for a table.
     *
     * @return array<string, Column>
     */
    public function getColumns(string $tableName): array
    {
        $columns = [];

        $sql = "
            SELECT
                c.column_name,
                c.data_type,
                c.udt_name,
                c.is_nullable,
                c.column_default,
                c.character_maximum_length,
                c.numeric_precision,
                c.numeric_scale,
                c.collation_name,
                pgd.description as column_comment,
                CASE WHEN pk.column_name IS NOT NULL THEN true ELSE false END as is_primary
            FROM information_schema.columns c
            LEFT JOIN pg_catalog.pg_description pgd
                ON pgd.objoid = (SELECT oid FROM pg_class WHERE relname = c.table_name)
                AND pgd.objsubid = c.ordinal_position
            LEFT JOIN (
                SELECT ku.column_name
                FROM information_schema.table_constraints tc
                JOIN information_schema.key_column_usage ku
                    ON tc.constraint_name = ku.constraint_name
                WHERE tc.constraint_type = 'PRIMARY KEY'
                    AND tc.table_name = ?
                    AND tc.table_schema = 'public'
            ) pk ON pk.column_name = c.column_name
            WHERE c.table_schema = 'public'
                AND c.table_name = ?
            ORDER BY c.ordinal_position
        ";

        $results = $this->connection->select($sql, [$tableName, $tableName]);

        foreach ($results as $row) {
            $autoIncrement = str_contains($row->column_default ?? '', 'nextval');

            $metadata = [
                'type' => $row->udt_name,
                'dataType' => $row->data_type,
                'nullable' => $row->is_nullable === 'YES',
                'default' => $this->parseDefault($row->column_default),
                'autoIncrement' => $autoIncrement,
                'unsigned' => false,
                'length' => $row->character_maximum_length,
                'precision' => $row->numeric_precision,
                'scale' => $row->numeric_scale,
                'collation' => $row->collation_name,
                'comment' => $row->column_comment,
            ];

            $columns[$row->column_name] = $this->createColumn($row->column_name, $metadata);
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

        $sql = "
            SELECT
                i.relname as index_name,
                a.attname as column_name,
                ix.indisunique as is_unique,
                ix.indisprimary as is_primary,
                am.amname as index_type
            FROM pg_index ix
            JOIN pg_class t ON t.oid = ix.indrelid
            JOIN pg_class i ON i.oid = ix.indexrelid
            JOIN pg_attribute a ON a.attrelid = t.oid AND a.attnum = ANY(ix.indkey)
            JOIN pg_am am ON am.oid = i.relam
            JOIN pg_namespace n ON n.oid = t.relnamespace
            WHERE t.relname = ?
                AND n.nspname = 'public'
            ORDER BY i.relname, a.attnum
        ";

        $results = $this->connection->select($sql, [$tableName]);

        $grouped = [];
        foreach ($results as $row) {
            $indexName = $row->index_name;
            if (! isset($grouped[$indexName])) {
                $grouped[$indexName] = [
                    'columns' => [],
                    'isUnique' => $row->is_unique,
                    'isPrimary' => $row->is_primary,
                    'type' => $row->index_type,
                ];
            }
            $grouped[$indexName]['columns'][] = $row->column_name;
        }

        foreach ($grouped as $indexName => $data) {
            $type = match (true) {
                $data['isPrimary'] => IndexType::Primary,
                $data['isUnique'] => IndexType::Unique,
                strtoupper($data['type']) === 'GIN' || strtoupper($data['type']) === 'GIST' => IndexType::Fulltext,
                default => IndexType::Index,
            };

            $indexes[$indexName] = new Index(
                name: $indexName,
                type: $type,
                columns: $data['columns'],
                algorithm: $data['type'] !== 'btree' ? $data['type'] : null,
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

        $sql = "
            SELECT
                tc.constraint_name,
                kcu.column_name,
                ccu.table_name AS foreign_table_name,
                ccu.column_name AS foreign_column_name,
                rc.update_rule,
                rc.delete_rule
            FROM information_schema.table_constraints AS tc
            JOIN information_schema.key_column_usage AS kcu
                ON tc.constraint_name = kcu.constraint_name
                AND tc.table_schema = kcu.table_schema
            JOIN information_schema.constraint_column_usage AS ccu
                ON ccu.constraint_name = tc.constraint_name
                AND ccu.table_schema = tc.table_schema
            JOIN information_schema.referential_constraints AS rc
                ON rc.constraint_name = tc.constraint_name
                AND rc.constraint_schema = tc.table_schema
            WHERE tc.constraint_type = 'FOREIGN KEY'
                AND tc.table_name = ?
                AND tc.table_schema = 'public'
            ORDER BY tc.constraint_name, kcu.ordinal_position
        ";

        $results = $this->connection->select($sql, [$tableName]);

        $grouped = [];
        foreach ($results as $row) {
            $fkName = $row->constraint_name;
            if (! isset($grouped[$fkName])) {
                $grouped[$fkName] = [
                    'columns' => [],
                    'referencedTable' => $row->foreign_table_name,
                    'referencedColumns' => [],
                    'onUpdate' => $row->update_rule,
                    'onDelete' => $row->delete_rule,
                ];
            }
            if (! in_array($row->column_name, $grouped[$fkName]['columns'], true)) {
                $grouped[$fkName]['columns'][] = $row->column_name;
            }
            if (! in_array($row->foreign_column_name, $grouped[$fkName]['referencedColumns'], true)) {
                $grouped[$fkName]['referencedColumns'][] = $row->foreign_column_name;
            }
        }

        foreach ($grouped as $fkName => $data) {
            $foreignKeys[$fkName] = new ForeignKey(
                name: $fkName,
                columns: $data['columns'],
                referencedTable: $data['referencedTable'],
                referencedColumns: $data['referencedColumns'],
                onUpdate: $data['onUpdate'],
                onDelete: $data['onDelete'],
            );
        }

        return $foreignKeys;
    }

    /**
     * Get the table engine (not applicable for PostgreSQL).
     */
    protected function getTableEngine(string $tableName): ?string
    {
        return null;
    }

    /**
     * Get the table charset (not applicable for PostgreSQL).
     */
    protected function getTableCharset(string $tableName): ?string
    {
        return null;
    }

    /**
     * Get the table collation.
     */
    protected function getTableCollation(string $tableName): ?string
    {
        return null;
    }

    /**
     * Get the table comment.
     */
    protected function getTableComment(string $tableName): ?string
    {
        $result = $this->connection->selectOne(
            "SELECT obj_description(oid) as comment FROM pg_class WHERE relname = ? AND relnamespace = 'public'::regnamespace",
            [$tableName]
        );

        return $result->comment ?? null;
    }

    /**
     * Map a PostgreSQL type to a ColumnType enum.
     *
     * @param  array<string, mixed>  $metadata
     */
    protected function mapDatabaseType(string $type, array $metadata): ColumnType
    {
        $autoIncrement = $metadata['autoIncrement'] ?? false;

        return match (strtolower($type)) {
            'int8', 'bigint', 'bigserial' => $autoIncrement ? ColumnType::BigIncrements : ColumnType::BigInteger,
            'int4', 'integer', 'serial' => $autoIncrement ? ColumnType::Increments : ColumnType::Integer,
            'int2', 'smallint', 'smallserial' => $autoIncrement ? ColumnType::SmallIncrements : ColumnType::SmallInteger,
            'numeric', 'decimal' => ColumnType::Decimal,
            'float8', 'double precision' => ColumnType::Double,
            'float4', 'real' => ColumnType::Float,
            'bpchar', 'char', 'character' => ColumnType::Char,
            'varchar', 'character varying' => ColumnType::String,
            'text' => ColumnType::Text,
            'bytea' => ColumnType::Binary,
            'bool', 'boolean' => ColumnType::Boolean,
            'date' => ColumnType::Date,
            'timestamp', 'timestamp without time zone' => ColumnType::DateTime,
            'timestamptz', 'timestamp with time zone' => ColumnType::DateTimeTz,
            'time', 'time without time zone' => ColumnType::Time,
            'timetz', 'time with time zone' => ColumnType::TimeTz,
            'json' => ColumnType::Json,
            'jsonb' => ColumnType::Jsonb,
            'uuid' => ColumnType::Uuid,
            'inet' => ColumnType::IpAddress,
            'macaddr', 'macaddr8' => ColumnType::MacAddress,
            'geometry' => ColumnType::Geometry,
            'geography' => ColumnType::Geography,
            'point' => ColumnType::Point,
            default => ColumnType::Unknown,
        };
    }

    /**
     * Parse a PostgreSQL default value.
     */
    private function parseDefault(?string $default): mixed
    {
        if ($default === null) {
            return null;
        }

        // Remove type casts
        $default = preg_replace('/::[\w\s]+$/', '', $default);

        // Handle nextval (auto-increment)
        if (str_contains($default, 'nextval')) {
            return null;
        }

        // Handle string literals
        if (preg_match("/^'(.*)'$/", $default ?? '', $matches)) {
            return $matches[1];
        }

        // Handle NULL
        if (strtoupper($default ?? '') === 'NULL') {
            return null;
        }

        // Handle booleans
        if (strtolower($default ?? '') === 'true') {
            return true;
        }
        if (strtolower($default ?? '') === 'false') {
            return false;
        }

        // Handle numeric values
        if (is_numeric($default)) {
            return str_contains($default, '.') ? (float) $default : (int) $default;
        }

        return $default;
    }
}
