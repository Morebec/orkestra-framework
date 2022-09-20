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
     * @throws Throwable
     */
    public function create(): Connection
    {
        $connection = DriverManager::getConnection([
            'url' => $this->connectionString,
        ], $this->configuration);


        if ($this->autoConnect) {
            $retry = RetryStrategy::create()
                ->maximumAttempts(50)
                ->useExponentialBackoff(
                    1000 * 10, // 10 seconds
                    2.0,
                    1000 * 60 * 5 // 5 minutes
                )
                ->onError(function (RetryContext $context, Throwable $exception) {
                    if ($context->isLastAttempt()) {
                        $this->logger->info("Connection to database failed, will not retry: {$exception->getMessage()}");
                    } else {
                        $this->logger->info("Connection to database failed, will retry: {$exception->getMessage()}");
                    }
                })
            ;
            $retry->execute(function () use ($connection) {
                $this->logger->info('Connecting to database ...');
                $connection->connect();
                // explicitly auto commit
                $connection->setAutoCommit(true);
                $this->logger->info('Database connection established.');
            });
        }

        return $connection;
    }
}
