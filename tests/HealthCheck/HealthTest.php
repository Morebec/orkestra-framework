<?php

namespace Tests\Morebec\Orkestra\Framework\HealthCheck;

use Morebec\Orkestra\Framework\HealthCheck\Health;
use Morebec\Orkestra\Framework\HealthCheck\HealthStatus;
use PHPUnit\Framework\TestCase;

class HealthTest extends TestCase
{
    public function testToArray(): void
    {
        $health = Health::up()
            ->withContextData('mem', 123456)
            ->withContextData('freemem', 987654)
            ->build()
        ;

        self::assertEquals([
            'status' => 'UP',
            'mem' => 123456,
            'freemem' => 987654,
        ], $health->toArray());
    }

    public function testUp(): void
    {
        $health = Health::up()
            ->build()
        ;

        self::assertEquals(HealthStatus::UP(), $health->getStatus());
    }

    public function testDown(): void
    {
        $health = Health::down()
            ->build()
        ;

        self::assertEquals(HealthStatus::DOWN(), $health->getStatus());
    }

    public function testDegraded(): void
    {
        $health = Health::degraded()
            ->build()
        ;

        self::assertEquals(HealthStatus::DEGRADED(), $health->getStatus());
    }
}
