<?php

declare(strict_types=1);

namespace Farzai\LaravelSchema\Contracts;

use Farzai\LaravelSchema\Schema\DatabaseSchema;

/**
 * Interface for parsing Laravel migration files.
 */
interface MigrationParserInterface
{
    /**
     * Parse all migration files and build a schema.
     *
     * @param  string  $migrationsPath  Path to migrations directory
     * @param  array<int, string>  $ignoredTables  Tables to exclude
     */
    public function parse(string $migrationsPath, array $ignoredTables = []): DatabaseSchema;

    /**
     * Get list of migration files in order.
     *
     * @return array<int, string> File paths
     */
    public function getMigrationFiles(string $migrationsPath): array;
}
