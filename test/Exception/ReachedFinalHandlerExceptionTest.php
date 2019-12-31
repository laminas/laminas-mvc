<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc;

use Laminas\Mvc\Exception\ReachedFinalHandlerException;
use PHPUnit\Framework\TestCase;

final class ReachedFinalHandlerExceptionTest extends TestCase
{
    public function testFromNothing()
    {
        $exception = ReachedFinalHandlerException::create();

        $this->assertInstanceOf(ReachedFinalHandlerException::class, $exception);
        $this->assertSame(
            'Reached the final handler for middleware pipe - check the pipe configuration',
            $exception->getMessage()
        );
    }
}
