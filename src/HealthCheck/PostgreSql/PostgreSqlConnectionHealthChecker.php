<?php

namespace Morebec\Orkestra\Framework\HealthCheck\PostgreSql;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Morebec\Orkestra\Framework\HealthCheck\Health;
use Morebec\Orkestra\Framework\HealthCheck\HealthCheckerInterface;

/**
 * Simple health checker for a connection of a PostgreSQL Database.
 * It essentially checks that a connection is established or can be established with the
 * Database server.
 */
class PostgreSqlConnectionHealthChecker implements HealthCheckerInterface
{
    private Connection $connection;
    private string $connectionName;

    public function __construct(Connection $connection, string $connectionName = 'default')
    {
        $this->connection = $connection;
        $this->connectionName = $connectionName;
    }

    public function check(): Health
    {
        if ($this->connection->isConnected()) {
            return Health::up()->build();
        }

        try {
            $this->connection->connect();

            return Health::up()->build();
        } catch (Exception $e) {
            return Health::down()
                ->withThrowable($e)
                ->build()
            ;
        }
    }

    public function getName(): string
    {
        return 'postgresql:connection:'.$this->connectionName;
    }
}
