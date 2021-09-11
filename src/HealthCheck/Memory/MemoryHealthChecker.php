<?php

namespace Morebec\Orkestra\Framework\HealthCheck\Memory;

use Morebec\Orkestra\Framework\HealthCheck\Health;
use Morebec\Orkestra\Framework\HealthCheck\HealthCheckerInterface;

/**
 * Service checking the health of the system's RAM.
 */
class MemoryHealthChecker implements HealthCheckerInterface
{
    private MemoryMetricsProviderInterface $metricsProvider;

    public function __construct(?MemoryMetricsProviderInterface $metricsProvider = null)
    {
        $this->metricsProvider = $metricsProvider ?: new LinuxMemoryMetricsProvider();
    }

    public function check(): Health
    {
        $freeSpacePercentage = $this->metricsProvider->getFreeMemoryAsPercentage() * 100;

        if ($freeSpacePercentage <= 5.0) {
            $health = Health::down();
        } elseif ($freeSpacePercentage <= 10.0) {
            $health = Health::degraded();
        } else {
            $health = Health::up();
        }

        return $health
            ->withContextData('total', $this->metricsProvider->getTotalMemory())
            ->withContextData('used', $this->metricsProvider->getUsedMemory())
            ->withContextData('free', $this->metricsProvider->getFreeMemory())
            ->withContextData('used%', $this->metricsProvider->getUsedMemoryAsPercentage() * 100)
            ->withContextData('free%', $this->metricsProvider->getFreeMemoryAsPercentage() * 100)
            ->build();
    }

    public function getName(): string
    {
        return 'memory';
    }
}
