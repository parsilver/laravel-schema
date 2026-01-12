<?php

declare(strict_types=1);

namespace Farzai\LaravelSchema\Introspectors;

use Farzai\LaravelSchema\Schema\Column;
use Farzai\LaravelSchema\Schema\Enums\ColumnType;
use Farzai\LaravelSchema\Schema\Enums\IndexType;
use Farzai\LaravelSchema\Schema\ForeignKey;
use Farzai\LaravelSchema\Schema\Index;

/**
 * MySQL/MariaDB schema introspector.
 */
final class MySqlIntrospector extends AbstractIntrospector
{
    /**
     * Get all table names.
     *
     * @return array<int, string>
     */
    protected function getTableNames(): array
    {
        $tables = $this->connection->select('SHOW TABLES');
        $key = 'Tables_in_'.$this->connection->getDatabaseName();

        return array_map(
            fn ($table) => $table->$key ?? array_values((array) $table)[0],
            $tables
        );
    }

    /**
     * Check if a table exists.
     */
    public function hasTable(string $tableName): bool
    {
        return in_array($tableName, $this->getTableNames(), true);
    }

    /**
     * Get all columns for a table.
     *
     * @return array<string, Column>
     */
    public function getColumns(string $tableName): array
    {
        $columns = [];
        $results = $this->connection->select("SHOW FULL COLUMNS FROM `{$tableName}`");

        foreach ($results as $row) {
            $parsed = $this->parseTypeString($row->Type);

            $metadata = [
                'type' => $row->Type,
                'nullable' => $row->Null === 'YES',
                'default' => $row->Default,
                'autoIncrement' => str_contains($row->Extra ?? '', 'auto_increment'),
                'unsigned' => $parsed['unsigned'],
                'length' => $parsed['length'],
                'precision' => $parsed['precision'],
                'scale' => $parsed['scale'],
                'charset' => null,
                'collation' => $row->Collation ?? null,
                'comment' => $row->Comment !== '' ? $row->Comment : null,
                'allowedValues' => $this->extractEnumValues($row->Type),
            ];

            $columns[$row->Field] = $this->createColumn($row->Field, $metadata);
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
        $results = $this->connection->select("SHOW INDEX FROM `{$tableName}`");

        $grouped = [];
        foreach ($results as $row) {
            $indexName = $row->Key_name;
            if (! isset($grouped[$indexName])) {
                $grouped[$indexName] = [
                    'columns' => [],
                    'unique' => ! $row->Non_unique,
                    'type' => $row->Index_type ?? 'BTREE',
                ];
            }
            $grouped[$indexName]['columns'][(int) $row->Seq_in_index] = $row->Column_name;
        }

        foreach ($grouped as $indexName => $data) {
            ksort($data['columns']);

            $type = match (true) {
                $indexName === 'PRIMARY' => IndexType::Primary,
                $data['unique'] => IndexType::Unique,
                strtoupper($data['type']) === 'FULLTEXT' => IndexType::Fulltext,
                strtoupper($data['type']) === 'SPATIAL' => IndexType::Spatial,
                default => IndexType::Index,
            };

            $indexes[$indexName] = new Index(
                name: $indexName,
                type: $type,
                columns: array_values($data['columns']),
                algorithm: $data['type'] !== 'BTREE' ? $data['type'] : null,
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
        $database = $this->connection->getDatabaseName();

        $sql = '
            SELECT
                kcu.CONSTRAINT_NAME,
                kcu.COLUMN_NAME,
                kcu.REFERENCED_TABLE_NAME,
                kcu.REFERENCED_COLUMN_NAME,
                rc.UPDATE_RULE,
                rc.DELETE_RULE
            FROM information_schema.KEY_COLUMN_USAGE kcu
            JOIN information_schema.REFERENTIAL_CONSTRAINTS rc
                ON kcu.CONSTRAINT_NAME = rc.CONSTRAINT_NAME
                AND kcu.TABLE_SCHEMA = rc.CONSTRAINT_SCHEMA
            WHERE kcu.TABLE_SCHEMA = ?
                AND kcu.TABLE_NAME = ?
                AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
            ORDER BY kcu.CONSTRAINT_NAME, kcu.ORDINAL_POSITION
        ';

        $results = $this->connection->select($sql, [$database, $tableName]);

        $grouped = [];
        foreach ($results as $row) {
            $fkName = $row->CONSTRAINT_NAME;
            if (! isset($grouped[$fkName])) {
                $grouped[$fkName] = [
                    'columns' => [],
                    'referencedTable' => $row->REFERENCED_TABLE_NAME,
                    'referencedColumns' => [],
                    'onUpdate' => $row->UPDATE_RULE,
                    'onDelete' => $row->DELETE_RULE,
                ];
            }
            $grouped[$fkName]['columns'][] = $row->COLUMN_NAME;
            $grouped[$fkName]['referencedColumns'][] = $row->REFERENCED_COLUMN_NAME;
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
     * Get the table engine.
     */
    protected function getTableEngine(string $tableName): ?string
    {
        $result = $this->connection->selectOne(
            'SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
            [$this->connection->getDatabaseName(), $tableName]
        );

        return $result->ENGINE ?? null;
    }

    /**
     * Get the table charset.
     */
    protected function getTableCharset(string $tableName): ?string
    {
        $result = $this->connection->selectOne(
            'SELECT CCSA.CHARACTER_SET_NAME
             FROM information_schema.TABLES T
             JOIN information_schema.COLLATION_CHARACTER_SET_APPLICABILITY CCSA
                 ON T.TABLE_COLLATION = CCSA.COLLATION_NAME
             WHERE T.TABLE_SCHEMA = ? AND T.TABLE_NAME = ?',
            [$this->connection->getDatabaseName(), $tableName]
        );

        return $result->CHARACTER_SET_NAME ?? null;
    }

    /**
     * Get the table collation.
     */
    protected function getTableCollation(string $tableName): ?string
    {
        $result = $this->connection->selectOne(
            'SELECT TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
            [$this->connection->getDatabaseName(), $tableName]
        );

        return $result->TABLE_COLLATION ?? null;
    }

    /**
     * Get the table comment.
     */
    protected function getTableComment(string $tableName): ?string
    {
        $result = $this->connection->selectOne(
            'SELECT TABLE_COMMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
            [$this->connection->getDatabaseName(), $tableName]
        );

        $comment = $result->TABLE_COMMENT ?? null;

        return $comment !== '' ? $comment : null;
    }

    /**
     * Map a MySQL type to a ColumnType enum.
     *
     * @param  array<string, mixed>  $metadata
     */
    protected function mapDatabaseType(string $type, array $metadata): ColumnType
    {
        $parsed = $this->parseTypeString($type);
        $baseType = $parsed['type'];
        $unsigned = $parsed['unsigned'] || ($metadata['unsigned'] ?? false);
        $autoIncrement = $metadata['autoIncrement'] ?? false;

        return match ($baseType) {
            'bigint' => match (true) {
                $autoIncrement => ColumnType::BigIncrements,
                $unsigned => ColumnType::UnsignedBigInteger,
                default => ColumnType::BigInteger,
            },
            'int', 'integer' => match (true) {
                $autoIncrement => ColumnType::Increments,
                $unsigned => ColumnType::UnsignedInteger,
                default => ColumnType::Integer,
            },
            'mediumint' => match (true) {
                $autoIncrement => ColumnType::MediumIncrements,
                $unsigned => ColumnType::UnsignedMediumInteger,
                default => ColumnType::MediumInteger,
            },
            'smallint' => match (true) {
                $autoIncrement => ColumnType::SmallIncrements,
                $unsigned => ColumnType::UnsignedSmallInteger,
                default => ColumnType::SmallInteger,
            },
            'tinyint' => match (true) {
                $parsed['length'] === 1 => ColumnType::Boolean,
                $autoIncrement => ColumnType::TinyIncrements,
                $unsigned => ColumnType::UnsignedTinyInteger,
                default => ColumnType::TinyInteger,
            },
            'decimal', 'numeric' => ColumnType::Decimal,
            'double' => ColumnType::Double,
            'float' => ColumnType::Float,
            'char' => match ($parsed['length']) {
                36 => ColumnType::Uuid,
                26 => ColumnType::Ulid,
                default => ColumnType::Char,
            },
            'varchar' => ColumnType::String,
            'text' => ColumnType::Text,
            'mediumtext' => ColumnType::MediumText,
            'longtext' => ColumnType::LongText,
            'tinytext' => ColumnType::TinyText,
            'blob', 'binary', 'varbinary', 'mediumblob', 'longblob', 'tinyblob' => ColumnType::Binary,
            'date' => ColumnType::Date,
            'datetime' => ColumnType::DateTime,
            'timestamp' => ColumnType::Timestamp,
            'time' => ColumnType::Time,
            'year' => ColumnType::Year,
            'json' => ColumnType::Json,
            'enum' => ColumnType::Enum,
            'set' => ColumnType::Set,
            'geometry' => ColumnType::Geometry,
            'point' => ColumnType::Point,
            'linestring' => ColumnType::LineString,
            'polygon' => ColumnType::Polygon,
            'geometrycollection' => ColumnType::GeometryCollection,
            'multipoint' => ColumnType::MultiPoint,
            'multilinestring' => ColumnType::MultiLineString,
            'multipolygon' => ColumnType::MultiPolygon,
            default => ColumnType::Unknown,
        };
    }

    /**
     * Extract enum/set values from type definition.
     *
     * @return array<int, string>|null
     */
    private function extractEnumValues(string $type): ?array
    {
        if (preg_match('/^(enum|set)\((.+)\)$/i', $type, $matches)) {
            $values = str_getcsv($matches[2], ',', "'");

            return array_map('trim', $values);
        }

        return null;
    }
}
