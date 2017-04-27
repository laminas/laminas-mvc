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
use Zend\Mvc\ResponseSender\SendResponseEvent;
use Zend\Stdlib\ResponseInterface;

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
