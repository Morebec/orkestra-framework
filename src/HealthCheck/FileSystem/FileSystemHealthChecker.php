<?php

namespace Morebec\Orkestra\Framework\HealthCheck\FileSystem;

use Morebec\Orkestra\Framework\HealthCheck\Health;
use Morebec\Orkestra\Framework\HealthCheck\HealthCheckerInterface;

/**
 * Checks for the health of the file system.
 */
class FileSystemHealthChecker implements HealthCheckerInterface
{
    private FileSystemMetricProviderInterface $metricsProvider;

    private float $downThreshold;

    private float $degradationThreshold;

    public function __construct(
        ?FileSystemMetricProviderInterface $fileSystemMetricProvider = null,
        float $downThreshold = 0.1,
        float $degradationThreshold = 0.2
    ) {
        $this->metricsProvider = $fileSystemMetricProvider ?: new FileSystemMetricsProvider(__DIR__);
        $this->downThreshold = $downThreshold;
        $this->degradationThreshold = $degradationThreshold;
    }

    public function check(): Health
    {
        $freeSpacePercentage = $this->metricsProvider->getFreeSpaceAsPercentage();

        if ($freeSpacePercentage <= $this->downThreshold) {
            $health = Health::down();
        } elseif ($freeSpacePercentage <= $this->degradationThreshold) {
            $health = Health::degraded();
        } else {
            $health = Health::up();
        }

        return $health
            ->withContextData('total', $this->metricsProvider->getTotalSpace())
            ->withContextData('used', $this->metricsProvider->getUsedSpace())
            ->withContextData('free', $this->metricsProvider->getFreeSpace())
            ->withContextData('used%', $this->metricsProvider->getUsedSpaceAsPercentage() * 100)
            ->withContextData('free%', $this->metricsProvider->getFreeSpaceAsPercentage() * 100)
            ->withContextData('downThreshold%', $this->downThreshold * 100)
            ->withContextData('degradationThreshold%', $this->degradationThreshold * 100)
            ->build();
    }

    public function getName(): string
    {
        return 'fileSystem';
    }
}
