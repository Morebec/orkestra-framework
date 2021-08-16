<?php

namespace Morebec\Orkestra\Framework\DependencyInjection;

use Doctrine\DBAL\Connection;
use Morebec\Orkestra\Messaging\Normalization\MessageNormalizerInterface;
use Morebec\Orkestra\PostgreSqlTimeoutStorage\PostgreSqlTimeoutStorage;
use Morebec\Orkestra\PostgreSqlTimeoutStorage\PostgreSqlTimeoutStorageConfiguration;
use RuntimeException;

class PostgreSqlTimeoutStorageFactory
{
    private MessageNormalizerInterface $messageNormalizer;

    private Connection $connection;

    public function __construct(Connection $connection, MessageNormalizerInterface $messageNormalizer)
    {
        $this->messageNormalizer = $messageNormalizer;
        $this->connection = $connection;
    }

    /**
     * @throws RuntimeException
     */
    public function create(): PostgreSqlTimeoutStorage
    {
        return new PostgreSqlTimeoutStorage($this->connection, $this->getConfiguration(), $this->messageNormalizer);
    }

    protected function getConfiguration(): PostgreSqlTimeoutStorageConfiguration
    {
        return new PostgreSqlTimeoutStorageConfiguration();
    }
}
