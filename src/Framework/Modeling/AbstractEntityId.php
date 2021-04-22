<?php

namespace Morebec\Orkestra\OrkestraFramework\Framework\Modeling;

use Ramsey\Uuid\Uuid;

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

    public static function fromString(string $id)
    {
        return new static($id);
    }

    public static function generate(): self
    {
        return new static(Uuid::uuid4());
    }

    public function isEqualTo(self $id): bool
    {
        return $this->value === $id->value;
    }
}
