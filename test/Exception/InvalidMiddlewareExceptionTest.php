<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Mvc;

use PHPUnit\Framework\TestCase;
use Zend\Mvc\Exception\InvalidMiddlewareException;

final class InvalidMiddlewareExceptionTest extends TestCase
{
    public function testFromMiddlewareName()
    {
        $middlewareName = uniqid('middlewareName', true);
        $exception = InvalidMiddlewareException::fromMiddlewareName($middlewareName);

        $this->assertInstanceOf(InvalidMiddlewareException::class, $exception);
        $this->assertSame('Cannot dispatch middleware ' . $middlewareName, $exception->getMessage());
        $this->assertSame($middlewareName, $exception->toMiddlewareName());
    }

    public function testToMiddlewareNameWhenNotSet()
    {
        $exception = new InvalidMiddlewareException();
        $this->assertSame('', $exception->toMiddlewareName());
    }

    public function testFromNull()
    {
        $exception = InvalidMiddlewareException::fromNull();

        $this->assertInstanceOf(InvalidMiddlewareException::class, $exception);
        $this->assertSame('Middleware name cannot be null', $exception->getMessage());
        $this->assertSame('', $exception->toMiddlewareName());
    }
}
