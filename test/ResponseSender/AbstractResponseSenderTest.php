<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Mvc\ResponseSender;

use PHPUnit\Framework\TestCase;
use Zend\Http\Headers;
use Zend\Http\Response;
use Zend\Mvc\ResponseSender\AbstractResponseSender;
use Zend\Mvc\ResponseSender\SendResponseEvent;

class AbstractResponseSenderTest extends TestCase
{
    /**
     * @runInSeparateProcess
     */
    public function testSendHeadersTwoTimesSendsOnlyOnce()
    {
        if (! function_exists('xdebug_get_headers')) {
            $this->markTestSkipped('Xdebug extension needed, skipped test');
        }
        $headers = [
            'Content-Length: 2000',
            'Transfer-Encoding: chunked'
        ];
        $response = new Response();
        $response->getHeaders()->addHeaders($headers);

        $mockSendResponseEvent = $this->getMockBuilder(SendResponseEvent::class)
            ->setMethods(['getResponse'])
            ->getMock();

        $mockSendResponseEvent->expects(
            $this->any()
        )
                ->method('getResponse')
                ->will($this->returnValue($response));

        $responseSender = $this->getMockForAbstractClass(AbstractResponseSender::class);
        $responseSender->sendHeaders($mockSendResponseEvent);

        $sentHeaders = xdebug_get_headers();
        $diff = array_diff($sentHeaders, $headers);

        if (count($diff)) {
            $header = array_shift($diff);
            $this->assertContains('XDEBUG_SESSION', $header);
            $this->assertEquals(0, count($diff));
        }

        $expected = [];
        if (version_compare(phpversion('xdebug'), '2.2.0', '>=')) {
            $expected = xdebug_get_headers();
        }

        $responseSender->sendHeaders($mockSendResponseEvent);
        $this->assertEquals($expected, xdebug_get_headers());
    }

    /**
     * @runInSeparateProcess
     */
    public function testSendHeadersSendsStatusLast()
    {
        if (! function_exists('xdebug_get_headers')) {
            $this->markTestSkipped('Xdebug extension needed, skipped test');
        }

        $mockResponse = $this->createMock(Response::class);
        $mockResponse
            ->expects($this->once())
            ->method('getHeaders')
            ->will($this->returnValue(Headers::fromString('Location: example.com')));
        $mockResponse
            ->expects($this->once())
            ->method('renderStatusLine')
            ->will($this->returnValue('X-Test: HTTP/1.1 202 Accepted'));

        $mockSendResponseEvent = $this->getMockBuilder(SendResponseEvent::class)
            ->setMethods(['getResponse'])
            ->getMock();
        $mockSendResponseEvent->expects($this->any())->method('getResponse')->will($this->returnValue($mockResponse));

        $responseSender = $this->getMockForAbstractClass(AbstractResponseSender::class);
        $responseSender->sendHeaders($mockSendResponseEvent);

        $sentHeaders = xdebug_get_headers();

        $this->assertCount(2, $sentHeaders);
        $this->assertEquals('Location: example.com', $sentHeaders[0]);
        $this->assertEquals(
            'X-Test: HTTP/1.1 202 Accepted',
            $sentHeaders[1],
            'Status header is sent last to prevent header() from overwriting the ZF status code when a Location '
            . 'header is used'
        );
    }
}
