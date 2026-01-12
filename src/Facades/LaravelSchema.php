<?php

declare(strict_types=1);

namespace Farzai\LaravelSchema\Facades;

use Farzai\LaravelSchema\SchemaInspector;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \Farzai\LaravelSchema\Schema\DatabaseSchema getDatabaseSchema()
 * @method static \Farzai\LaravelSchema\Schema\DatabaseSchema getMigrationSchema()
 * @method static \Farzai\LaravelSchema\Diff\SchemaDiff compare()
 * @method static array<string, \Farzai\LaravelSchema\Schema\Table> getTables()
 * @method static \Farzai\LaravelSchema\Schema\Table|null getTable(string $tableName)
 * @method static \Farzai\LaravelSchema\Diff\TableDiff|null getTableDiff(string $tableName)
 * @method static void auth(\Closure $callback)
 * @method static bool check(\Illuminate\Http\Request $request)
 *
 * @see \Farzai\LaravelSchema\SchemaInspector
 */
class LaravelSchema extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SchemaInspector::class;
    }
}
