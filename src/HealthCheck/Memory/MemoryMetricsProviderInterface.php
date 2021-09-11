<?php

namespace Morebec\Orkestra\Framework\HealthCheck\Memory;

interface MemoryMetricsProviderInterface
{
    /**
     * Returns the total memory of the system in bytes.
     * @return int
     */
    public function getTotalMemory(): int;

    /**
     * Returns the memory used by the system in bytes.
     * @return int
     */
    public function getUsedMemory(): int;

    /**
     * Returns the memory used as a decimal percentage, i.e. a number between 0 and 1.
     * @return float
     */
    public function getUsedMemoryAsPercentage(): float;

    /**
     * Returns the free memory in bytes.
     * @return int
     */
    public function getFreeMemory(): int;

    /**
     * Returns the memory used as a decimal percentage, i.e. a number between 0 and 1.
     * @return float
     */
    public function getFreeMemoryAsPercentage(): float;
}