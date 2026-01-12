<?php

declare(strict_types=1);

namespace Farzai\LaravelSchema\Http\Controllers;

use Farzai\LaravelSchema\SchemaInspector;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

final class SchemaApiController extends Controller
{
    public function __construct(
        private readonly SchemaInspector $inspector,
    ) {}

    /**
     * GET /api/tables - List all tables from database.
     */
    public function tables(): JsonResponse
    {
        $schema = $this->inspector->getDatabaseSchema();

        $tables = array_map(
            fn ($table) => $table->toArray(),
            $schema->tables
        );

        return $this->jsonResponse([
            'tables' => array_values($tables),
            'count' => count($tables),
        ]);
    }

    /**
     * GET /api/tables/{table} - Get specific table details.
     */
    public function table(string $table): JsonResponse
    {
        $tableObj = $this->inspector->getTable($table);

        if ($tableObj === null) {
            return $this->jsonResponse(
                ['error' => "Table '{$table}' not found."],
                404
            );
        }

        return $this->jsonResponse([
            'table' => $tableObj->toArray(),
        ]);
    }

    /**
     * GET /api/diff - Get full schema diff.
     */
    public function diff(): JsonResponse
    {
        $diff = $this->inspector->compare();

        return $this->jsonResponse([
            'diff' => $diff->toArray(),
            'summary' => $diff->getSummary(),
            'hasDifferences' => $diff->hasDifferences,
        ]);
    }

    /**
     * GET /api/diff/{table} - Get table-specific diff.
     */
    public function tableDiff(string $table): JsonResponse
    {
        $tableDiff = $this->inspector->getTableDiff($table);

        if ($tableDiff === null) {
            return $this->jsonResponse(
                ['error' => "Table '{$table}' not found in diff."],
                404
            );
        }

        return $this->jsonResponse([
            'table' => $table,
            'diff' => $tableDiff->toArray(),
            'status' => $tableDiff->status->value,
        ]);
    }

    /**
     * GET /api/migrations - List migration files.
     */
    public function migrations(): JsonResponse
    {
        $parser = $this->inspector->getMigrationParser();
        $path = base_path(config('schema.migrations_path', 'database/migrations'));
        $files = $parser->getMigrationFiles($path);

        $migrations = array_map(
            fn (string $file) => [
                'file' => basename($file),
                'path' => $file,
            ],
            $files
        );

        return $this->jsonResponse([
            'migrations' => array_values($migrations),
            'count' => count($migrations),
            'path' => $path,
        ]);
    }

    /**
     * GET /api/status - Get schema sync status.
     */
    public function status(): JsonResponse
    {
        $diff = $this->inspector->compare();
        $summary = $diff->getSummary();

        return $this->jsonResponse([
            'synced' => ! $diff->hasDifferences,
            'summary' => $summary,
            'addedTables' => count($diff->getAddedTables()),
            'removedTables' => count($diff->getRemovedTables()),
            'modifiedTables' => count($diff->getModifiedTables()),
        ]);
    }

    /**
     * POST /api/refresh - Force schema refresh.
     */
    public function refresh(): JsonResponse
    {
        // Currently just re-runs the comparison
        // Future: could add caching and this would clear the cache
        $diff = $this->inspector->compare();

        return $this->jsonResponse([
            'refreshed' => true,
            'hasDifferences' => $diff->hasDifferences,
            'summary' => $diff->getSummary(),
        ]);
    }

    /**
     * Create a consistent JSON response.
     *
     * @param  array<string, mixed>  $data
     */
    private function jsonResponse(array $data, int $status = 200): JsonResponse
    {
        return response()->json([
            'data' => $data,
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ], $status);
    }
}
