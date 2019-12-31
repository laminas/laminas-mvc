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
use Laminas\Mvc\Controller\ControllerManager;
use Laminas\Mvc\Controller\Plugin\Forward as ForwardPlugin;
use Laminas\Mvc\Controller\PluginManager;
use Laminas\Mvc\MvcEvent;
use Laminas\Mvc\Router\RouteMatch;
use Laminas\Stdlib\CallbackHandler;
use LaminasTest\Mvc\Controller\TestAsset\ForwardController;
use LaminasTest\Mvc\Controller\TestAsset\SampleController;
use LaminasTest\Mvc\Controller\TestAsset\UneventfulController;
use LaminasTest\Mvc\TestAsset\Locator;
use PHPUnit_Framework_TestCase as TestCase;
use stdClass;

class ForwardTest extends TestCase
{
    /**
     * @var PluginManager
     */
    private $plugins;

    /**
     * @var ControllerManager
     */
    private $controllers;

    /**
     * @var SampleController
     */
    private $controller;

    /**
     * @var \Laminas\Mvc\Controller\Plugin\Forward
     */
    private $plugin;

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

        $routeMatch = new RouteMatch(array('action' => 'test'));
        $routeMatch->setMatchedRouteName('some-route');
        $event->setRouteMatch($routeMatch);

        $services    = new Locator();
        $plugins     = $this->plugins = new PluginManager();
        $plugins->setServiceLocator($services);

        $controllers = $this->controllers = new ControllerManager();
        $controllers->setFactory('forward', function () use ($plugins) {
            $controller = new ForwardController();
            $controller->setPluginManager($plugins);
            return $controller;
        });
        $controllers->setServiceLocator($services);
        $controllerLoader = function () use ($controllers) {
            return $controllers;
        };
        $services->add('ControllerLoader', $controllerLoader);
        $services->add('ControllerManager', $controllerLoader);
        $services->add('ControllerPluginManager', function () use ($plugins) {
            return $plugins;
        });
        $services->add('Laminas\ServiceManager\ServiceLocatorInterface', function () use ($services) {
            return $services;
        });
        $services->add('EventManager', function () use ($mockEventManager) {
            return $mockEventManager;
        });
        $services->add('SharedEventManager', function () use ($mockSharedEventManager) {
            return $mockSharedEventManager;
        });

        $this->controller = new SampleController();
        $this->controller->setEvent($event);
        $this->controller->setServiceLocator($services);
        $this->controller->setPluginManager($plugins);

        $this->plugin = $this->controller->plugin('forward');
    }

    public function tearDown()
    {
        StaticEventManager::resetInstance();
    }

    public function testPluginWithoutEventAwareControllerRaisesDomainException()
    {
        $controller = new UneventfulController();
        $plugin     = new ForwardPlugin($this->controllers);
        $plugin->setController($controller);
        $this->setExpectedException('Laminas\Mvc\Exception\DomainException', 'InjectApplicationEventInterface');
        $plugin->dispatch('forward');
    }

    public function testPluginWithoutControllerLocatorRaisesServiceNotCreatedException()
    {
        $controller = new SampleController();
        $this->setExpectedException('Laminas\ServiceManager\Exception\ServiceNotCreatedException');
        $plugin     = $controller->plugin('forward');
    }

    public function testDispatchRaisesDomainExceptionIfDiscoveredControllerIsNotDispatchable()
    {
        $locator = $this->controller->getServiceLocator();
        $locator->add('bogus', function () {
            return new stdClass;
        });
        $this->setExpectedException('Laminas\ServiceManager\Exception\ServiceNotFoundException');
        $this->plugin->dispatch('bogus');
    }

    public function testDispatchRaisesDomainExceptionIfCircular()
    {
        $this->setExpectedException('Laminas\Mvc\Exception\DomainException', 'Circular forwarding');
        $sampleController = $this->controller;
        $this->controllers->setService('sample', $sampleController);
        $this->plugin->dispatch('sample', array('action' => 'test-circular'));
    }

    public function testPluginDispatchsRequestedControllerWhenFound()
    {
        $result = $this->plugin->dispatch('forward');
        $this->assertInternalType('array', $result);
        $this->assertEquals(array('content' => 'LaminasTest\Mvc\Controller\TestAsset\ForwardController::testAction'), $result);
    }

    /**
     * @group 5432
     */
    public function testNonArrayListenerDoesNotRaiseErrorWhenPluginDispatchsRequestedController()
    {
        $services = $this->plugins->getServiceLocator();
        $events   = $services->get('EventManager');
        $sharedEvents = $this->getMock('Laminas\EventManager\SharedEventManagerInterface');
        $sharedEvents->expects($this->any())->method('getListeners')->will($this->returnValue(array(
            new CallbackHandler(function ($e) {})
        )));
        $events = $this->getMock('Laminas\EventManager\EventManagerInterface');
        $events->expects($this->any())->method('getSharedManager')->will($this->returnValue($sharedEvents));
        $application = $this->getMock('Laminas\Mvc\ApplicationInterface');
        $application->expects($this->any())->method('getEventManager')->will($this->returnValue($events));
        $event = $this->controller->getEvent();
        $event->setApplication($application);

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
        $routeMatch            = $this->controller->getEvent()->getRouteMatch();
        $matchParams           = $routeMatch->getParams();
        $matchMatchedRouteName = $routeMatch->getMatchedRouteName();
        $result = $this->plugin->dispatch('forward', array(
            'action' => 'test-matches',
            'param1' => 'foobar',
        ));
        $testMatch            = $this->controller->getEvent()->getRouteMatch();
        $testParams           = $testMatch->getParams();
        $testMatchedRouteName = $testMatch->getMatchedRouteName();

        $this->assertSame($routeMatch, $testMatch);
        $this->assertEquals($matchParams, $testParams);
        $this->assertEquals($matchMatchedRouteName, $testMatchedRouteName);
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

    /**
     * @group 6398
     */
    public function testSetListenersToDetachIsFluent()
    {
        $this->assertSame($this->plugin, $this->plugin->setListenersToDetach(array()));
    }
}
