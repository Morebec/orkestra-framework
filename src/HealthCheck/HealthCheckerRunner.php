<?php

namespace Morebec\Orkestra\Framework\HealthCheck;

use DateTimeInterface;
use Morebec\Orkestra\DateTime\ClockInterface;

/**
 * Default implementation of a {@link HealthCheckerRunnerInterface} that receives a list of {@link HealthCheckerInterface}
 * as part of its constructor.
 * It also allows adding new checkers at Runtime.
 */
class HealthCheckerRunner implements HealthCheckerRunnerInterface
{
    /** @var HealthCheckerInterface[] */
    private array $healthCheckers;

    private ClockInterface $clock;

    public function __construct(ClockInterface $clock, iterable $healthCheckers = [])
    {
        foreach ($healthCheckers as $healthChecker) {
            $this->addHealthChecker($healthChecker);
        }
        $this->clock = $clock;
    }

    /**
     * Runs the Health Checks and returns.
     */
    public function run(): HealthCheckerRunnerResult
    {
        $result = new HealthCheckerRunnerResult();

        $startedAt = $this->clock->now();

        foreach ($this->healthCheckers as $healthChecker) {
            $result->addHealthCheck(
                $healthChecker->getName(),
                $healthChecker->check()
            );
        }

        $endedAt = $this->clock->now();

        $durationInMs = $endedAt->getMillisTimestamp() - $startedAt->getMillisTimestamp();

        $result->addContextData('date', $startedAt->format(DateTimeInterface::RFC3339_EXTENDED));
        $result->addContextData('durationMs', $durationInMs);
        $result->addContextData('nbChecks', \count($result->getHealthChecks()));

        return $result;
    }

    public function addHealthChecker($healthChecker): void
    {
        $this->healthCheckers[] = $healthChecker;
    }
}
