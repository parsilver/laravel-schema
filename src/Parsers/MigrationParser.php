<?php

declare(strict_types=1);

namespace Farzai\LaravelSchema\Parsers;

use Farzai\LaravelSchema\Contracts\MigrationParserInterface;
use Farzai\LaravelSchema\Schema\Column;
use Farzai\LaravelSchema\Schema\DatabaseSchema;
use Farzai\LaravelSchema\Schema\Enums\ColumnType;
use Farzai\LaravelSchema\Schema\Enums\IndexType;
use Farzai\LaravelSchema\Schema\ForeignKey;
use Farzai\LaravelSchema\Schema\Index;
use Farzai\LaravelSchema\Schema\Table;

/**
 * Parser for Laravel migration files.
 */
final class MigrationParser implements MigrationParserInterface
{
    public function __construct(
        private BlueprintAnalyzer $analyzer,
    ) {}

    /**
     * Parse all migration files and build a schema.
     *
     * @param  array<int, string>  $ignoredTables
     */
    public function parse(string $migrationsPath, array $ignoredTables = []): DatabaseSchema
    {
        $files = $this->getMigrationFiles($migrationsPath);

        /** @var array<string, Table> $tables */
        $tables = [];

        foreach ($files as $file) {
            $operations = $this->analyzer->analyzeFile($file);
            $tables = $this->applyOperations($tables, $operations);
        }

        // Filter ignored tables
        $tables = array_filter(
            $tables,
            fn (Table $table) => ! in_array($table->name, $ignoredTables, true),
        );

        return new DatabaseSchema(tables: $tables);
    }

    /**
     * Get list of migration files in order.
     *
     * @return array<int, string>
     */
    public function getMigrationFiles(string $migrationsPath): array
    {
        if (! is_dir($migrationsPath)) {
            return [];
        }

        $files = glob($migrationsPath.'/*.php');

        if ($files === false) {
            return [];
        }

        // Sort by filename (which includes timestamp)
        sort($files);

        return $files;
    }

    /**
     * Apply operations to the tables array.
     *
     * @param  array<string, Table>  $tables
     * @param  array<int, array<string, mixed>>  $operations
     * @return array<string, Table>
     */
    private function applyOperations(array $tables, array $operations): array
    {
        foreach ($operations as $operation) {
            $tables = match ($operation['type']) {
                'create' => $this->applyCreate($tables, $operation),
                'table' => $this->applyAlter($tables, $operation),
                'drop' => $this->applyDrop($tables, $operation),
                'dropIfExists' => $this->applyDropIfExists($tables, $operation),
                'rename' => $this->applyRename($tables, $operation),
                default => $tables,
            };
        }

        return $tables;
    }

    /**
     * Apply a create table operation.
     *
     * @param  array<string, Table>  $tables
     * @param  array<string, mixed>  $operation
     * @return array<string, Table>
     */
    private function applyCreate(array $tables, array $operation): array
    {
        $tableName = $operation['table'];

        $columns = $this->buildColumns($operation['columns'] ?? []);
        $indexes = $this->buildIndexes($operation['indexes'] ?? []);
        $foreignKeys = $this->buildForeignKeys($operation['foreignKeys'] ?? []);

        $tables[$tableName] = new Table(
            name: $tableName,
            columns: $columns,
            indexes: $indexes,
            foreignKeys: $foreignKeys,
        );

        return $tables;
    }

    /**
     * Apply an alter table operation.
     *
     * @param  array<string, Table>  $tables
     * @param  array<string, mixed>  $operation
     * @return array<string, Table>
     */
    private function applyAlter(array $tables, array $operation): array
    {
        $tableName = $operation['table'];

        if (! isset($tables[$tableName])) {
            // Table doesn't exist, create it
            return $this->applyCreate($tables, $operation);
        }

        $existingTable = $tables[$tableName];

        // Merge columns
        $columns = $existingTable->columns;
        foreach ($this->buildColumns($operation['columns'] ?? []) as $name => $column) {
            $columns[$name] = $column;
        }

        // Apply drops
        if (isset($operation['drops']['columns'])) {
            foreach ($operation['drops']['columns'] as $columnName) {
                unset($columns[$columnName]);
            }
        }

        // Merge indexes
        $indexes = $existingTable->indexes;
        foreach ($this->buildIndexes($operation['indexes'] ?? []) as $name => $index) {
            $indexes[$name] = $index;
        }

        // Merge foreign keys
        $foreignKeys = $existingTable->foreignKeys;
        foreach ($this->buildForeignKeys($operation['foreignKeys'] ?? []) as $name => $fk) {
            $foreignKeys[$name] = $fk;
        }

        $tables[$tableName] = new Table(
            name: $tableName,
            columns: $columns,
            indexes: $indexes,
            foreignKeys: $foreignKeys,
            engine: $existingTable->engine,
            charset: $existingTable->charset,
            collation: $existingTable->collation,
            comment: $existingTable->comment,
        );

        return $tables;
    }

