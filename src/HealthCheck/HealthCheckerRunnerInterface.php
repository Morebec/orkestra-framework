<?php

namespace Morebec\Orkestra\Framework\HealthCheck;

interface HealthCheckerRunnerInterface
{
    /**
     * Runs the health checkers of this runner and returns their results.
     */
    public function run(): HealthCheckerRunnerResult;
}
