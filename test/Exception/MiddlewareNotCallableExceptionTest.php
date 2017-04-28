<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2017 Zend Technologies USA Inc. (http://www.zend.com)
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

        $this->assertInstanceOf(MiddlewareNotCallableException::class, $exception);
        $this->assertSame('Cannot dispatch middleware ' . $middlewareName, $exception->getMessage());
        $this->assertSame($middlewareName, $exception->toMiddlewareName());
    }

    public function testToMiddlewareNameWhenNotSet()
    {
        $exception = new MiddlewareNotCallableException();
        $this->assertSame('', $exception->toMiddlewareName());
    }
}
