<?php

namespace Morebec\Orkestra\OrkestraFramework\Framework\DependencyInjection;

use Morebec\Orkestra\PostgreSqlEventStore\PostgreSqlEventStorePositionStorageConfiguration;

class PostgreSqlEventStorePositionStorageConfigurationFactory
{
    public static function create(): PostgreSqlEventStorePositionStorageConfiguration
    {
        return new PostgreSqlEventStorePositionStorageConfiguration();
    }
}
