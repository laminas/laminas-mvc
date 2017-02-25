<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Mvc;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\Mvc\Exception\MiddlewareNotCallableException;

final class MiddlewareNotCallableExceptionTest extends TestCase
{
    public function testFromMiddlewareName()
    {
        $middlewareName = uniqid('middlewareName', true);
        $exception = MiddlewareNotCallableException::fromMiddlewareName($middlewareName);

        self::assertInstanceOf(MiddlewareNotCallableException::class, $exception);
        self::assertSame('Cannot dispatch middleware ' . $middlewareName, $exception->getMessage());
        self::assertSame($middlewareName, $exception->toMiddlewareName());
    }

    public function testToMiddlewareNameWhenNotSet()
    {
        $exception = new MiddlewareNotCallableException();
        self::assertSame('', $exception->toMiddlewareName());
    }
}
