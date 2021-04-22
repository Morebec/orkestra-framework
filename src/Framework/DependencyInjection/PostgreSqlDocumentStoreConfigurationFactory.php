<?php

namespace Morebec\Orkestra\OrkestraFramework\Framework\DependencyInjection;

use Doctrine\DBAL\Connection;
use Morebec\Orkestra\DateTime\ClockInterface;
use Morebec\Orkestra\PostgreSqlDocumentStore\PostgreSqlDocumentStore;
use Morebec\Orkestra\PostgreSqlDocumentStore\PostgreSqlDocumentStoreConfiguration;

class PostgreSqlDocumentStoreConfigurationFactory
{
    public static function create(): PostgreSqlDocumentStoreConfiguration
    {
        return new PostgreSqlDocumentStoreConfiguration();
    }
}
