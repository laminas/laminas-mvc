<?php

declare(strict_types=1);

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
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

class SendResponseListenerFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testFactoryReturnsListenerWithEventManagerFromContainer(): void
    {
        $sharedEvents = $this->prophesize(SharedEventManagerInterface::class);
        $events       = $this->prophesize(EventManagerInterface::class);
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

        $factory  = new SendResponseListenerFactory();
        $listener = $factory($container->reveal());
        $this->assertInstanceOf(SendResponseListener::class, $listener);
        $this->assertSame($events->reveal(), $listener->getEventManager());
    }
}