    /**
     * Apply a drop table operation.
     *
     * @param  array<string, Table>  $tables
     * @param  array<string, mixed>  $operation
     * @return array<string, Table>
     */
    private function applyDrop(array $tables, array $operation): array
    {
        $tableName = $operation['table'];
        unset($tables[$tableName]);

        return $tables;
    }

    /**
     * Apply a drop if exists operation.
     *
     * @param  array<string, Table>  $tables
     * @param  array<string, mixed>  $operation
     * @return array<string, Table>
     */
    private function applyDropIfExists(array $tables, array $operation): array
    {
        return $this->applyDrop($tables, $operation);
    }

    /**
     * Apply a rename table operation.
     *
     * @param  array<string, Table>  $tables
     * @param  array<string, mixed>  $operation
     * @return array<string, Table>
     */
    private function applyRename(array $tables, array $operation): array
    {
        $oldName = $operation['table'];
        $newName = $operation['newName'] ?? null;

        if ($newName === null || ! isset($tables[$oldName])) {
            return $tables;
        }

        $oldTable = $tables[$oldName];
        unset($tables[$oldName]);

        $tables[$newName] = new Table(
            name: $newName,
            columns: $oldTable->columns,
            indexes: $oldTable->indexes,
            foreignKeys: $oldTable->foreignKeys,
            engine: $oldTable->engine,
            charset: $oldTable->charset,
            collation: $oldTable->collation,
            comment: $oldTable->comment,
        );

        return $tables;
    }

    /**
     * Build Column objects from operation data.
     *
     * @param  array<string, array<string, mixed>>  $columnsData
     * @return array<string, Column>
     */
    private function buildColumns(array $columnsData): array
    {
        $columns = [];

        foreach ($columnsData as $name => $data) {
            $columns[$name] = new Column(
                name: $data['name'] ?? $name,
                type: $this->mapColumnType($data['type'] ?? 'string'),
                nullable: $data['nullable'] ?? false,
                default: $data['default'] ?? null,
                autoIncrement: $data['autoIncrement'] ?? false,
                unsigned: $data['unsigned'] ?? false,
                length: $data['length'] ?? null,
                precision: $data['precision'] ?? null,
                scale: $data['scale'] ?? null,
                charset: $data['charset'] ?? null,
                collation: $data['collation'] ?? null,
                comment: $data['comment'] ?? null,
                allowedValues: $data['allowedValues'] ?? null,
                after: $data['after'] ?? null,
                first: $data['first'] ?? false,
            );
        }

        return $columns;
    }

    /**
     * Build Index objects from operation data.
     *
     * @param  array<string, array<string, mixed>>  $indexesData
     * @return array<string, Index>
     */
    private function buildIndexes(array $indexesData): array
    {
        $indexes = [];

        foreach ($indexesData as $name => $data) {
            $type = match ($data['type'] ?? 'index') {
                'primary' => IndexType::Primary,
                'unique' => IndexType::Unique,
                'fulltext' => IndexType::Fulltext,
                'spatialIndex', 'spatial' => IndexType::Spatial,
                default => IndexType::Index,
            };

            $indexes[$name] = new Index(
                name: $data['name'] ?? $name,
                type: $type,
                columns: $data['columns'] ?? [],
                algorithm: $data['algorithm'] ?? null,
            );
        }

        return $indexes;
    }

    /**
     * Build ForeignKey objects from operation data.
     *
     * @param  array<string, array<string, mixed>>  $foreignKeysData
     * @return array<string, ForeignKey>
     */
    private function buildForeignKeys(array $foreignKeysData): array
    {
        $foreignKeys = [];

        foreach ($foreignKeysData as $name => $data) {
            $foreignKeys[$name] = new ForeignKey(
                name: $data['name'] ?? $name,
                columns: $data['columns'] ?? [],
                referencedTable: $data['referencedTable'] ?? '',
                referencedColumns: $data['referencedColumns'] ?? [],
                onUpdate: $data['onUpdate'] ?? 'NO ACTION',
                onDelete: $data['onDelete'] ?? 'NO ACTION',
            );
        }

        return $foreignKeys;
    }

    /**
     * Map a Laravel column type string to ColumnType enum.
     */
    private function mapColumnType(string $type): ColumnType
    {
        return ColumnType::tryFrom($type) ?? ColumnType::Unknown;
    }
}
