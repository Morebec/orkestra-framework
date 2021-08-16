<?php

namespace Tests\Morebec\Orkestra\Framework\Optional;

use Morebec\Orkestra\Framework\Optional\Optional;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;

class OptionalTest extends TestCase
{
    public function testIsNone(): void
    {
        // Optionals
        self::assertTrue(Optional::isNone(Optional::none()));
        self::assertFalse(Optional::isNone(Optional::some(150)));

        // Objects
        self::assertTrue(Optional::isNone(null));
        self::assertFalse(Optional::isNone(new stdClass()));

        // Bool
        self::assertTrue(Optional::isNone(false));
        self::assertFalse(Optional::isNone(true));

        // String
        self::assertTrue(Optional::isNone(''));
        self::assertFalse(Optional::isNone('hello world'));

        // Numbers
        self::assertTrue(Optional::isNone(0));
        self::assertFalse(Optional::isNone(1));
        self::assertTrue(Optional::isNone(0.0));
        self::assertFalse(Optional::isNone(1.0));
    }

    public function testIsSome(): void
    {
        // Optionals
        self::assertFalse(Optional::isSome(Optional::none()));
        self::assertTrue(Optional::isSome(Optional::some(150)));

        // Objects
        self::assertFalse(Optional::isSome(null));
        self::assertTrue(Optional::isSome(new stdClass()));

        // Bool
        self::assertFalse(Optional::isSome(false));
        self::assertTrue(Optional::isSome(true));

        // String
        self::assertFalse(Optional::isSome(''));
        self::assertTrue(Optional::isSome('hello world'));

        // Numbers
        self::assertFalse(Optional::isSome(0));
        self::assertTrue(Optional::isSome(1));
        self::assertFalse(Optional::isSome(0.0));
        self::assertTrue(Optional::isSome(1.0));
    }

    public function testNone(): void
    {
        $optional = Optional::none();
        self::assertNull($optional->get());
    }

    public function testGet(): void
    {
        $optional = Optional::none();
        self::assertNull($optional->get());

        $optional = Optional::some(50);
        self::assertEquals(50, $optional->get());
    }

    public function testGetOrElse(): void
    {
        self::assertEquals(50, Optional::none()->getOrElse(50));
        self::assertEquals(100, Optional::some(100)->getOrElse(50));

        // Nested
        $optional = new Optional(Optional::none());
        self::assertEquals('hello', $optional->getOrElse('hello'));
    }

    public function testGetOrThrow(): void
    {
        self::assertEquals(100, Optional::some(100)->getOrThrow(new RuntimeException()));

        $this->expectException(RuntimeException::class);
        Optional::none()->getOrThrow(new RuntimeException(''));

        // Nested
        $optional = new Optional(Optional::none());
        self::assertEquals('hello', $optional->getOrThrow(new RuntimeException('Oh no!')));
    }

    public function testGetOrThrowNested(): void
    {
        // Nested
        $this->expectException(RuntimeException::class);
        $optional = new Optional(Optional::none());
        self::assertEquals('hello', $optional->getOrThrow(new RuntimeException('Oh no!')));
    }

    public function testGetOrCall(): void
    {
        $mock = $this->createPartialMock(\stdClass::class, ['__invoke']);
        $mock
            ->expects(self::never())
            ->method('__invoke');

        self::assertEquals(100, Optional::some(100)->getOrCall($mock));

        $mock = $this->createPartialMock(\stdClass::class, ['__invoke']);
        $mock
            ->expects(self::once())
            ->method('__invoke')
            ->willReturn(50)
        ;

        self::assertEquals(50, Optional::none()->getOrCall($mock));

        $mock = $this->createPartialMock(\stdClass::class, ['__invoke']);
        $mock
            ->expects(self::once())
            ->method('__invoke')
            ->willReturn(50)
        ;

        $optional = new Optional(Optional::none());
        self::assertEquals(50, $optional->getOrCall($mock));
    }

    public function testSome(): void
    {
        $optional = Optional::some(150);
        self::assertEquals(150, $optional->get());

        $this->expectException(\InvalidArgumentException::class);
        Optional::some(null);
    }

    public function testSomeNested(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Optional::some(Optional::none());
    }

    public function testIsPresent(): void
    {
        self::assertFalse(Optional::none()->isPresent());
        self::assertTrue(Optional::some(500)->isPresent());

        self::assertFalse((new Optional(Optional::none()))->isPresent());
    }

    public function testOrElse(): void
    {
        $optional =
            Optional::none()
                ->orElse(null)
                ->orElse(0)
                ->orElse(56)
        ;

        self::assertEquals(56, $optional->get());
    }

    public function testIfPresentCall(): void
    {
        $value = Optional::of('')
            ->ifPresentCall(fn ($v) => trim($v))
            ->ifPresentCall(fn ($v) => [
                'value' => $v,
            ])
            ->getOrElse(null);

        self::assertNull($value);

        $value = Optional::of(' ')
            ->ifPresentCall(fn ($v) => trim($v))
            ->ifPresentCall(fn ($v) => [
                'value' => $v,
            ])
            ->getOrElse(null);

        self::assertNull($value);

        $value = Optional::of('hello world')
            ->ifPresentCall(fn ($v) => trim($v))
            ->ifPresentCall(fn ($v) => [
                'value' => $v,
            ])
            ->getOrElse(null);

        self::assertEquals(['value' => 'hello world'], $value);
    }
}
