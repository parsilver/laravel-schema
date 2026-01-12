<?php

declare(strict_types=1);

namespace Farzai\LaravelSchema\Contracts;

use Farzai\LaravelSchema\Schema\Column;
use Farzai\LaravelSchema\Schema\DatabaseSchema;
use Farzai\LaravelSchema\Schema\ForeignKey;
use Farzai\LaravelSchema\Schema\Index;
use Farzai\LaravelSchema\Schema\Table;

/**
 * Interface for database schema introspection.
 */
interface SchemaIntrospectorInterface
{
    /**
     * Get the complete schema.
     *
     * @param  array<int, string>  $ignoredTables
     */
    public function introspect(array $ignoredTables = []): DatabaseSchema;

    /**
     * Get all tables from the database.
     *
     * @param  array<int, string>  $ignoredTables  Tables to exclude
     * @return array<string, Table>
     */
    public function getTables(array $ignoredTables = []): array;

    /**
     * Get a single table by name.
     */
    public function getTable(string $tableName): ?Table;

    /**
     * Get all columns for a table.
     *
     * @return array<string, Column>
     */
    public function getColumns(string $tableName): array;

    /**
     * Get all indexes for a table.
     *
     * @return array<string, Index>
     */
    public function getIndexes(string $tableName): array;

    /**
     * Get all foreign keys for a table.
     *
     * @return array<string, ForeignKey>
     */
    public function getForeignKeys(string $tableName): array;

    /**
     * Check if a table exists.
     */
    public function hasTable(string $tableName): bool;
}
