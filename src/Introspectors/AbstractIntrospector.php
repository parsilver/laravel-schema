<?php

declare(strict_types=1);

namespace Farzai\LaravelSchema\Introspectors;

use Farzai\LaravelSchema\Contracts\SchemaIntrospectorInterface;
use Farzai\LaravelSchema\Schema\Column;
use Farzai\LaravelSchema\Schema\DatabaseSchema;
use Farzai\LaravelSchema\Schema\Enums\ColumnType;
use Farzai\LaravelSchema\Schema\ForeignKey;
use Farzai\LaravelSchema\Schema\Index;
use Farzai\LaravelSchema\Schema\Table;
use Illuminate\Database\Connection;

/**
 * Abstract base class for database introspectors.
 */
abstract class AbstractIntrospector implements SchemaIntrospectorInterface
{
    public function __construct(
        protected Connection $connection,
    ) {}

    /**
     * Get the complete schema.
     *
     * @param  array<int, string>  $ignoredTables
     */
    public function introspect(array $ignoredTables = []): DatabaseSchema
    {
        return new DatabaseSchema(
            tables: $this->getTables($ignoredTables),
            connection: $this->connection->getName(),
        );
    }

    /**
     * Get all tables from the database.
     *
     * @param  array<int, string>  $ignoredTables
     * @return array<string, Table>
     */
    public function getTables(array $ignoredTables = []): array
    {
        $tableNames = $this->getTableNames();
        $tables = [];

        foreach ($tableNames as $tableName) {
            if (in_array($tableName, $ignoredTables, true)) {
                continue;
            }

            $table = $this->getTable($tableName);
            if ($table !== null) {
                $tables[$tableName] = $table;
            }
        }

        return $tables;
    }

    /**
     * Get a single table by name.
     */
    public function getTable(string $tableName): ?Table
    {
        if (! $this->hasTable($tableName)) {
            return null;
        }

        return new Table(
            name: $tableName,
            columns: $this->getColumns($tableName),
            indexes: $this->getIndexes($tableName),
            foreignKeys: $this->getForeignKeys($tableName),
            engine: $this->getTableEngine($tableName),
            charset: $this->getTableCharset($tableName),
            collation: $this->getTableCollation($tableName),
            comment: $this->getTableComment($tableName),
        );
    }

    /**
     * Get all table names.
     *
     * @return array<int, string>
     */
    abstract protected function getTableNames(): array;

    /**
     * Get the table engine.
     */
    abstract protected function getTableEngine(string $tableName): ?string;

    /**
     * Get the table charset.
     */
    abstract protected function getTableCharset(string $tableName): ?string;

    /**
     * Get the table collation.
     */
    abstract protected function getTableCollation(string $tableName): ?string;

    /**
     * Get the table comment.
     */
    abstract protected function getTableComment(string $tableName): ?string;

    /**
     * Map a database type to a ColumnType enum.
     *
     * @param  array<string, mixed>  $metadata
     */
    abstract protected function mapDatabaseType(string $type, array $metadata): ColumnType;

    /**
     * Create a Column from database metadata.
     *
     * @param  array<string, mixed>  $metadata
     */
    protected function createColumn(string $name, array $metadata): Column
    {
        return new Column(
            name: $name,
            type: $this->mapDatabaseType($metadata['type'] ?? '', $metadata),
            nullable: $metadata['nullable'] ?? false,
            default: $metadata['default'] ?? null,
            autoIncrement: $metadata['autoIncrement'] ?? false,
            unsigned: $metadata['unsigned'] ?? false,
            length: $metadata['length'] ?? null,
            precision: $metadata['precision'] ?? null,
            scale: $metadata['scale'] ?? null,
            charset: $metadata['charset'] ?? null,
            collation: $metadata['collation'] ?? null,
            comment: $metadata['comment'] ?? null,
            allowedValues: $metadata['allowedValues'] ?? null,
        );
    }

    /**
     * Create an Index from database metadata.
     *
     * @param  array<string, mixed>  $metadata
     */
    protected function createIndex(string $name, array $metadata): Index
    {
        return Index::fromArray([
            'name' => $name,
            'type' => $metadata['type'] ?? 'index',
            'columns' => $metadata['columns'] ?? [],
            'algorithm' => $metadata['algorithm'] ?? null,
        ]);
    }

    /**
     * Create a ForeignKey from database metadata.
     *
     * @param  array<string, mixed>  $metadata
     */
    protected function createForeignKey(string $name, array $metadata): ForeignKey
    {
        return new ForeignKey(
            name: $name,
            columns: $metadata['columns'] ?? [],
            referencedTable: $metadata['referencedTable'] ?? '',
            referencedColumns: $metadata['referencedColumns'] ?? [],
            onUpdate: $metadata['onUpdate'] ?? 'NO ACTION',
            onDelete: $metadata['onDelete'] ?? 'NO ACTION',
        );
    }

    /**
     * Parse a database type string to extract base type and length.
     *
     * @return array{type: string, length: int|null, precision: int|null, scale: int|null, unsigned: bool}
     */
    protected function parseTypeString(string $typeString): array
    {
        $unsigned = str_contains(strtolower($typeString), 'unsigned');
        $type = preg_replace('/\s*unsigned\s*/i', '', $typeString) ?? $typeString;

        $length = null;
        $precision = null;
        $scale = null;

        if (preg_match('/^(\w+)\((\d+)(?:,(\d+))?\)/', $type, $matches)) {
            $type = $matches[1];
            $precision = (int) $matches[2];

            if (isset($matches[3])) {
                $scale = (int) $matches[3];
            } else {
                $length = $precision;
                $precision = null;
            }
        }

        return [
            'type' => strtolower($type),
            'length' => $length,
            'precision' => $precision,
            'scale' => $scale,
            'unsigned' => $unsigned,
        ];
    }
}
