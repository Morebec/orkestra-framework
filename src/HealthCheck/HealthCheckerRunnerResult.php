<?php

namespace Morebec\Orkestra\Framework\HealthCheck;

/**
 * Represents the result of a {@link HealthCheckerRunnerInterface run.
 */
class HealthCheckerRunnerResult
{
    /** @var Health[] */
    private array $healthChecks;

    private array $contextData;

    public function __construct()
    {
        $this->healthChecks = [];
        $this->contextData = [];
    }

    public function addHealthCheck(string $healthCheckerName, Health $health): self
    {
        $this->healthChecks[$healthCheckerName] = $health;

        return $this;
    }

    public function addContextData(string $key, $value): self
    {
        $this->contextData[$key] = $value;

        return $this;
    }

    public function hasHealthWithStatus(HealthStatus $status): bool
    {
        foreach ($this->healthChecks as $healthCheck) {
            if ($healthCheck->hasStatus($status)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return Health[]
     */
    public function getHealthChecks(): array
    {
        return $this->healthChecks;
    }

    public function toArray(): array
    {
        $data = $this->contextData;
        $data['checks'] = [];

        foreach ($this->healthChecks as $healthCheckerName => $healthCheck) {
            $data['checks'][$healthCheckerName] = $healthCheck->toArray();
        }

        return $data;
    }
}
