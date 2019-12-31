<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Service;

use Interop\Container\ContainerInterface;
use Laminas\EventManager\EventManagerInterface;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\Mvc\ResponseSender\HttpResponseSender;
use Laminas\Mvc\ResponseSender\PhpEnvironmentResponseSender;
use Laminas\Mvc\ResponseSender\SendResponseEvent;
use Laminas\Mvc\ResponseSender\SimpleStreamResponseSender;
use Laminas\Mvc\SendResponseListener;
use Laminas\Mvc\Service\SendResponseListenerFactory;
use PHPUnit_Framework_TestCase as TestCase;
use Prophecy\Argument;

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
