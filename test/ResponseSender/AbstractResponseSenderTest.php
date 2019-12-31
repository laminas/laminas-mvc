<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\ResponseSender;

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

        $mockSendResponseEvent = $this->getMock('Laminas\Mvc\ResponseSender\SendResponseEvent', array('getResponse'));
        $mockSendResponseEvent->expects($this->any())->method('getResponse')->will($this->returnValue($response));

        $responseSender = $this->getMockForAbstractClass('Laminas\Mvc\ResponseSender\AbstractResponseSender');
        $responseSender->sendHeaders($mockSendResponseEvent);

        $sentHeaders = xdebug_get_headers();
        $diff = array_diff($sentHeaders, $headers);

        if (count($diff)) {
            $header = array_shift($diff);
            $this->assertContains('XDEBUG_SESSION', $header);
            $this->assertEquals(0, count($diff));
        }

        $responseSender->sendHeaders($mockSendResponseEvent);
        $this->assertEquals(array(), xdebug_get_headers());
    }
}
