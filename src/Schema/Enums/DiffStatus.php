<?php

declare(strict_types=1);

namespace Farzai\LaravelSchema\Schema\Enums;

/**
 * Enum representing the status of a schema difference.
 */
enum DiffStatus: string
{
    case Added = 'added';
    case Removed = 'removed';
    case Modified = 'modified';
    case Unchanged = 'unchanged';

    /**
     * Check if this status indicates a difference.
     */
    public function hasDifference(): bool
    {
        return $this !== self::Unchanged;
    }

    /**
     * Get the display label for this status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Added => 'Added',
            self::Removed => 'Removed',
            self::Modified => 'Modified',
            self::Unchanged => 'Unchanged',
        };
    }

    /**
     * Get the color associated with this status for UI display.
     */
    public function color(): string
    {
        return match ($this) {
            self::Added => 'green',
            self::Removed => 'red',
            self::Modified => 'yellow',
            self::Unchanged => 'gray',
        };
    }
}
