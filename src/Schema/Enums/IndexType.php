<?php

declare(strict_types=1);

namespace Farzai\LaravelSchema\Schema\Enums;

/**
 * Enum representing database index types.
 */
enum IndexType: string
{
    case Primary = 'primary';
    case Unique = 'unique';
    case Index = 'index';
    case Fulltext = 'fulltext';
    case Spatial = 'spatial';

    /**
     * Check if this index enforces uniqueness.
     */
    public function isUnique(): bool
    {
        return in_array($this, [self::Primary, self::Unique], true);
    }

    /**
     * Get the display label for this index type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Primary => 'Primary Key',
            self::Unique => 'Unique',
            self::Index => 'Index',
            self::Fulltext => 'Fulltext',
            self::Spatial => 'Spatial',
        };
    }

    /**
     * Try to create an IndexType from a string value.
     */
    public static function tryFromString(string $value): self
    {
        $normalized = strtolower(trim($value));

        return match ($normalized) {
            'primary', 'primary key', 'pri' => self::Primary,
            'unique', 'uni' => self::Unique,
            'index', 'mul', 'key' => self::Index,
            'fulltext' => self::Fulltext,
            'spatial' => self::Spatial,
            default => self::Index,
        };
    }
}
