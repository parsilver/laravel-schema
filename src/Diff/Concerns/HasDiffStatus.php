<?php

declare(strict_types=1);

namespace Farzai\LaravelSchema\Diff\Concerns;

use Farzai\LaravelSchema\Schema\Enums\DiffStatus;

/**
 * Trait for diff status checking methods.
 *
 * Classes using this trait must have a public DiffStatus $status property.
 */
trait HasDiffStatus
{
    /**
     * Check if the item was added.
     */
    public function isAdded(): bool
    {
        return $this->status === DiffStatus::Added;
    }

    /**
     * Check if the item was removed.
     */
    public function isRemoved(): bool
    {
        return $this->status === DiffStatus::Removed;
    }

    /**
     * Check if the item was modified.
     */
    public function isModified(): bool
    {
        return $this->status === DiffStatus::Modified;
    }

    /**
     * Check if the item is unchanged.
     */
    public function isUnchanged(): bool
    {
        return $this->status === DiffStatus::Unchanged;
    }

    /**
     * Check if there are any differences.
     */
    public function hasDifference(): bool
    {
        return $this->status->hasDifference();
    }
}
