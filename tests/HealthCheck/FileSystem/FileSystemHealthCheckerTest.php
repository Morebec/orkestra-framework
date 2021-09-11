<?php

namespace Tests\Morebec\Orkestra\Framework\HealthCheck\FileSystem;

use Morebec\Orkestra\Framework\HealthCheck\FileSystem\FileSystemHealthChecker;
use Morebec\Orkestra\Framework\HealthCheck\FileSystem\FileSystemMetricsProvider;
use Morebec\Orkestra\Framework\HealthCheck\FileSystem\FileSystemMetricProviderInterface;
use Morebec\Orkestra\Framework\HealthCheck\HealthStatus;
use PHPUnit\Framework\TestCase;

class FileSystemHealthCheckerTest extends TestCase
{

    public function testCheck(): void
    {
        // TEST UP
        $provider = $this->getMockBuilder(FileSystemMetricProviderInterface::class)->getMock();
        $checker = new FileSystemHealthChecker($provider);

        $provider->method('getUsedSpace')->willReturn(10);
        $provider->method('getFreeSpace')->willReturn(90);
        $provider->method('getFreeSpaceAsPercentage')->willReturn(0.9);
        $provider->method('getUsedSpaceAsPercentage')->willReturn(0.1);
        $provider->method('getTotalSpace')->willReturn(100);
        $result = $checker->check();

        self::assertTrue($result->hasStatus(HealthStatus::UP()));

        // TEST DEGRADED
        $provider = $this->getMockBuilder(FileSystemMetricProviderInterface::class)->getMock();
        $checker = new FileSystemHealthChecker($provider);

        $provider->method('getUsedSpace')->willReturn(80);
        $provider->method('getFreeSpace')->willReturn(20);
        $provider->method('getFreeSpaceAsPercentage')->willReturn(0.2);
        $provider->method('getUsedSpaceAsPercentage')->willReturn(0.8);
        $provider->method('getTotalSpace')->willReturn(100);

        $result = $checker->check();

        self::assertTrue($result->hasStatus(HealthStatus::DEGRADED()));

        // TEST DOWN
        $provider = $this->getMockBuilder(FileSystemMetricProviderInterface::class)->getMock();
        $checker = new FileSystemHealthChecker($provider);

        $provider->method('getUsedSpace')->willReturn(95);
        $provider->method('getFreeSpace')->willReturn(5);
        $provider->method('getFreeSpaceAsPercentage')->willReturn(0.05);
        $provider->method('getUsedSpaceAsPercentage')->willReturn(0.95);
        $provider->method('getTotalSpace')->willReturn(100);
        $result = $checker->check();

        self::assertTrue($result->hasStatus(HealthStatus::DOWN()));

    }
}
