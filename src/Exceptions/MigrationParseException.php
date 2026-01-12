<?php

declare(strict_types=1);

namespace Farzai\LaravelSchema\Exceptions;

/**
 * Exception thrown when a migration file cannot be parsed.
 */
final class MigrationParseException extends SchemaException
{
    public function __construct(string $file, string $reason)
    {
        parent::__construct(
            message: "Failed to parse migration '{$file}': {$reason}"
        );
    }
}
