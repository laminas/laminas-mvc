<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc;

use Laminas\Mvc\MvcEvent;
use Laminas\Mvc\ResponseSender\SendResponseEvent;
use Laminas\Mvc\SendResponseListener;
use Laminas\Stdlib\ResponseInterface;
use PHPUnit\Framework\TestCase;

class SendResponseListenerTest extends TestCase
{
    public function testEventManagerIdentifiers()
    {
        $listener = new SendResponseListener();
        $identifiers = $listener->getEventManager()->getIdentifiers();
        $expected    = [SendResponseListener::class];
        $this->assertEquals($expected, array_values($identifiers));
    }

    public function testSendResponseTriggersSendResponseEvent()
    {
        $listener = new SendResponseListener();
        $result = [];
        $listener->getEventManager()->attach(SendResponseEvent::EVENT_SEND_RESPONSE, function ($e) use (&$result) {
            $result['target'] = $e->getTarget();
            $result['response'] = $e->getResponse();
        }, 10000);
        $mockResponse = $this->getMockForAbstractClass(ResponseInterface::class);
        $mockMvcEvent = $this->getMockBuilder(MvcEvent::class)
            ->setMethods(['getResponse'])
            ->getMock();
        $mockMvcEvent->expects($this->any())->method('getResponse')->will($this->returnValue($mockResponse));
        $listener->sendResponse($mockMvcEvent);
        $expected = [
            'target' => $listener,
            'response' => $mockResponse
        ];
        $this->assertEquals($expected, $result);
    }
}
