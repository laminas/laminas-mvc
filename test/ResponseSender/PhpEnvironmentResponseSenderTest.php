<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\ResponseSender;

use Laminas\Http\PhpEnvironment\Response;
use Laminas\Mvc\ResponseSender\PhpEnvironmentResponseSender;
use Laminas\Mvc\ResponseSender\SendResponseEvent;
use Laminas\Stdlib\ResponseInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

use function ob_get_clean;
use function ob_start;

class PhpEnvironmentResponseSenderTest extends TestCase
{
    public function testSendResponseIgnoresInvalidResponseTypes(): void
    {
        $mockResponse          = $this->getMockForAbstractClass(ResponseInterface::class);
        $mockSendResponseEvent = $this->getSendResponseEventMock();
        $mockSendResponseEvent->method('getResponse')->willReturn($mockResponse);
        $responseSender = new PhpEnvironmentResponseSender();
        ob_start();
        $responseSender($mockSendResponseEvent);
        $body = ob_get_clean();
        $this->assertEquals('', $body);
    }

    public function testSendResponseTwoTimesPrintsResponseOnlyOnce(): void
    {
        $mockResponse = $this->createMock(Response::class);
        $mockResponse->method('getContent')->willReturn('body');
        $mockSendResponseEvent = $this->getSendResponseEventMock();
        $mockSendResponseEvent->method('getResponse')->willReturn($mockResponse);
        $mockSendResponseEvent->expects($this->once())->method('setContentSent');
        $responseSender = new PhpEnvironmentResponseSender();
        ob_start();
        $responseSender($mockSendResponseEvent);
        $body = ob_get_clean();
        $this->assertEquals('body', $body);

        ob_start();
        $responseSender($mockSendResponseEvent);
        $body = ob_get_clean();
        $this->assertEquals('', $body);
    }

    /**
     * @return SendResponseEvent&MockObject
     */
    protected function getSendResponseEventMock(): MockObject
    {
        $returnValue           = false;
        $mockSendResponseEvent = $this->getMockBuilder(SendResponseEvent::class)
            ->onlyMethods(['getResponse', 'contentSent', 'setContentSent'])
            ->getMock();

        $mockSendResponseEvent->method('contentSent')
            ->willReturnCallback(static function () use (&$returnValue): bool {
                if (false === $returnValue) {
                    $returnValue = true;
                    return false;
                }
                return true;
            });
        return $mockSendResponseEvent;
    }
}
