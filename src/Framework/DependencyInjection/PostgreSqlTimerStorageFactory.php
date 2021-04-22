<?php

namespace Morebec\Orkestra\OrkestraFramework\Framework\DependencyInjection;

use Doctrine\DBAL\Connection;
use Morebec\Orkestra\Messaging\Normalization\MessageNormalizerInterface;
use Morebec\Orkestra\Normalization\ObjectNormalizerInterface;
use Morebec\Orkestra\PostgreSqlTimerStorage\PostgreSqlTimerStorage;
use Morebec\Orkestra\PostgreSqlTimerStorage\PostgreSqlTimerStorageConfiguration;

class PostgreSqlTimerStorageFactory
{
    /**
     * @var ObjectNormalizerInterface
     */
    private $objectNormalizer;
    /**
     * @var MessageNormalizerInterface
     */
    private $messageNormalizer;
    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection, MessageNormalizerInterface $messageNormalizer, ObjectNormalizerInterface $objectNormalizer)
    {
        $this->objectNormalizer = $objectNormalizer;
        $this->messageNormalizer = $messageNormalizer;
        $this->connection = $connection;
    }

    public function create(): PostgreSqlTimerStorage
    {
        $config = new PostgreSqlTimerStorageConfiguration();
        $config->connectionUrl = $_ENV['POSTGRESQL_URL'];

        return new PostgreSqlTimerStorage($this->connection, $config, $this->messageNormalizer, $this->objectNormalizer);
    }
}
