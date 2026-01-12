<?php

declare(strict_types=1);

namespace Farzai\LaravelSchema\Contracts;

use Farzai\LaravelSchema\Diff\SchemaDiff;
use Farzai\LaravelSchema\Diff\TableDiff;
use Farzai\LaravelSchema\Schema\DatabaseSchema;
use Farzai\LaravelSchema\Schema\Table;

/**
 * Interface for schema comparison.
 */
interface SchemaDifferInterface
{
    /**
     * Compare two schemas and return the differences.
     *
     * @param  DatabaseSchema  $expected  The expected schema (from migrations)
     * @param  DatabaseSchema  $actual  The actual schema (from database)
     */
    public function diff(DatabaseSchema $expected, DatabaseSchema $actual): SchemaDiff;

    /**
     * Compare two tables and return the differences.
     */
    public function diffTables(Table $expected, Table $actual): TableDiff;
}
