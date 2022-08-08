<?php

namespace Morebec\Orkestra\Framework\DependencyInjection;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Factory for a {@link Connection}.
 */
class ConnectionFactory
{
    private ?Configuration $configuration;
    private string $connectionString;
    private bool $autoConnect;
    private LoggerInterface $logger;

    /**
     * @param string             $connectionString database connection string
     * @param Configuration|null $configuration    doctrine Connection configuration
     * @param bool               $autoConnect      indicates if connecting to the database should be performed automatically when creating the connection
     */
    public function __construct(
        string $connectionString,
        ?Configuration $configuration = null,
        bool $autoConnect = true,
        ?LoggerInterface $logger = null
    ) {
        $this->configuration = $configuration ?? new Configuration();
        $this->connectionString = $connectionString;
        $this->autoConnect = $autoConnect;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Creates a connection.
     *
     * @throws Exception
     */
    public function create(): Connection
    {
        $connection = DriverManager::getConnection([
            'url' => $this->connectionString,
        ], $this->configuration);

        if ($this->autoConnect) {
            $this->logger->info('Connecting to database ...');
            $connection->connect();
            $this->logger->info('Database connection established.');
        }

        // explicitly auto commit
        $connection->setAutoCommit(true);

        return $connection;
    }
}
