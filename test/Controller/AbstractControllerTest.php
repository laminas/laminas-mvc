<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\Controller;

use Laminas\EventManager\EventManagerAwareInterface;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Mvc\Controller\AbstractController;
use Laminas\Mvc\InjectApplicationEventInterface;
use Laminas\Stdlib\DispatchableInterface;
use LaminasTest\Mvc\Controller\TestAsset\AbstractControllerStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/**
 * @covers \Laminas\Mvc\Controller\AbstractController
 */
class AbstractControllerTest extends TestCase
{
    /** @var AbstractController|MockObject */
    private $controller;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->controller = new AbstractControllerStub();
    }

    public function testSetEventManagerWithDefaultIdentifiers(): void
    {
        /** @var EventManagerInterface|MockObject $eventManager */
        $eventManager = $this->createMock(EventManagerInterface::class);

        $eventManager
            ->expects($this->once())
            ->method('setIdentifiers')
            ->with($this->logicalNot($this->containsEqual('customEventIdentifier')));

        $this->controller->setEventManager($eventManager);
    }

    public function testSetEventManagerWithCustomStringIdentifier(): void
    {
        /** @var EventManagerInterface|MockObject $eventManager */
        $eventManager = $this->createMock(EventManagerInterface::class);

        $eventManager->expects($this->once())->method('setIdentifiers')
            ->with($this->containsEqual('customEventIdentifier'));

        $reflection = new ReflectionProperty($this->controller, 'eventIdentifier');

        $reflection->setAccessible(true);
        $reflection->setValue($this->controller, 'customEventIdentifier');

        $this->controller->setEventManager($eventManager);
    }

    public function testSetEventManagerWithMultipleCustomStringIdentifier(): void
    {
        /** @var EventManagerInterface|MockObject $eventManager */
        $eventManager = $this->createMock(EventManagerInterface::class);

        $eventManager->expects($this->once())->method('setIdentifiers')->with($this->logicalAnd(
            $this->containsEqual('customEventIdentifier1'),
            $this->containsEqual('customEventIdentifier2')
        ));

        $reflection = new ReflectionProperty($this->controller, 'eventIdentifier');

        $reflection->setAccessible(true);
        $reflection->setValue($this->controller, ['customEventIdentifier1', 'customEventIdentifier2']);

        $this->controller->setEventManager($eventManager);
    }

    public function testSetEventManagerWithDefaultIdentifiersIncludesImplementedInterfaces(): void
    {
        /** @var EventManagerInterface|MockObject $eventManager */
        $eventManager = $this->createMock(EventManagerInterface::class);

        $eventManager
            ->expects($this->once())
            ->method('setIdentifiers')
            ->with($this->logicalAnd(
                $this->containsEqual(EventManagerAwareInterface::class),
                $this->containsEqual(DispatchableInterface::class),
                $this->containsEqual(InjectApplicationEventInterface::class)
            ));

        $this->controller->setEventManager($eventManager);
    }

    public function testSetEventManagerWithDefaultIdentifiersIncludesExtendingClassNameAndNamespace(): void
    {
        /** @var EventManagerInterface|MockObject $eventManager */
        $eventManager = $this->createMock(EventManagerInterface::class);

        $eventManager
            ->expects($this->once())
            ->method('setIdentifiers')
            ->with($this->logicalAnd(
                $this->containsEqual(AbstractController::class),
                $this->containsEqual(AbstractControllerStub::class),
                $this->containsEqual('LaminasTest'),
                $this->containsEqual('LaminasTest\\Mvc\\Controller\\TestAsset')
            ));

        $this->controller->setEventManager($eventManager);
    }
}
