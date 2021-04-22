<?php

namespace Morebec\Orkestra\OrkestraFramework\Framework\DependencyInjection;

use Morebec\Orkestra\PostgreSqlEventStore\PostgreSqlEventStoreConfiguration;

class PostgreSqlEventStoreConfigurationFactory
{
    public static function create(): PostgreSqlEventStoreConfiguration
    {
        $configuration = new PostgreSqlEventStoreConfiguration();
        $configuration->notifyTimeout = 1000 * 60;

        return $configuration;
    }
}
