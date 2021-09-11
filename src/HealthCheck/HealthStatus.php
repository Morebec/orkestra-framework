<?php

namespace Morebec\Orkestra\Framework\HealthCheck;

use Morebec\Orkestra\Enum\Enum;

/**
 * @method static self UP()
 * @method static self DEGRADED()
 * @method static self DOWN()
 */
class HealthStatus extends Enum
{
    /**
     * Indicates that the component checked is working as expected.
     */
    public const UP = 'UP';

    /**
     * Indicates that the component checked is not working as expected, but cannot be considered down either.
     */
    public const DEGRADED = 'DEGRADED';

    /**
     * Indicates that the component checked is not working as expected.
     */
    public const DOWN = 'DOWN';
}
