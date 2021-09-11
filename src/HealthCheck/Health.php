<?php

namespace Morebec\Orkestra\Framework\HealthCheck;

class Health
{
    private HealthStatus $status;

    private array $contextData;

    public function __construct(
        HealthStatus $status,
        array $contextData
    ) {
        $this->status = $status;
        $this->contextData = $contextData;
    }

    public static function up(): HealthBuilder
    {
        return new HealthBuilder(HealthStatus::UP());
    }

    public static function down(): HealthBuilder
    {
        return new HealthBuilder(HealthStatus::DOWN());
    }

    public static function degraded(): HealthBuilder
    {
        return new HealthBuilder(HealthStatus::DEGRADED());
    }

    /**
     * Indicates if this Health as a given status.
     */
    public function hasStatus(HealthStatus $status): bool
    {
        return $this->status->isEqualTo($status);
    }

    public function getStatus(): HealthStatus
    {
        return $this->status;
    }

    public function toArray(): array
    {
        $data = $this->contextData;
        $data['status'] = (string) $this->getStatus();

        return $data;
    }
}
