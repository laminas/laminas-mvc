<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\ResponseSender;

use Laminas\Mvc\ResponseSender\SendResponseEvent;
use Laminas\Stdlib\ResponseInterface;
use PHPUnit\Framework\TestCase;

class SendResponseEventTest extends TestCase
{
    public function testContentSentAndHeadersSent(): void
    {
        $mockResponse  = $this->getMockForAbstractClass(ResponseInterface::class);
        $mockResponse2 = $this->getMockForAbstractClass(ResponseInterface::class);
        $event         = new SendResponseEvent();
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
