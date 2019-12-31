<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\ResponseSender;

use Laminas\Mvc\ResponseSender\PhpEnvironmentResponseSender;
use PHPUnit_Framework_TestCase as TestCase;

class PhpEnvironmentResponseSenderTest extends TestCase
{
    public function testSendResponseIgnoresInvalidResponseTypes()
    {
        $mockResponse = $this->getMockForAbstractClass('Laminas\Stdlib\ResponseInterface');
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
        $mockResponse = $this->getMock('Laminas\Http\PhpEnvironment\Response');
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
        $mockSendResponseEvent = $this->getMock(
            'Laminas\Mvc\ResponseSender\SendResponseEvent',
            array('getResponse', 'contentSent', 'setContentSent')
        );
        $mockSendResponseEvent->expects($this->any())
            ->method('contentSent')
            ->will($this->returnCallback(function () use (&$returnValue) {
                if (false === $returnValue) {
                    $returnValue = true;
                    return false;
                }
                return true;
        }));
        return $mockSendResponseEvent;
    }
}
