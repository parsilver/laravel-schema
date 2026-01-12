<?php

declare(strict_types=1);

namespace Farzai\LaravelSchema\Schema;

use Farzai\LaravelSchema\Schema\Enums\IndexType;

/**
 * Immutable value object representing a database index.
 */
final readonly class Index
{
    /**
     * @param  array<int, string>  $columns
     */
    public function __construct(
        public string $name,
        public IndexType $type,
        public array $columns,
        public ?string $algorithm = null,
        public ?string $language = null,
    ) {}

    /**
     * Check if this index equals another index.
     */
    public function equals(self $other): bool
    {
        return $this->name === $other->name
            && $this->type === $other->type
            && $this->columns === $other->columns
            && $this->algorithm === $other->algorithm;
    }

    /**
     * Convert the index to an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type->value,
            'columns' => $this->columns,
            'algorithm' => $this->algorithm,
            'language' => $this->language,
        ];
    }

    /**
     * Create an Index from an array.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $type = $data['type'] ?? 'index';

        return new self(
            name: $data['name'],
            type: is_string($type) ? IndexType::tryFromString($type) : $type,
            columns: $data['columns'] ?? [],
            algorithm: $data['algorithm'] ?? null,
            language: $data['language'] ?? null,
        );
    }
}
