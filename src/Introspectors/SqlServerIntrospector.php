<?php

declare(strict_types=1);

namespace Farzai\LaravelSchema\Introspectors;

use Farzai\LaravelSchema\Schema\Column;
use Farzai\LaravelSchema\Schema\Enums\ColumnType;
use Farzai\LaravelSchema\Schema\Enums\IndexType;
use Farzai\LaravelSchema\Schema\ForeignKey;
use Farzai\LaravelSchema\Schema\Index;

/**
 * SQL Server schema introspector.
 */
final class SqlServerIntrospector extends AbstractIntrospector
{
    /**
     * Get all table names.
     *
     * @return array<int, string>
     */
    protected function getTableNames(): array
    {
        $results = $this->connection->select(
            "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE' AND TABLE_SCHEMA = 'dbo'"
        );

        return array_map(fn ($row) => $row->TABLE_NAME, $results);
    }

    /**
     * Check if a table exists.
     */
    public function hasTable(string $tableName): bool
    {
        $result = $this->connection->selectOne(
            "SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = ? AND TABLE_SCHEMA = 'dbo'",
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

        $sql = "
            SELECT
                c.COLUMN_NAME,
                c.DATA_TYPE,
                c.IS_NULLABLE,
                c.COLUMN_DEFAULT,
                c.CHARACTER_MAXIMUM_LENGTH,
                c.NUMERIC_PRECISION,
                c.NUMERIC_SCALE,
                c.COLLATION_NAME,
                COLUMNPROPERTY(OBJECT_ID(c.TABLE_SCHEMA + '.' + c.TABLE_NAME), c.COLUMN_NAME, 'IsIdentity') AS IS_IDENTITY,
                ep.value AS COLUMN_COMMENT
            FROM INFORMATION_SCHEMA.COLUMNS c
            LEFT JOIN sys.extended_properties ep
                ON ep.major_id = OBJECT_ID(c.TABLE_SCHEMA + '.' + c.TABLE_NAME)
                AND ep.minor_id = COLUMNPROPERTY(OBJECT_ID(c.TABLE_SCHEMA + '.' + c.TABLE_NAME), c.COLUMN_NAME, 'ColumnId')
                AND ep.name = 'MS_Description'
            WHERE c.TABLE_NAME = ?
                AND c.TABLE_SCHEMA = 'dbo'
            ORDER BY c.ORDINAL_POSITION
        ";

        $results = $this->connection->select($sql, [$tableName]);

        foreach ($results as $row) {
            $metadata = [
                'type' => $row->DATA_TYPE,
                'nullable' => $row->IS_NULLABLE === 'YES',
                'default' => $this->parseDefault($row->COLUMN_DEFAULT),
                'autoIncrement' => (bool) $row->IS_IDENTITY,
                'unsigned' => false,
                'length' => $row->CHARACTER_MAXIMUM_LENGTH !== -1 ? $row->CHARACTER_MAXIMUM_LENGTH : null,
                'precision' => $row->NUMERIC_PRECISION,
                'scale' => $row->NUMERIC_SCALE,
                'collation' => $row->COLLATION_NAME,
                'comment' => $row->COLUMN_COMMENT,
            ];

            $columns[$row->COLUMN_NAME] = $this->createColumn($row->COLUMN_NAME, $metadata);
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

        $sql = '
            SELECT
                i.name AS index_name,
                c.name AS column_name,
                i.is_unique,
                i.is_primary_key,
                i.type_desc AS index_type
            FROM sys.indexes i
            JOIN sys.index_columns ic ON i.object_id = ic.object_id AND i.index_id = ic.index_id
            JOIN sys.columns c ON ic.object_id = c.object_id AND ic.column_id = c.column_id
            WHERE i.object_id = OBJECT_ID(?)
                AND i.name IS NOT NULL
            ORDER BY i.name, ic.key_ordinal
        ';

        $results = $this->connection->select($sql, [$tableName]);

        $grouped = [];
        foreach ($results as $row) {
            $indexName = $row->index_name;
            if (! isset($grouped[$indexName])) {
                $grouped[$indexName] = [
                    'columns' => [],
                    'isUnique' => $row->is_unique,
                    'isPrimary' => $row->is_primary_key,
                    'type' => $row->index_type,
                ];
            }
            $grouped[$indexName]['columns'][] = $row->column_name;
        }

        foreach ($grouped as $indexName => $data) {
            $type = match (true) {
                (bool) $data['isPrimary'] => IndexType::Primary,
                (bool) $data['isUnique'] => IndexType::Unique,
                default => IndexType::Index,
            };

            $indexes[$indexName] = new Index(
                name: $indexName,
                type: $type,
                columns: $data['columns'],
                algorithm: $data['type'] !== 'NONCLUSTERED' ? $data['type'] : null,
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

        $sql = '
            SELECT
                fk.name AS constraint_name,
                COL_NAME(fkc.parent_object_id, fkc.parent_column_id) AS column_name,
                OBJECT_NAME(fkc.referenced_object_id) AS referenced_table,
                COL_NAME(fkc.referenced_object_id, fkc.referenced_column_id) AS referenced_column,
                fk.update_referential_action_desc AS on_update,
                fk.delete_referential_action_desc AS on_delete
            FROM sys.foreign_keys fk
            JOIN sys.foreign_key_columns fkc ON fk.object_id = fkc.constraint_object_id
            WHERE fk.parent_object_id = OBJECT_ID(?)
            ORDER BY fk.name, fkc.constraint_column_id
        ';

        $results = $this->connection->select($sql, [$tableName]);

        $grouped = [];
        foreach ($results as $row) {
            $fkName = $row->constraint_name;
            if (! isset($grouped[$fkName])) {
                $grouped[$fkName] = [
                    'columns' => [],
                    'referencedTable' => $row->referenced_table,
                    'referencedColumns' => [],
                    'onUpdate' => $this->normalizeReferentialAction($row->on_update),
                    'onDelete' => $this->normalizeReferentialAction($row->on_delete),
                ];
            }
            $grouped[$fkName]['columns'][] = $row->column_name;
            $grouped[$fkName]['referencedColumns'][] = $row->referenced_column;
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
     * Get the table engine (not applicable for SQL Server).
     */
    protected function getTableEngine(string $tableName): ?string
    {
        return null;
    }

    /**
     * Get the table charset (not applicable for SQL Server).
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
            "SELECT ep.value AS comment
             FROM sys.extended_properties ep
             WHERE ep.major_id = OBJECT_ID(?)
                 AND ep.minor_id = 0
                 AND ep.name = 'MS_Description'",
            [$tableName]
        );

        return $result->comment ?? null;
    }

    /**
     * Map a SQL Server type to a ColumnType enum.
     *
     * @param  array<string, mixed>  $metadata
     */
    protected function mapDatabaseType(string $type, array $metadata): ColumnType
    {
        $autoIncrement = $metadata['autoIncrement'] ?? false;

        return match (strtolower($type)) {
            'bigint' => $autoIncrement ? ColumnType::BigIncrements : ColumnType::BigInteger,
            'int' => $autoIncrement ? ColumnType::Increments : ColumnType::Integer,
            'smallint' => $autoIncrement ? ColumnType::SmallIncrements : ColumnType::SmallInteger,
            'tinyint' => $autoIncrement ? ColumnType::TinyIncrements : ColumnType::TinyInteger,
            'bit' => ColumnType::Boolean,
            'decimal', 'numeric' => ColumnType::Decimal,
            'float' => ColumnType::Double,
            'real' => ColumnType::Float,
            'char', 'nchar' => ColumnType::Char,
            'varchar', 'nvarchar' => ColumnType::String,
            'text', 'ntext' => ColumnType::Text,
            'binary', 'varbinary', 'image' => ColumnType::Binary,
            'date' => ColumnType::Date,
            'datetime', 'datetime2', 'smalldatetime' => ColumnType::DateTime,
            'datetimeoffset' => ColumnType::DateTimeTz,
            'time' => ColumnType::Time,
            'uniqueidentifier' => ColumnType::Uuid,
            'xml' => ColumnType::Text,
            'geography' => ColumnType::Geography,
            'geometry' => ColumnType::Geometry,
            default => ColumnType::Unknown,
        };
    }

    /**
     * Parse a SQL Server default value.
     */
    private function parseDefault(?string $default): mixed
    {
        if ($default === null) {
            return null;
        }

        // Remove parentheses wrapping
        $default = trim($default, '()');

        // Handle NULL
        if (strtoupper($default) === 'NULL') {
            return null;
        }

        // Handle string literals
        if (preg_match("/^N?'(.*)'$/", $default, $matches)) {
            return $matches[1];
        }

        // Handle numeric values
        if (is_numeric($default)) {
            return str_contains($default, '.') ? (float) $default : (int) $default;
        }

        return $default;
    }

    /**
     * Normalize referential action from SQL Server format.
     */
    private function normalizeReferentialAction(string $action): string
    {
        return match (strtoupper($action)) {
            'NO_ACTION' => 'NO ACTION',
            'CASCADE' => 'CASCADE',
            'SET_NULL' => 'SET NULL',
            'SET_DEFAULT' => 'SET DEFAULT',
            default => $action,
        };
    }
}
