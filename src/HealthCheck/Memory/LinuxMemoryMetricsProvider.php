<?php

namespace Morebec\Orkestra\Framework\HealthCheck\Memory;

/**
 * Implementation of a {@link MemoryMetricsProviderInterface} for linux that uses the /proc/mem file to get the information.
 */
class LinuxMemoryMetricsProvider implements MemoryMetricsProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function getTotalMemory(): int
    {
        $data = $this->readProcMemFile();

        return $data['MemTotal'] * 1024;
    }

    /**
     * {@inheritDoc}
     */
    public function getUsedMemory(): int
    {
        $data = $this->readProcMemFile();

        return ($data['MemTotal'] - $data['MemFree']) * 1024;
    }

    /**
     * {@inheritDoc}
     */
    public function getUsedMemoryAsPercentage(): float
    {
        $data = $this->readProcMemFile();

        return round(($data['MemTotal'] - $data['MemFree']) / $data['MemTotal'], 4);
    }

    /**
     * {@inheritDoc}
     */
    public function getFreeMemory(): int
    {
        $data = $this->readProcMemFile();

        return ($data['MemFree']) * 1024;
    }

    /**
     * {@inheritDoc}
     */
    public function getFreeMemoryAsPercentage(): float
    {
        $data = $this->readProcMemFile();

        return round($data['MemFree'] / $data['MemTotal'], 4);
    }

    /**
     * Reads the /proc/mem and returns the values as an array.
     * The values returned by /proc/meminfo are in kB.
     *
     * @return array {'MemTotal': int, 'MemFree}
     */
    private function readProcMemFile(): array
    {
        $memContent = file_get_contents('/proc/meminfo');
        if ($memContent === false) {
            throw new \RuntimeException('Could not read /proc/meminfo file');
        }
        $data = explode("\n", $memContent);
        $memInfo = [];
        foreach ($data as $line) {
            if (!$line) {
                continue;
            }

            [$key, $val] = explode(':', $line);
            $memInfo[trim($key)] = (int) str_replace('kB', '', trim($val));
        }

        return $memInfo;
    }
}
