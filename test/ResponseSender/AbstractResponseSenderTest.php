<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\ResponseSender;

use Laminas\Http\Headers;
use Laminas\Http\Response;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * @category   Laminas
 * @package    Laminas_Mvc
 * @subpackage UnitTest
 */
class AbstractResponseSenderTest extends TestCase
{
    /**
     * @runInSeparateProcess
     */
    public function testSendHeadersTwoTimesSendsOnlyOnce()
    {
        if (!function_exists('xdebug_get_headers')) {
            $this->markTestSkipped('Xdebug extension needed, skipped test');
        }
        $headers = array(
            'Content-Length: 2000',
            'Transfer-Encoding: chunked'
        );
        $response = new Response();
        $response->getHeaders()->addHeaders($headers);

        $mockSendResponseEvent = $this->getMock(
            'Laminas\Mvc\ResponseSender\SendResponseEvent',
            array('getResponse')
        );
        $mockSendResponseEvent->expects(
            $this->any())
                ->method('getResponse')
                ->will($this->returnValue($response)
        );

        $responseSender = $this->getMockForAbstractClass(
            'Laminas\Mvc\ResponseSender\AbstractResponseSender'
        );
        $responseSender->sendHeaders($mockSendResponseEvent);

        $sentHeaders = xdebug_get_headers();
        $diff = array_diff($sentHeaders, $headers);

        if (count($diff)) {
            $header = array_shift($diff);
            $this->assertContains('XDEBUG_SESSION', $header);
            $this->assertEquals(0, count($diff));
        }

        $expected = array();
        if (version_compare(phpversion('xdebug'), '2.2.0', '>='))  {
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
        if (!function_exists('xdebug_get_headers')) {
            $this->markTestSkipped('Xdebug extension needed, skipped test');
        }

        $mockResponse = $this->getMock('Laminas\Http\Response');
        $mockResponse->expects($this->once())->method('getHeaders')->will($this->returnValue(Headers::fromString('Location: example.com')));
        $mockResponse->expects($this->once())->method('renderStatusLine')->will($this->returnValue('X-Test: HTTP/1.1 202 Accepted'));

        $mockSendResponseEvent = $this->getMock('Laminas\Mvc\ResponseSender\SendResponseEvent', array('getResponse'));
        $mockSendResponseEvent->expects($this->any())->method('getResponse')->will($this->returnValue($mockResponse));

        $responseSender = $this->getMockForAbstractClass('Laminas\Mvc\ResponseSender\AbstractResponseSender');
        $responseSender->sendHeaders($mockSendResponseEvent);

        $sentHeaders = xdebug_get_headers();

        $this->assertCount(2, $sentHeaders);
        $this->assertEquals('Location: example.com', $sentHeaders[0]);
        $this->assertEquals(
            'X-Test: HTTP/1.1 202 Accepted',
            $sentHeaders[1],
            'Status header is sent last to prevent header() from overwriting the Laminas status code when a Location header is used'
        );
    }
}
