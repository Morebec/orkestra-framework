<?php

namespace Morebec\Orkestra\Framework\DependencyInjection;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception;
use Morebec\Orkestra\Retry\RetryContext;
use Morebec\Orkestra\Retry\RetryStrategy;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

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
     * @param string $connectionString database connection string
     * @param Configuration|null $configuration doctrine Connection configuration
     */
    public function __construct(string $connectionString, ?Configuration $configuration = null) {
        $this->configuration = $configuration ?? new Configuration();
        $this->connectionString = $connectionString;
    }

    /**
     * Creates a connection.
     *
     * @throws Exception
     * @throws Throwable
     */
    public function create(): Connection
    {
        return DriverManager::getConnection([
            'url' => $this->connectionString,
        ], $this->configuration);
    }
}
