<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Controller\Plugin;

use Laminas\EventManager\StaticEventManager;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\Plugin\Forward as ForwardPlugin;
use Laminas\Mvc\MvcEvent;
use Laminas\Mvc\Router\RouteMatch;
use LaminasTest\Mvc\Controller\TestAsset\ForwardController;
use LaminasTest\Mvc\Controller\TestAsset\SampleController;
use LaminasTest\Mvc\Controller\TestAsset\UneventfulController;
use LaminasTest\Mvc\Controller\TestAsset\UnlocatableEventfulController;
use LaminasTest\Mvc\TestAsset\Locator;
use PHPUnit_Framework_TestCase as TestCase;
use stdClass;

class ForwardTest extends TestCase
{
    public function setUp()
    {
        StaticEventManager::resetInstance();

        $mockSharedEventManager = $this->getMock('Laminas\EventManager\SharedEventManagerInterface');
        $mockSharedEventManager->expects($this->any())->method('getListeners')->will($this->returnValue(array()));
        $mockEventManager = $this->getMock('Laminas\EventManager\EventManagerInterface');
        $mockEventManager->expects($this->any())->method('getSharedManager')->will($this->returnValue($mockSharedEventManager));
        $mockApplication = $this->getMock('Laminas\Mvc\ApplicationInterface');
        $mockApplication->expects($this->any())->method('getEventManager')->will($this->returnValue($mockEventManager));

        $event   = new MvcEvent();
        $event->setApplication($mockApplication);
        $event->setRequest(new Request());
        $event->setResponse(new Response());
        $event->setRouteMatch(new RouteMatch(array('action' => 'test')));

        $locator = new Locator;
        $locator->add('forward', function() {
            return new ForwardController();
        });

        $this->controller = new SampleController();
        $this->controller->setEvent($event);
        $this->controller->setServiceLocator($locator);

        $this->plugin = $this->controller->plugin('forward');
    }

    public function tearDown()
    {
        StaticEventManager::resetInstance();
    }

    public function testPluginWithoutEventAwareControllerRaisesDomainException()
    {
        $controller = new UneventfulController();
        $plugin     = new ForwardPlugin();
        $plugin->setController($controller);
        $this->setExpectedException('Laminas\Mvc\Exception\DomainException', 'InjectApplicationEventInterface');
        $plugin->dispatch('forward');
    }

    public function testPluginWithoutLocatorAwareControllerRaisesDomainException()
    {
        $controller = new UnlocatableEventfulController();
        $controller->setEvent($this->controller->getEvent());
        $plugin     = new ForwardPlugin();
        $plugin->setController($controller);
        $this->setExpectedException('Laminas\Mvc\Exception\DomainException', 'implements ServiceLocatorAwareInterface');
        $plugin->dispatch('forward');
    }

    public function testPluginWithoutControllerLocatorRaisesDomainException()
    {
        $controller = new SampleController();
        $plugin     = $controller->plugin('forward');
        $this->setExpectedException('Laminas\Mvc\Exception\DomainException', 'composes Locator');
        $plugin->dispatch('forward');
    }

    public function testDispatchRaisesDomainExceptionIfDiscoveredControllerIsNotDispatchable()
    {
        $locator = $this->controller->getServiceLocator();
        $locator->add('bogus', function() {
            return new stdClass;
        });
        $this->setExpectedException('Laminas\Mvc\Exception\DomainException', 'DispatchableInterface');
        $this->plugin->dispatch('bogus');
    }

    public function testDispatchRaisesDomainExceptionIfCircular()
    {
        $this->setExpectedException('Laminas\Mvc\Exception\DomainException', 'Circular forwarding');
        $sampleController = $this->controller;
        $sampleController->getServiceLocator()->add('sample', function() use ($sampleController) {
            return $sampleController;
        });
        $this->plugin->dispatch('sample', array('action' => 'test-circular'));
    }

    public function testPluginDispatchsRequestedControllerWhenFound()
    {
        $result = $this->plugin->dispatch('forward');
        $this->assertInternalType('array', $result);
        $this->assertEquals(array('content' => 'LaminasTest\Mvc\Controller\TestAsset\ForwardController::testAction'), $result);
    }

    public function testDispatchWillSeedRouteMatchWithPassedParameters()
    {
        $result = $this->plugin->dispatch('forward', array(
            'action' => 'test-matches',
            'param1' => 'foobar',
        ));
        $this->assertInternalType('array', $result);
        $this->assertTrue(isset($result['action']));
        $this->assertEquals('test-matches', $result['action']);
        $this->assertTrue(isset($result['param1']));
        $this->assertEquals('foobar', $result['param1']);
    }

    public function testRouteMatchObjectRemainsSameFollowingForwardDispatch()
    {
        $routeMatch  = $this->controller->getEvent()->getRouteMatch();
        $matchParams = $routeMatch->getParams();
        $result = $this->plugin->dispatch('forward', array(
            'action' => 'test-matches',
            'param1' => 'foobar',
        ));
        $test       = $this->controller->getEvent()->getRouteMatch();
        $testParams = $test->getParams();

        $this->assertSame($routeMatch, $test);
        $this->assertEquals($matchParams, $testParams);
    }

    public function testAllowsPassingEmptyArrayOfRouteParams()
    {
        $result = $this->plugin->dispatch('forward', array());
        $this->assertInternalType('array', $result);
        $this->assertTrue(isset($result['status']));
        $this->assertEquals('not-found', $result['status']);
        $this->assertTrue(isset($result['params']));
        $this->assertEquals(array(), $result['params']);
    }
}
