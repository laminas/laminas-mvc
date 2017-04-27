<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Mvc\Service;

use Interop\Container\ContainerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\Mvc\ResponseSender\HttpResponseSender;
use Zend\Mvc\ResponseSender\PhpEnvironmentResponseSender;
use Zend\Mvc\ResponseSender\SendResponseEvent;
use Zend\Mvc\ResponseSender\SimpleStreamResponseSender;
use Zend\Mvc\SendResponseListener;
use Zend\Mvc\Service\SendResponseListenerFactory;

class SendResponseListenerFactoryTest extends TestCase
{
    public function testFactoryReturnsListenerWithEventManagerFromContainer()
    {
        $sharedEvents = $this->prophesize(SharedEventManagerInterface::class);
        $events = $this->prophesize(EventManagerInterface::class);
        $events->getSharedManager()->will([$sharedEvents, 'reveal']);

        $events->setIdentifiers([SendResponseListener::class, SendResponseListener::class])->shouldBeCalled();
        $events->attach(
            SendResponseEvent::EVENT_SEND_RESPONSE,
            Argument::type(PhpEnvironmentResponseSender::class),
            -1000
        )->shouldBeCalled();
        $events->attach(
            SendResponseEvent::EVENT_SEND_RESPONSE,
            Argument::type(SimpleStreamResponseSender::class),
            -3000
        )->shouldBeCalled();
        $events->attach(
            SendResponseEvent::EVENT_SEND_RESPONSE,
            Argument::type(HttpResponseSender::class),
            -4000
        )->shouldBeCalled();

        $container = $this->prophesize(ContainerInterface::class);
        $container->get('EventManager')->will([$events, 'reveal']);

        $factory = new SendResponseListenerFactory();
        $listener = $factory($container->reveal());
        $this->assertInstanceOf(SendResponseListener::class, $listener);
        $this->assertSame($events->reveal(), $listener->getEventManager());
    }
}
