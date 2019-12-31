<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Controller;

use Laminas\ServiceManager\ServiceLocatorInterface;
use PHPUnit_Framework_TestCase as TestCase;
use ReflectionProperty;

/**
 * @covers \Laminas\Mvc\Controller\AbstractController
 */
class AbstractControllerTest extends TestCase
{
    /**
     * @var \Laminas\Mvc\Controller\AbstractController|\PHPUnit_Framework_MockObject_MockObject
     */
    private $controller;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->controller = $this->getMockForAbstractClass('Laminas\\Mvc\\Controller\\AbstractController');
    }

    /**
     * @group 6553
     */
    public function testSetEventManagerWithDefaultIdentifiers()
    {
        /* @var $eventManager \Laminas\EventManager\EventManagerInterface|\PHPUnit_Framework_MockObject_MockObject */
        $eventManager = $this->getMock('Laminas\\EventManager\\EventManagerInterface');

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
        /* @var $eventManager \Laminas\EventManager\EventManagerInterface|\PHPUnit_Framework_MockObject_MockObject */
        $eventManager = $this->getMock('Laminas\\EventManager\\EventManagerInterface');

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
        /* @var $eventManager \Laminas\EventManager\EventManagerInterface|\PHPUnit_Framework_MockObject_MockObject */
        $eventManager = $this->getMock('Laminas\\EventManager\\EventManagerInterface');

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
        /* @var $eventManager \Laminas\EventManager\EventManagerInterface|\PHPUnit_Framework_MockObject_MockObject */
        $eventManager = $this->getMock('Laminas\\EventManager\\EventManagerInterface');

        $eventManager
            ->expects($this->once())
            ->method('setIdentifiers')
            ->with($this->logicalAnd(
                $this->contains('Laminas\\EventManager\\EventManagerAwareInterface'),
                $this->contains('Laminas\\Stdlib\\DispatchableInterface'),
                $this->contains('Laminas\\Mvc\\InjectApplicationEventInterface')
            ));

        $this->controller->setEventManager($eventManager);
    }

    public function testRetrievingServiceLocatorRaisesDeprecationNotice()
    {
        $services = $this->prophesize(ServiceLocatorInterface::class)->reveal();

        $controller = new TestAsset\SampleController();
        $controller->setServiceLocator($services);

        $this->setExpectedException('PHPUnit_Framework_Error_Deprecated', 'retrieving the service locator');
        $controller->getServiceLocator();
    }
}
