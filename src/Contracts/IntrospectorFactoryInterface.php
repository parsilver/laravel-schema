<?php

declare(strict_types=1);

namespace Farzai\LaravelSchema\Contracts;

/**
 * Interface for creating database-specific schema introspectors.
 */
interface IntrospectorFactoryInterface
{
    /**
     * Create an introspector for the specified database driver.
     *
     * @param  string|null  $driver  The database driver name (mysql, pgsql, sqlite, sqlsrv).
     *                               If null, uses the current connection's driver.
     *
     * @throws \Farzai\LaravelSchema\Exceptions\UnsupportedDriverException
     */
    public function create(?string $driver = null): SchemaIntrospectorInterface;

    /**
     * Check if a database driver is supported.
     */
    public function isSupported(string $driver): bool;

    /**
     * Get list of all supported database drivers.
     *
     * @return array<int, string>
     */
    public function getSupportedDrivers(): array;
}
