<?php

declare(strict_types=1);

namespace Farzai\LaravelSchema\Schema;

/**
 * Immutable value object representing a complete database schema.
 */
final readonly class DatabaseSchema
{
    /**
     * @param  array<string, Table>  $tables
     */
    public function __construct(
        public array $tables = [],
        public ?string $connection = null,
    ) {}

    /**
     * Get a table by name.
     */
    public function getTable(string $name): ?Table
    {
        return $this->tables[$name] ?? null;
    }

    /**
     * Check if a table exists.
     */
    public function hasTable(string $name): bool
    {
        return isset($this->tables[$name]);
    }

    /**
     * Get all table names.
     *
     * @return array<int, string>
     */
    public function getTableNames(): array
    {
        return array_keys($this->tables);
    }

    /**
     * Get the number of tables.
     */
    public function count(): int
    {
        return count($this->tables);
    }

    /**
     * Check if the schema is empty.
     */
    public function isEmpty(): bool
    {
        return count($this->tables) === 0;
    }

    /**
     * Convert the schema to an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'tables' => array_map(
                fn (Table $table) => $table->toArray(),
                $this->tables
            ),
            'connection' => $this->connection,
        ];
    }

    /**
     * Create a Schema from an array.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $tables = [];
        foreach ($data['tables'] ?? [] as $name => $tableData) {
            if (is_array($tableData)) {
                $tables[$name] = Table::fromArray($tableData);
            } elseif ($tableData instanceof Table) {
                $tables[$name] = $tableData;
            }
        }

        return new self(
            tables: $tables,
            connection: $data['connection'] ?? null,
        );
    }
}
