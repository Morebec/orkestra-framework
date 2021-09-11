<?php

namespace Morebec\Orkestra\Framework\HealthCheck;

/**
 * Class used to easily build {@link Health} instances with a fluent API.
 */
class HealthBuilder
{
    private HealthStatus $status;

    private array $contextData;

    public function __construct(HealthStatus $status)
    {
        $this->status = $status;
        $this->contextData = [];
    }

    public function withContextData(string $key, $value): self
    {
        $this->contextData[$key] = $value;

        return $this;
    }

    public function withThrowable(\Throwable $throwable): self
    {
        return $this->withContextData(
            'error',
            [
                'name' => (new \ReflectionClass($throwable))->getName(),
                'message' => $throwable->getMessage(),
                'line' => $throwable->getLine(),
                'file' => $throwable->getFile(),
            ]
        );
    }

    public function build(): Health
    {
        return new Health($this->status, $this->contextData);
    }
}
