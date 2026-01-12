<?php

declare(strict_types=1);

namespace Farzai\LaravelSchema\Diff;

use Farzai\LaravelSchema\Diff\Concerns\HasDiffStatus;
use Farzai\LaravelSchema\Schema\Column;
use Farzai\LaravelSchema\Schema\Enums\DiffStatus;

/**
 * Immutable value object representing the difference between two columns.
 */
final readonly class ColumnDiff
{
    use HasDiffStatus;

    /**
     * @param  array<string, array{expected: mixed, actual: mixed}>  $changes
     */
    public function __construct(
        public string $name,
        public DiffStatus $status,
        public ?Column $expected = null,
        public ?Column $actual = null,
        public array $changes = [],
    ) {}

    /**
     * Convert to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'status' => $this->status->value,
            'expected' => $this->expected?->toArray(),
            'actual' => $this->actual?->toArray(),
            'changes' => $this->changes,
        ];
    }
}
