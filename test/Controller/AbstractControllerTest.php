<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

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
    /**
     * @var AbstractController|\PHPUnit_Framework_MockObject_MockObject
     */
    private $controller;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->controller = new AbstractControllerStub();
    }

    public function testSetEventManagerWithDefaultIdentifiers()
    {
        /* @var $eventManager EventManagerInterface&MockObject */
        $eventManager = $this->createMock(EventManagerInterface::class);

        $eventManager
            ->expects($this->once())
            ->method('setIdentifiers')
            ->with($this->logicalNot($this->containsIdentical('customEventIdentifier')));

        $this->controller->setEventManager($eventManager);
    }

    public function testSetEventManagerWithCustomStringIdentifier()
    {
        /* @var $eventManager EventManagerInterface&MockObject */
        $eventManager = $this->createMock(EventManagerInterface::class);

        $eventManager
            ->expects($this->once())
            ->method('setIdentifiers')
            ->with($this->containsIdentical('customEventIdentifier'));

        $reflection = new ReflectionProperty($this->controller, 'eventIdentifier');

        $reflection->setAccessible(true);
        $reflection->setValue($this->controller, 'customEventIdentifier');

        $this->controller->setEventManager($eventManager);
    }

    public function testSetEventManagerWithMultipleCustomStringIdentifier()
    {
        /* @var $eventManager EventManagerInterface&MockObject */
        $eventManager = $this->createMock(EventManagerInterface::class);

        $eventManager->expects($this->once())->method('setIdentifiers')->with($this->logicalAnd(
            $this->containsIdentical('customEventIdentifier1'),
            $this->containsIdentical('customEventIdentifier2')
        ));

        $reflection = new ReflectionProperty($this->controller, 'eventIdentifier');

        $reflection->setAccessible(true);
        $reflection->setValue($this->controller, ['customEventIdentifier1', 'customEventIdentifier2']);

        $this->controller->setEventManager($eventManager);
    }

    public function testSetEventManagerWithDefaultIdentifiersIncludesImplementedInterfaces()
    {
        /* @var $eventManager EventManagerInterface&MockObject */
        $eventManager = $this->createMock(EventManagerInterface::class);

        $eventManager
            ->expects($this->once())
            ->method('setIdentifiers')
            ->with($this->logicalAnd(
                $this->containsIdentical(EventManagerAwareInterface::class),
                $this->containsIdentical(DispatchableInterface::class),
                $this->containsIdentical(InjectApplicationEventInterface::class)
            ));

        $this->controller->setEventManager($eventManager);
    }

    public function testSetEventManagerWithDefaultIdentifiersIncludesExtendingClassNameAndNamespace()
    {
        /* @var $eventManager EventManagerInterface&MockObject */
        $eventManager = $this->createMock(EventManagerInterface::class);

        $eventManager
            ->expects($this->once())
            ->method('setIdentifiers')
            ->with($this->logicalAnd(
                $this->containsIdentical(AbstractController::class),
                $this->containsIdentical(AbstractControllerStub::class),
                $this->containsIdentical('LaminasTest'),
                $this->containsIdentical('LaminasTest\\Mvc\\Controller\\TestAsset')
            ));

        $this->controller->setEventManager($eventManager);
    }
}
