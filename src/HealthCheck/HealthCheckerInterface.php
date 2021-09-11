<?php

namespace Morebec\Orkestra\Framework\HealthCheck;

/**
 * Represents a health check of a component of the system.
 */
interface HealthCheckerInterface
{
    /**
     * Checks the health of this indicator.
     */
    public function check(): Health;

    /**
     * Name of this health check.
     */
    public function getName(): string;
}
