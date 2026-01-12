<?php

declare(strict_types=1);

namespace Farzai\LaravelSchema\Schema;

/**
 * Immutable value object representing a database table.
 */
final readonly class Table
{
    /**
     * @param  array<string, Column>  $columns
     * @param  array<string, Index>  $indexes
     * @param  array<string, ForeignKey>  $foreignKeys
     */
    public function __construct(
        public string $name,
        public array $columns = [],
        public array $indexes = [],
        public array $foreignKeys = [],
        public ?string $engine = null,
        public ?string $charset = null,
        public ?string $collation = null,
        public ?string $comment = null,
    ) {}

    /**
     * Get a column by name.
     */
    public function getColumn(string $name): ?Column
    {
        return $this->columns[$name] ?? null;
    }

    /**
     * Check if a column exists.
     */
    public function hasColumn(string $name): bool
    {
        return isset($this->columns[$name]);
    }

    /**
     * Get an index by name.
     */
    public function getIndex(string $name): ?Index
    {
        return $this->indexes[$name] ?? null;
    }

    /**
     * Check if an index exists.
     */
    public function hasIndex(string $name): bool
    {
        return isset($this->indexes[$name]);
    }

    /**
     * Get a foreign key by name.
     */
    public function getForeignKey(string $name): ?ForeignKey
    {
        return $this->foreignKeys[$name] ?? null;
    }

    /**
     * Check if a foreign key exists.
     */
    public function hasForeignKey(string $name): bool
    {
        return isset($this->foreignKeys[$name]);
    }

    /**
     * Get all column names.
     *
     * @return array<int, string>
     */
    public function getColumnNames(): array
    {
        return array_keys($this->columns);
    }

    /**
     * Get all index names.
     *
     * @return array<int, string>
     */
    public function getIndexNames(): array
    {
        return array_keys($this->indexes);
    }

    /**
     * Get all foreign key names.
     *
     * @return array<int, string>
     */
    public function getForeignKeyNames(): array
    {
        return array_keys($this->foreignKeys);
    }

    /**
     * Convert the table to an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'columns' => array_values(array_map(
                fn (Column $column) => $column->toArray(),
                $this->columns
            )),
            'indexes' => array_values(array_map(
                fn (Index $index) => $index->toArray(),
                $this->indexes
            )),
            'foreignKeys' => array_values(array_map(
                fn (ForeignKey $fk) => $fk->toArray(),
                $this->foreignKeys
            )),
            'engine' => $this->engine,
            'charset' => $this->charset,
            'collation' => $this->collation,
            'comment' => $this->comment,
        ];
    }

    /**
     * Create a Table from an array.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $columns = [];
        foreach ($data['columns'] ?? [] as $name => $columnData) {
            if (is_array($columnData)) {
                $columns[$name] = Column::fromArray($columnData);
            } elseif ($columnData instanceof Column) {
                $columns[$name] = $columnData;
            }
        }

        $indexes = [];
        foreach ($data['indexes'] ?? [] as $name => $indexData) {
            if (is_array($indexData)) {
                $indexes[$name] = Index::fromArray($indexData);
            } elseif ($indexData instanceof Index) {
                $indexes[$name] = $indexData;
            }
        }

        $foreignKeys = [];
        foreach ($data['foreignKeys'] ?? [] as $name => $fkData) {
            if (is_array($fkData)) {
                $foreignKeys[$name] = ForeignKey::fromArray($fkData);
            } elseif ($fkData instanceof ForeignKey) {
                $foreignKeys[$name] = $fkData;
            }
        }

        return new self(
            name: $data['name'],
            columns: $columns,
            indexes: $indexes,
            foreignKeys: $foreignKeys,
            engine: $data['engine'] ?? null,
            charset: $data['charset'] ?? null,
            collation: $data['collation'] ?? null,
            comment: $data['comment'] ?? null,
        );
    }
}
