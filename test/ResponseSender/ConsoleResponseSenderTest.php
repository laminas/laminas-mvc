<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\ResponseSender;

use LaminasTest\Mvc\ResponseSender\TestAsset\ConsoleResponseSender;
use PHPUnit_Framework_TestCase as TestCase;

class ConsoleResponseSenderTest extends TestCase
{
    public function testSendResponseIgnoresInvalidResponseTypes()
    {
        $mockResponse = $this->getMockForAbstractClass('Laminas\Stdlib\ResponseInterface');
        $mockSendResponseEvent = $this->getSendResponseEventMock($mockResponse);
        $responseSender = new ConsoleResponseSender();
        ob_start();
        $result = $responseSender($mockSendResponseEvent);
        $body = ob_get_clean();
        $this->assertEquals('', $body);
        $this->assertNull($result);
    }

    public function testSendResponseTwoTimesPrintsResponseOnceAndReturnsErrorLevel()
    {
        $returnValue = false;
        $mockResponse = $this->getMock('Laminas\Console\Response');
        $mockResponse->expects($this->once())->method('getContent')->will($this->returnValue('body'));
        $mockResponse->expects($this->exactly(2))->method('getMetadata')->with('errorLevel', 0)->will($this->returnValue(0));
        $mockSendResponseEvent = $this->getSendResponseEventMock($mockResponse);
        $mockSendResponseEvent->expects($this->once())->method('setContentSent');
        $mockSendResponseEvent->expects($this->any())->method('contentSent')->will($this->returnCallback(
            function () use (&$returnValue) {
                if (false === $returnValue) {
                    $returnValue = true;
                    return false;
                }
                return true;
        }));
        $responseSender = new ConsoleResponseSender();
        ob_start();
        $result = $responseSender($mockSendResponseEvent);
        $body = ob_get_clean();
        $this->assertEquals('body', $body);
        $this->assertEquals(0, $result);

        ob_start();
        $result = $responseSender($mockSendResponseEvent);
        $body = ob_get_clean();
        $this->assertEquals('', $body);
        $this->assertEquals(0, $result);
    }

    protected function getSendResponseEventMock($response)
    {
        $mockSendResponseEvent = $this->getMock('Laminas\Mvc\ResponseSender\SendResponseEvent', array('getResponse', 'contentSent', 'setContentSent'));
        $mockSendResponseEvent->expects($this->any())->method('getResponse')->will($this->returnValue($response));
        return $mockSendResponseEvent;
    }
}
