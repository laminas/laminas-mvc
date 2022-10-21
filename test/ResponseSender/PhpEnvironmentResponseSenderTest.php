<?php

namespace LaminasTest\Mvc\ResponseSender;

use Laminas\Http\PhpEnvironment\Response;
use Laminas\Mvc\ResponseSender\PhpEnvironmentResponseSender;
use Laminas\Mvc\ResponseSender\SendResponseEvent;
use Laminas\Stdlib\ResponseInterface;
use PHPUnit\Framework\TestCase;

class PhpEnvironmentResponseSenderTest extends TestCase
{
    public function testSendResponseIgnoresInvalidResponseTypes()
    {
        $mockResponse = $this->getMockForAbstractClass(ResponseInterface::class);
        $mockSendResponseEvent = $this->getSendResponseEventMock();
        $mockSendResponseEvent->expects($this->any())->method('getResponse')->will($this->returnValue($mockResponse));
        $responseSender = new PhpEnvironmentResponseSender();
        ob_start();
        $responseSender($mockSendResponseEvent);
        $body = ob_get_clean();
        $this->assertEquals('', $body);
    }

    public function testSendResponseTwoTimesPrintsResponseOnlyOnce()
    {
        $mockResponse = $this->createMock(Response::class);
        $mockResponse->expects($this->any())->method('getContent')->will($this->returnValue('body'));
        $mockSendResponseEvent = $this->getSendResponseEventMock();
        $mockSendResponseEvent->expects($this->any())->method('getResponse')->will($this->returnValue($mockResponse));
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

    protected function getSendResponseEventMock()
    {
        $returnValue = false;
        $mockSendResponseEvent = $this->getMockBuilder(SendResponseEvent::class)
            ->setMethods(['getResponse', 'contentSent', 'setContentSent'])
            ->getMock();

        $mockSendResponseEvent->expects($this->any())
            ->method('contentSent')
            ->will($this->returnCallback(static function () use (&$returnValue) : bool {
                if (false === $returnValue) {
                    $returnValue = true;
                    return false;
                }
                return true;
            }));
        return $mockSendResponseEvent;
    }
}
