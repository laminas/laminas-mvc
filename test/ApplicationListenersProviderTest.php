<?php

declare(strict_types=1);

namespace LaminasTest\Mvc;

use Laminas\EventManager\EventManagerInterface;
use Laminas\EventManager\ListenerAggregateInterface;
use Laminas\Mvc\Application;
use Laminas\Mvc\ApplicationListenersProvider;
use Laminas\Mvc\Exception\DomainException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use stdClass;

use function array_unique;
use function get_debug_type;
use function sprintf;

use const SORT_REGULAR;

/**
 * @covers \Laminas\Mvc\ApplicationListenersProvider
 */
class ApplicationListenersProviderTest extends TestCase
{
    public function testWithDefaultListenersAddsDefaults(): void
    {
        $container        = self::createStub(ContainerInterface::class);
        $listenerProvider = ApplicationListenersProvider::withDefaultListeners($container, []);

        $listeners = $listenerProvider->getListeners();
        foreach (ApplicationListenersProvider::DEFAULT_LISTENERS as $listener) {
            self::assertContains($listener, $listeners);
        }
    }

    public function testWithDefaultListenersAndExtraAddsDefaults(): void
    {
        $container = self::createStub(ContainerInterface::class);

        $listenerProvider = ApplicationListenersProvider::withDefaultListeners($container, [
            'ExtraListener',
        ]);

        $listeners = $listenerProvider->getListeners();
        foreach (ApplicationListenersProvider::DEFAULT_LISTENERS as $listener) {
            self::assertContains($listener, $listeners);
        }

        self::assertContains('ExtraListener', $listeners);
    }

    public function testWithoutDefaultListenersDoesNotHaveDefaults(): void
    {
        $container        = self::createStub(ContainerInterface::class);
        $listenerProvider = ApplicationListenersProvider::withoutDefaultListeners($container, []);

        $listeners = $listenerProvider->getListeners();
        foreach (ApplicationListenersProvider::DEFAULT_LISTENERS as $listener) {
            self::assertNotContains($listener, $listeners);
        }
    }

    public function testWithoutDefaultListenersAndWithExtraDoesNotHaveDefaults(): void
    {
        $container = self::createStub(ContainerInterface::class);

        $listenerProvider = ApplicationListenersProvider::withoutDefaultListeners($container, [
            'ExtraListener',
        ]);

        $listeners = $listenerProvider->getListeners();
        foreach (ApplicationListenersProvider::DEFAULT_LISTENERS as $listener) {
            self::assertNotContains($listener, $listeners);
        }

        self::assertContains('ExtraListener', $listeners);
    }

    public function testRemovesDuplicatesWithDefaults(): void
    {
        $container         = self::createStub(ContainerInterface::class);
        $listenerAggregate = self::createStub(ListenerAggregateInterface::class);

        $listenerProvider = ApplicationListenersProvider::withDefaultListeners($container, [
            ApplicationListenersProvider::DEFAULT_LISTENERS[0],
            'ExtraListener',
            $listenerAggregate,
            'ExtraListener',
            $listenerAggregate,
        ]);

        $listeners = $listenerProvider->getListeners();
        self::assertSame(array_unique($listeners, SORT_REGULAR), $listeners);
    }

    public function testRemovesDuplicatesWithoutDefaults(): void
    {
        $container         = self::createStub(ContainerInterface::class);
        $listenerAggregate = self::createStub(ListenerAggregateInterface::class);

        $listenerProvider = ApplicationListenersProvider::withoutDefaultListeners($container, [
            'ExtraListener',
            $listenerAggregate,
            'ExtraListener',
            $listenerAggregate,
        ]);

        $listeners = $listenerProvider->getListeners();
        self::assertSame(array_unique($listeners, SORT_REGULAR), $listeners);
    }

    public function testDoesNotPullFromContainerUntilAttaching(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::never())
            ->method('get');

        $listenerProvider = ApplicationListenersProvider::withDefaultListeners($container, [
            'ExtraListener',
        ]);
        self::assertNotEmpty($listenerProvider->getListeners());

        $listenerProvider = ApplicationListenersProvider::withoutDefaultListeners($container, [
            'ExtraListener',
        ]);
        self::assertNotEmpty($listenerProvider->getListeners());
    }

    public function testRegistersListeners(): void
    {
        $container = self::createStub(ContainerInterface::class);

        $application = self::createStub(Application::class);
        $events      = self::createStub(EventManagerInterface::class);
        $application->method('getEventManager')
            ->willReturn($events);

        $listenerMap = [];
        foreach (ApplicationListenersProvider::DEFAULT_LISTENERS as $listener) {
            $listenerMock = $this->createMock(ListenerAggregateInterface::class);
            $listenerMock->expects(self::once())
                ->method('attach')
                ->with($events);
            $listenerMap[] = [$listener, $listenerMock];
        }
        $listenerMock = $this->createMock(ListenerAggregateInterface::class);
        $listenerMock->expects(self::once())
            ->method('attach')
            ->with($events);
        $listenerMap[] = ['ExtraListener', $listenerMock];
        $container->method('get')
            ->willReturnMap($listenerMap);

        $listenerInstanceMock = $this->createMock(ListenerAggregateInterface::class);
        $listenerInstanceMock->expects(self::once())
            ->method('attach')
            ->with($events);

        $listenerProvider = ApplicationListenersProvider::withDefaultListeners($container, [
            'ExtraListener',
            $listenerInstanceMock,
        ]);

        $listenerProvider->registerListeners($application);
    }

    /**
     * @return array<string,array{0: mixed}>
     */
    public static function invalidListenersProvider(): array
    {
        return [
            'closure' => [fn () => null],
            'object'  => [new stdClass()],
            'int'     => [1],
        ];
    }

    /**
     * @dataProvider invalidListenersProvider
     */
    public function testRejectsInvalidListeners(mixed $listener): void
    {
        $container = self::createStub(ContainerInterface::class);

        $application = self::createStub(Application::class);
        $events      = self::createStub(EventManagerInterface::class);
        $application->method('getEventManager')
            ->willReturn($events);

        /**
         * @psalm-suppress MixedArgumentTypeCoercion intentional check of invalid inputs
         */
        $listenerProvider = ApplicationListenersProvider::withoutDefaultListeners($container, [
            $listener,
        ]);

        self::expectException(DomainException::class);
        self::expectExceptionMessage(sprintf(
            'Application listener expected to be instance of %s, %s given',
            ListenerAggregateInterface::class,
            get_debug_type($listener),
        ));
        $listenerProvider->registerListeners($application);
    }

    /**
     * @dataProvider invalidListenersProvider
     */
    public function testRejectsInvalidListenersFromContainer(mixed $listener): void
    {
        $container = self::createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([['InvalidListener', $listener]]);

        $application = self::createStub(Application::class);
        $events      = self::createStub(EventManagerInterface::class);
        $application->method('getEventManager')
            ->willReturn($events);

        $listenerProvider = ApplicationListenersProvider::withoutDefaultListeners($container, ['InvalidListener']);

        self::expectException(DomainException::class);
        self::expectExceptionMessage(sprintf(
            'Application listener with container id "%s" expected to be instance of %s, %s given',
            'InvalidListener',
            ListenerAggregateInterface::class,
            get_debug_type($listener),
        ));
        $listenerProvider->registerListeners($application);
    }
}
