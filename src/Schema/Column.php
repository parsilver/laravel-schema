<?php

declare(strict_types=1);

namespace Farzai\LaravelSchema\Schema;

use Farzai\LaravelSchema\Schema\Enums\ColumnType;

/**
 * Immutable value object representing a database column.
 */
final readonly class Column
{
    /**
     * @param  array<string>|null  $allowedValues  For enum/set types
     */
    public function __construct(
        public string $name,
        public ColumnType $type,
        public bool $nullable = false,
        public mixed $default = null,
        public bool $autoIncrement = false,
        public bool $unsigned = false,
        public ?int $length = null,
        public ?int $precision = null,
        public ?int $scale = null,
        public ?string $charset = null,
        public ?string $collation = null,
        public ?string $comment = null,
        public ?array $allowedValues = null,
        public ?string $after = null,
        public bool $first = false,
        public ?string $virtualAs = null,
        public ?string $storedAs = null,
        public bool $invisible = false,
    ) {}

    /**
     * Check if this column equals another column.
     */
    public function equals(self $other): bool
    {
        return $this->name === $other->name
            && $this->type === $other->type
            && $this->nullable === $other->nullable
            && $this->default === $other->default
            && $this->autoIncrement === $other->autoIncrement
            && $this->unsigned === $other->unsigned
            && $this->length === $other->length
            && $this->precision === $other->precision
            && $this->scale === $other->scale
            && $this->allowedValues === $other->allowedValues;
    }

    /**
     * Convert the column to an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type->value,
            'nullable' => $this->nullable,
            'default' => $this->default,
            'autoIncrement' => $this->autoIncrement,
            'unsigned' => $this->unsigned,
            'length' => $this->length,
            'precision' => $this->precision,
            'scale' => $this->scale,
            'charset' => $this->charset,
            'collation' => $this->collation,
            'comment' => $this->comment,
            'allowedValues' => $this->allowedValues,
            'after' => $this->after,
            'first' => $this->first,
            'virtualAs' => $this->virtualAs,
            'storedAs' => $this->storedAs,
            'invisible' => $this->invisible,
        ];
    }

    /**
     * Create a Column from an array.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            type: is_string($data['type'] ?? null)
                ? ColumnType::tryFromString($data['type'])
                : ($data['type'] ?? ColumnType::Unknown),
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
            virtualAs: $data['virtualAs'] ?? null,
            storedAs: $data['storedAs'] ?? null,
            invisible: $data['invisible'] ?? false,
        );
    }
}
