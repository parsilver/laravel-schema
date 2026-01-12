<?php

declare(strict_types=1);

namespace Farzai\LaravelSchema\Exceptions;

/**
 * Exception thrown when an unsupported database driver is encountered.
 */
final class UnsupportedDriverException extends SchemaException
{
    public function __construct(string $driver)
    {
        parent::__construct(
            message: "Unsupported database driver: {$driver}. Supported drivers are: mysql, mariadb, pgsql, sqlite, sqlsrv."
        );
    }
}
