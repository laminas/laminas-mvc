<?php

declare(strict_types=1);

namespace LaminasTest\Mvc;

use Laminas\Mvc\Exception\InvalidMiddlewareException;
use PHPUnit\Framework\TestCase;

use function uniqid;

final class InvalidMiddlewareExceptionTest extends TestCase
{
    public function testFromMiddlewareName()
    {
        $middlewareName = uniqid('middlewareName', true);
        $exception      = InvalidMiddlewareException::fromMiddlewareName($middlewareName);

        self::assertInstanceOf(InvalidMiddlewareException::class, $exception);
        self::assertSame('Cannot dispatch middleware ' . $middlewareName, $exception->getMessage());
        self::assertSame($middlewareName, $exception->toMiddlewareName());
    }

    public function testToMiddlewareNameWhenNotSet()
    {
        $exception = new InvalidMiddlewareException();
        self::assertSame('', $exception->toMiddlewareName());
    }

    public function testFromNull()
    {
        $exception = InvalidMiddlewareException::fromNull();

        self::assertInstanceOf(InvalidMiddlewareException::class, $exception);
        self::assertSame('Middleware name cannot be null', $exception->getMessage());
        self::assertSame('', $exception->toMiddlewareName());
    }
}
