<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\Service;

// phpcs:ignore
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
use RuntimeException;

class SendResponseListenerFactoryTest extends TestCase
{
    public function testFactoryReturnsListenerWithEventManagerFromContainer()
    {
        $sharedEvents = $this->createMock(SharedEventManagerInterface::class);
        $events       = $this->createMock(EventManagerInterface::class);
        $events->method('getSharedManager')->willReturn($sharedEvents);

        $events->expects($this->once())
            ->method('setIdentifiers')
            ->with([SendResponseListener::class, SendResponseListener::class]);

        $invokedCount = $this->exactly(3);
        $events->expects($invokedCount)
            ->method('attach')
            ->willReturnCallback(function ($eventName, callable $listener, $priority = 1) use ($invokedCount) {
                if ($invokedCount->numberOfInvocations() === 1) {
                    self::assertSame($eventName, SendResponseEvent::EVENT_SEND_RESPONSE);
                    self::assertSame(-1000, $priority);
                    self::assertInstanceOf(PhpEnvironmentResponseSender::class, $listener);
                    return;
                }

                if ($invokedCount->numberOfInvocations() === 2) {
                    self::assertSame($eventName, SendResponseEvent::EVENT_SEND_RESPONSE);
                    self::assertSame(-3000, $priority);
                    self::assertInstanceOf(SimpleStreamResponseSender::class, $listener);
                    return;
                }

                if ($invokedCount->numberOfInvocations() === 3) {
                    self::assertSame($eventName, SendResponseEvent::EVENT_SEND_RESPONSE);
                    self::assertSame(-4000, $priority);
                    self::assertInstanceOf(HttpResponseSender::class, $listener);
                    return;
                }

                throw new RuntimeException('Unexpected numberOfInvocations' . $invokedCount->numberOfInvocations());
            });

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->with('EventManager')->willReturn($events);

        $factory  = new SendResponseListenerFactory();
        $listener = $factory($container);
        $this->assertInstanceOf(SendResponseListener::class, $listener);
        $this->assertSame($events, $listener->getEventManager());
    }
}
