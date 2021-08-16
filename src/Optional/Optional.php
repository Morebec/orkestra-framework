<?php

namespace Morebec\Orkestra\Framework\Optional;

use Throwable;

/**
 * @template T
 */
class Optional
{
    /**
     * @var T
     */
    private $value;

    /**
     * @param T $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * Indicates if a value is truthy.
     * Intended to make some conditionals more readable.
     *
     * @param mixed $v
     */
    public static function isSome($v): bool
    {
        if ($v instanceof self) {
            return $v->isPresent();
        }

        return (bool) $v;
    }

    /**
     * Indicates if a value is falsy.
     * Intended to make some conditionals more readable.
     *
     * @param mixed $v
     */
    public static function isNone($v): bool
    {
        if ($v instanceof self) {
            return !$v->isPresent();
        }

        return !$v;
    }

    /**
     * Creates a none instance.
     *
     * @return Optional<null>
     */
    public static function none(): self
    {
        return new self(null);
    }

    /**
     * Creates a some instance.
     *
     * @param T $v
     *
     * @return Optional<T>
     */
    public static function some($value): self
    {
        if (($value instanceof self) && !$value->isPresent()) {
            throw new \InvalidArgumentException('Cannot create some from none');
        }
        if (!$value) {
            throw new \InvalidArgumentException('Cannot create some from none');
        }

        return new self($value);
    }

    /**
     * Helper factory method for any type of value.
     *
     * @param T $v
     *
     * @return Optional<T>
     */
    public static function of($v): self
    {
        return new self($v);
    }

    /**
     * @return T
     */
    public function get()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     *
     * @return T
     */
    public function getOrElse($value)
    {
        return $this->isPresent() ? $this->value : $value;
    }

    /**
     * @return mixed
     *
     * @throws Throwable
     */
    public function getOrThrow(Throwable $throwable)
    {
        if ($this->isPresent()) {
            return $this->value;
        }

        throw $throwable;
    }

    /**
     * @return mixed
     */
    public function getOrCall(callable $c)
    {
        return $this->isPresent() ?: $c();
    }

    /**
     * @param mixed $value
     *
     * @return Optional<T>
     */
    public function orElse($value): self
    {
        return $this->isPresent() ? $this : new self($value);
    }

    /**
     * Indicates if there is a value present in this optional or not.
     * Returns true if value is present, otherwise false.
     */
    public function isPresent(): bool
    {
        if ($this->value instanceof self) {
            return $this->value->isPresent();
        }

        return (bool) $this->value;
    }

    /**
     * If the value is present calls the provided callable
     * with the value of this optional and returns the value returned by the callable as an optional
     * Otherwise, returns itself.
     */
    public function ifPresentCall(callable $c): self
    {
        if ($this->isPresent()) {
            $ret = $c($this->value);

            return $ret instanceof self ? $ret : new self($ret);
        }

        return $this;
    }
}
