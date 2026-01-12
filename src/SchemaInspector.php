<?php

declare(strict_types=1);

namespace Farzai\LaravelSchema;

use Closure;
use Farzai\LaravelSchema\Contracts\IntrospectorFactoryInterface;
use Farzai\LaravelSchema\Contracts\MigrationParserInterface;
use Farzai\LaravelSchema\Contracts\SchemaDifferInterface;
use Farzai\LaravelSchema\Diff\SchemaDiff;
use Farzai\LaravelSchema\Schema\DatabaseSchema;
use Illuminate\Http\Request;

/**
 * Main service class for schema inspection and comparison.
 */
class SchemaInspector
{
    public function __construct(
        private IntrospectorFactoryInterface $introspectorFactory,
        private MigrationParserInterface $migrationParser,
        private SchemaDifferInterface $schemaDiffer,
    ) {}

    /**
     * Get configuration at runtime to allow for config overrides.
     *
     * @return array<string, mixed>
     */
    private function getConfig(): array
    {
        return config('schema', []);
    }

    /**
     * Get schema from current database.
     */
    public function getDatabaseSchema(): DatabaseSchema
    {
        $introspector = $this->introspectorFactory->create();
        $config = $this->getConfig();
        $ignoredTables = $config['ignored_tables'] ?? [];

        return $introspector->introspect($ignoredTables);
    }

    /**
     * Get schema from migration files.
     */
    public function getMigrationSchema(): DatabaseSchema
    {
        $config = $this->getConfig();
        $migrationsPath = $config['migrations_path'] ?? 'database/migrations';

        // Support both absolute and relative paths
        $path = str_starts_with($migrationsPath, '/')
            ? $migrationsPath
            : base_path($migrationsPath);

        $ignoredTables = $config['ignored_tables'] ?? [];

        return $this->migrationParser->parse($path, $ignoredTables);
    }

    /**
     * Compare migration schema with database schema.
     */
    public function compare(): SchemaDiff
    {
        $expected = $this->getMigrationSchema();
        $actual = $this->getDatabaseSchema();

        return $this->schemaDiffer->diff($expected, $actual);
    }

    /**
     * Get the list of tables from the database.
     *
     * @return array<string, \Farzai\LaravelSchema\Schema\Table>
     */
    public function getTables(): array
    {
        return $this->getDatabaseSchema()->tables;
    }

    /**
     * Get a single table by name.
     */
    public function getTable(string $tableName): ?\Farzai\LaravelSchema\Schema\Table
    {
        return $this->getDatabaseSchema()->getTable($tableName);
    }

    /**
     * Get the diff for a specific table.
     */
    public function getTableDiff(string $tableName): ?\Farzai\LaravelSchema\Diff\TableDiff
    {
        return $this->compare()->getTable($tableName);
    }

    /**
     * Get the migration parser.
     */
    public function getMigrationParser(): MigrationParserInterface
    {
        return $this->migrationParser;
    }

    /**
     * Get the schema differ.
     */
    public function getSchemaDiffer(): SchemaDifferInterface
    {
        return $this->schemaDiffer;
    }

    /**
     * Get the introspector factory.
     */
    public function getIntrospectorFactory(): IntrospectorFactoryInterface
    {
        return $this->introspectorFactory;
    }

    /**
     * Set the authorization callback.
     *
     * @deprecated Use AuthorizationManager::gate() instead
     */
    public static function auth(Closure $callback): void
    {
        AuthorizationManager::gate($callback);
    }

    /**
     * Check authorization.
     *
     * @deprecated Use AuthorizationManager::check() instead
     */
    public static function check(Request $request): bool
    {
        return AuthorizationManager::check($request);
    }

    /**
     * Get the authorization callback.
     *
     * @deprecated Use AuthorizationManager::getCallback() instead
     */
    public static function getAuthCallback(): ?Closure
    {
        return AuthorizationManager::getCallback();
    }

    /**
     * Clear the authorization callback.
     *
     * @deprecated Use AuthorizationManager::clear() instead
     */
    public static function clearAuth(): void
    {
        AuthorizationManager::clear();
    }
}
