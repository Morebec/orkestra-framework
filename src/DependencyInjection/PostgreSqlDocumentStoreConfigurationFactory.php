<?php

namespace Morebec\Orkestra\Framework\DependencyInjection;

use Morebec\Orkestra\PostgreSqlDocumentStore\PostgreSqlDocumentStoreConfiguration;

class PostgreSqlDocumentStoreConfigurationFactory
{
    public static function create(): PostgreSqlDocumentStoreConfiguration
    {
        return new PostgreSqlDocumentStoreConfiguration();
    }
}
