<?php

declare(strict_types=1);

namespace Farzai\LaravelSchema\Introspectors;

use Farzai\LaravelSchema\Contracts\IntrospectorFactoryInterface;
use Farzai\LaravelSchema\Contracts\SchemaIntrospectorInterface;
use Farzai\LaravelSchema\Exceptions\UnsupportedDriverException;
use Illuminate\Database\Connection;

/**
 * Factory for creating database introspectors.
 */
final class IntrospectorFactory implements IntrospectorFactoryInterface
{
    public function __construct(
        private Connection $connection,
    ) {}

    /**
     * Create an introspector for the current or specified driver.
     *
     * @throws UnsupportedDriverException
     */
    public function create(?string $driver = null): SchemaIntrospectorInterface
    {
        $driver ??= $this->connection->getDriverName();

        return match ($driver) {
            'mysql', 'mariadb' => new MySqlIntrospector($this->connection),
            'pgsql' => new PostgresIntrospector($this->connection),
            'sqlite' => new SqliteIntrospector($this->connection),
            'sqlsrv' => new SqlServerIntrospector($this->connection),
            default => throw new UnsupportedDriverException($driver),
        };
    }

    /**
     * Check if a driver is supported.
     */
    public function isSupported(string $driver): bool
    {
        return in_array($driver, ['mysql', 'mariadb', 'pgsql', 'sqlite', 'sqlsrv'], true);
    }

    /**
     * Get the list of supported drivers.
     *
     * @return array<int, string>
     */
    public function getSupportedDrivers(): array
    {
        return ['mysql', 'mariadb', 'pgsql', 'sqlite', 'sqlsrv'];
    }
}
