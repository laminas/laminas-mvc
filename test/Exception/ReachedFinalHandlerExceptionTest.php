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
use Zend\Mvc\Exception\ReachedFinalHandlerException;

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
