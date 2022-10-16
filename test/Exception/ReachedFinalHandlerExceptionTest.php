<?php

declare(strict_types=1);

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
