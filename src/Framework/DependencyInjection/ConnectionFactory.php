<?php

namespace Morebec\Orkestra\OrkestraFramework\Framework\DependencyInjection;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;

class ConnectionFactory
{
    public static function create(): Connection
    {
        $connection = DriverManager::getConnection([
            'url' => $_ENV['POSTGRESQL_URL'],
        ], new Configuration());

        $connection->connect();

        return $connection;
    }
}
