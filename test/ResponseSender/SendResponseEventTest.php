<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\ResponseSender;

use Laminas\Mvc\ResponseSender\SendResponseEvent;
use Laminas\Stdlib\ResponseInterface;
use PHPUnit\Framework\TestCase;

class SendResponseEventTest extends TestCase
{
    public function testContentSentAndHeadersSent()
    {
        $mockResponse  = $this->getMockForAbstractClass(ResponseInterface::class);
        $mockResponse2 = $this->getMockForAbstractClass(ResponseInterface::class);
        $event         = new SendResponseEvent();
        $event->setResponse($mockResponse);
        self::assertFalse($event->headersSent());
        self::assertFalse($event->contentSent());
        $event->setHeadersSent();
        $event->setContentSent();
        self::assertTrue($event->headersSent());
        self::assertTrue($event->contentSent());
        $event->setResponse($mockResponse2);
        self::assertFalse($event->headersSent());
        self::assertFalse($event->contentSent());
    }
}
