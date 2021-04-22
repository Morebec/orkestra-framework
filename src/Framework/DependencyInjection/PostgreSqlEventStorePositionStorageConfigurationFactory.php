<?php

namespace Morebec\Orkestra\OrkestraFramework\Framework\DependencyInjection;

use Doctrine\DBAL\Connection;
use Morebec\Orkestra\PostgreSqlEventStore\PostgreSqlEventStorePositionStorage;
use Morebec\Orkestra\PostgreSqlEventStore\PostgreSqlEventStorePositionStorageConfiguration;

class PostgreSqlEventStorePositionStorageConfigurationFactory
{
    public static function create(): PostgreSqlEventStorePositionStorageConfiguration
    {
        return new PostgreSqlEventStorePositionStorageConfiguration();
    }
}
