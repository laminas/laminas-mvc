<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\ResponseSender;

use Laminas\Mvc\ResponseSender\SendResponseEvent;
use Laminas\Stdlib\ResponseInterface;
use PHPUnit\Framework\TestCase;

class SendResponseEventTest extends TestCase
{
    public function testContentSentAndHeadersSent()
    {
        $mockResponse = $this->getMockForAbstractClass(ResponseInterface::class);
        $mockResponse2 = $this->getMockForAbstractClass(ResponseInterface::class);
        $event = new SendResponseEvent();
        $event->setResponse($mockResponse);
        $this->assertFalse($event->headersSent());
        $this->assertFalse($event->contentSent());
        $event->setHeadersSent();
        $event->setContentSent();
        $this->assertTrue($event->headersSent());
        $this->assertTrue($event->contentSent());
        $event->setResponse($mockResponse2);
        $this->assertFalse($event->headersSent());
        $this->assertFalse($event->contentSent());
    }
}
