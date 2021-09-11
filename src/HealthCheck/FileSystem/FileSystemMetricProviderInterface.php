<?php

namespace Morebec\Orkestra\Framework\HealthCheck\FileSystem;

interface FileSystemMetricProviderInterface
{
    /**
     * Returns the total space available on the file system in bytes.
     * @return int
     */
    public function getTotalSpace(): int;

    /**
     * Returns the space used on the file system in bytes.
     * @return int
     */
    public function getUsedSpace(): int;

    /**
     * Returns the percentage of space used as percentage, i.e. a number between 0 and 1
     * @return float
     */
    public function getUsedSpaceAsPercentage(): float;

    /**
     * Returns the free space on the file system in bytes.
     * @return int
     */
    public function getFreeSpace(): int;

    /**
     * Returns the percentage of free space as percentage, i.e. a number between 0 and 1
     * @return float
     */
    public function getFreeSpaceAsPercentage(): float;
}