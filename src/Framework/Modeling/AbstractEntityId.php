<?php

namespace Morebec\Orkestra\OrkestraFramework\Framework\Modeling;

use Ramsey\Uuid\Uuid;

/**
 * Abstract Value object class to easily create Identifier value objects.
 */
abstract class AbstractEntityId
{
    /**
     * @var string
     */
    private $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * @return static
     */
    public static function fromString(string $id): self
    {
        return new static($id);
    }

    /**
     * Generate a new ID.
     *
     * @return static
     */
    public static function generate(): self
    {
        return new static(Uuid::uuid4());
    }

    /**
     * Indicates if this ID is equal to another one or not.
     *
     * @param AbstractEntityId $id
     */
    public function isEqualTo(self $id): bool
    {
        return $this->value === $id->value;
    }
}
