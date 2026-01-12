<?php

declare(strict_types=1);

namespace Farzai\LaravelSchema\Schema;

/**
 * Immutable value object representing a database foreign key.
 */
final readonly class ForeignKey
{
    /**
     * @param  array<int, string>  $columns
     * @param  array<int, string>  $referencedColumns
     */
    public function __construct(
        public string $name,
        public array $columns,
        public string $referencedTable,
        public array $referencedColumns,
        public string $onUpdate = 'NO ACTION',
        public string $onDelete = 'NO ACTION',
    ) {}

    /**
     * Check if this foreign key equals another foreign key.
     */
    public function equals(self $other): bool
    {
        return $this->name === $other->name
            && $this->columns === $other->columns
            && $this->referencedTable === $other->referencedTable
            && $this->referencedColumns === $other->referencedColumns
            && strtoupper($this->onUpdate) === strtoupper($other->onUpdate)
            && strtoupper($this->onDelete) === strtoupper($other->onDelete);
    }

    /**
     * Convert the foreign key to an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'columns' => $this->columns,
            'referencedTable' => $this->referencedTable,
            'referencedColumns' => $this->referencedColumns,
            'onUpdate' => $this->onUpdate,
            'onDelete' => $this->onDelete,
        ];
    }

    /**
     * Create a ForeignKey from an array.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            columns: $data['columns'] ?? [],
            referencedTable: $data['referencedTable'] ?? $data['referenced_table'] ?? '',
            referencedColumns: $data['referencedColumns'] ?? $data['referenced_columns'] ?? [],
            onUpdate: $data['onUpdate'] ?? $data['on_update'] ?? 'NO ACTION',
            onDelete: $data['onDelete'] ?? $data['on_delete'] ?? 'NO ACTION',
        );
    }
}
