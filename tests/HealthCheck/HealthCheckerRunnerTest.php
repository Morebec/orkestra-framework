<?php

namespace Tests\Morebec\Orkestra\Framework\HealthCheck;

use Morebec\Orkestra\DateTime\SystemClock;
use Morebec\Orkestra\Framework\HealthCheck\FileSystem\FileSystemHealthChecker;
use Morebec\Orkestra\Framework\HealthCheck\HealthCheckerRunner;
use Morebec\Orkestra\Framework\HealthCheck\Memory\MemoryHealthChecker;
use PHPUnit\Framework\TestCase;

class HealthCheckerRunnerTest extends TestCase
{
    public function testRun(): void
    {
        $runner = new HealthCheckerRunner(new SystemClock(), [
            new FileSystemHealthChecker(),
            new MemoryHealthChecker()
        ]);

        $result = $runner->run();

        self::assertCount(2, $result->getHealthChecks());
    }
}
