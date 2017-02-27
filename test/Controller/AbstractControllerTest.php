<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Mvc\Controller;

use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerInterface;
use Zend\Mvc\Controller\AbstractController;
use Zend\Mvc\InjectApplicationEventInterface;
use Zend\Stdlib\DispatchableInterface;

/**
 * @covers \Zend\Mvc\Controller\AbstractController
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
    protected function setUp()
    {
        $this->controller = $this->getMockForAbstractClass(AbstractController::class);
    }

    /**
     * @group 6553
     */
    public function testSetEventManagerWithDefaultIdentifiers()
    {
        /* @var $eventManager EventManagerInterface|\PHPUnit_Framework_MockObject_MockObject */
        $eventManager = $this->createMock(EventManagerInterface::class);

        $eventManager
            ->expects($this->once())
            ->method('setIdentifiers')
            ->with($this->logicalNot($this->contains('customEventIdentifier')));

        $this->controller->setEventManager($eventManager);
    }

    /**
     * @group 6553
     */
    public function testSetEventManagerWithCustomStringIdentifier()
    {
        /* @var $eventManager EventManagerInterface|\PHPUnit_Framework_MockObject_MockObject */
        $eventManager = $this->createMock(EventManagerInterface::class);

        $eventManager->expects($this->once())->method('setIdentifiers')->with($this->contains('customEventIdentifier'));

        $reflection = new ReflectionProperty($this->controller, 'eventIdentifier');

        $reflection->setAccessible(true);
        $reflection->setValue($this->controller, 'customEventIdentifier');

        $this->controller->setEventManager($eventManager);
    }

    /**
     * @group 6553
     */
    public function testSetEventManagerWithMultipleCustomStringIdentifier()
    {
        /* @var $eventManager EventManagerInterface|\PHPUnit_Framework_MockObject_MockObject */
        $eventManager = $this->createMock(EventManagerInterface::class);

        $eventManager->expects($this->once())->method('setIdentifiers')->with($this->logicalAnd(
            $this->contains('customEventIdentifier1'),
            $this->contains('customEventIdentifier2')
        ));

        $reflection = new ReflectionProperty($this->controller, 'eventIdentifier');

        $reflection->setAccessible(true);
        $reflection->setValue($this->controller, ['customEventIdentifier1', 'customEventIdentifier2']);

        $this->controller->setEventManager($eventManager);
    }

    /**
     * @group 6615
     */
    public function testSetEventManagerWithDefaultIdentifiersIncludesImplementedInterfaces()
    {
        /* @var $eventManager EventManagerInterface|\PHPUnit_Framework_MockObject_MockObject */
        $eventManager = $this->createMock(EventManagerInterface::class);

        $eventManager
            ->expects($this->once())
            ->method('setIdentifiers')
            ->with($this->logicalAnd(
                $this->contains(EventManagerAwareInterface::class),
                $this->contains(DispatchableInterface::class),
                $this->contains(InjectApplicationEventInterface::class)
            ));

        $this->controller->setEventManager($eventManager);
    }
}
