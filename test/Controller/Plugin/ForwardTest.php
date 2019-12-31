<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Controller\Plugin;

use Laminas\EventManager\EventManager;
use Laminas\EventManager\SharedEventManager;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\ControllerManager;
use Laminas\Mvc\Controller\Plugin\Forward as ForwardPlugin;
use Laminas\Mvc\Controller\PluginManager;
use Laminas\Mvc\MvcEvent;
use Laminas\Mvc\Router\RouteMatch;
use Laminas\ServiceManager\Config;
use Laminas\ServiceManager\ServiceManager;
use LaminasTest\Mvc\Controller\TestAsset\ForwardController;
use LaminasTest\Mvc\Controller\TestAsset\SampleController;
use LaminasTest\Mvc\Controller\TestAsset\UneventfulController;
use PHPUnit_Framework_Error_Deprecated;
use PHPUnit_Framework_TestCase as TestCase;
use ReflectionClass;
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
        // Ignore deprecation errors
        PHPUnit_Framework_Error_Deprecated::$enabled = false;

        $eventManager = $this->createEventManager(new SharedEventManager());
        $mockApplication = $this->getMock('Laminas\Mvc\ApplicationInterface');
        $mockApplication->expects($this->any())->method('getEventManager')->will($this->returnValue($eventManager));

        $event   = new MvcEvent();
        $event->setApplication($mockApplication);
        $event->setRequest(new Request());
        $event->setResponse(new Response());

        $routeMatch = new RouteMatch(['action' => 'test']);
        $routeMatch->setMatchedRouteName('some-route');
        $event->setRouteMatch($routeMatch);

        $config = new Config([
            'aliases' => [
                'ControllerLoader' => 'ControllerManager',
            ],
            'factories' => [
                'ControllerManager' => function ($services, $name) {
                    $plugins = $services->get('ControllerPluginManager');

                    return new ControllerManager($services, ['factories' => [
                        'forward' => function ($services) use ($plugins) {
                            $controller = new ForwardController();
                            $controller->setPluginManager($plugins);
                            return $controller;
                        },
                    ]]);
                },
                'ControllerPluginManager' => function ($services, $name) {
                    return new PluginManager($services);
                },
                'EventManager' => function ($services, $name) {
                    return $this->createEventManager($services->get('SharedEventManager'));
                },
                'SharedEventManager' => function ($services, $name) {
                    return new SharedEventManager();
                },
            ],
            'shared' => [
                'EventManager' => false,
            ],
        ]);
        $this->services = $services = new ServiceManager();
        $config->configureServiceManager($services);

        $this->controllers = $services->get('ControllerManager');

        $plugins = $services->get('ControllerPluginManager');
        $this->controller = new SampleController();
        $this->controller->setEvent($event);
        $this->controller->setPluginManager($plugins);

        $this->plugin = $plugins->get('forward');
    }

    /**
     * Create an event manager instance based on laminas-eventmanager version
     *
     * @param SharedEventManager
     * @return EventManager
     */
    protected function createEventManager($sharedManager)
    {
        $r = new ReflectionClass(EventManager::class);

        if ($r->hasMethod('setSharedManager')) {
            $events = new EventManager();
            $events->setSharedManager($sharedManager);
            return $events;
        }

        return new EventManager($sharedManager);
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
        $this->controllers->setFactory('bogus', function () {
            return new stdClass;
        });
        $plugin = new ForwardPlugin($this->controllers);
        $plugin->setController($this->controller);

        // Vary exception expected based on laminas-servicemanager version
        $expectedException = method_exists($this->controllers, 'configure')
            ? 'Laminas\ServiceManager\Exception\InvalidServiceException' // v3
            : 'Laminas\Mvc\Exception\InvalidControllerException';        // v2

        $this->setExpectedException($expectedException, 'DispatchableInterface');
        $plugin->dispatch('bogus');
    }

    public function testDispatchRaisesDomainExceptionIfCircular()
    {
        $event = $this->controller->getEvent();

        $config = new Config([
            'aliases' => [
                'ControllerLoader' => 'ControllerManager',
            ],
            'factories' => [
                'ControllerManager' => function ($services) use ($event) {
                    $plugins = $services->get('ControllerPluginManager');

                    return new ControllerManager($services, ['factories' => [
                        'forward' => function ($services) use ($plugins) {
                            $controller = new ForwardController();
                            $controller->setPluginManager($plugins);
                            return $controller;
                        },
                        'sample' => function ($services) use ($event, $plugins) {
                            $controller = new SampleController();
                            $controller->setEvent($event);
                            $controller->setPluginManager($plugins);
                            return $controller;
                        },
                    ]]);
                },
                'ControllerPluginManager' => function ($services) {
                    return new PluginManager($services);
                },
                'EventManager' => function ($services, $name) {
                    return $this->createEventManager($services->get('SharedEventManager'));
                },
                'SharedEventManager' => function ($services, $name) {
                    return new SharedEventManager();
                },
            ],
            'shared' => [
                'EventManager' => false,
            ],
        ]);
        $services = new ServiceManager();
        $config->configureServiceManager($services);

        $controllers = $services->get('ControllerManager');

        $forward = new ForwardPlugin($controllers);
        $forward->setController($controllers->get('sample'));

        $this->setExpectedException('Laminas\Mvc\Exception\DomainException', 'Circular forwarding');
        $forward->dispatch('sample', ['action' => 'test-circular']);
    }

    public function testPluginDispatchsRequestedControllerWhenFound()
    {
        $result = $this->plugin->dispatch('forward');
        $this->assertInternalType('array', $result);
        $this->assertEquals(['content' => 'LaminasTest\Mvc\Controller\TestAsset\ForwardController::testAction'], $result);
    }

    /**
     * @group 5432
     */
    public function testNonArrayListenerDoesNotRaiseErrorWhenPluginDispatchsRequestedController()
    {
        $services = $this->services;
        $events   = $services->get('EventManager');
        $sharedEvents = $this->getMock('Laminas\EventManager\SharedEventManagerInterface');
        // @codingStandardsIgnoreStart
        $sharedEvents->expects($this->any())->method('getListeners')->will($this->returnValue([
            function ($e) {}
        ]));
        // @codingStandardsIgnoreEnd
        $events = $this->createEventManager($sharedEvents);
        $application = $this->getMock('Laminas\Mvc\ApplicationInterface');
        $application->expects($this->any())->method('getEventManager')->will($this->returnValue($events));
        $event = $this->controller->getEvent();
        $event->setApplication($application);

        $result = $this->plugin->dispatch('forward');
        $this->assertInternalType('array', $result);
        $this->assertEquals(['content' => 'LaminasTest\Mvc\Controller\TestAsset\ForwardController::testAction'], $result);
    }

    public function testDispatchWillSeedRouteMatchWithPassedParameters()
    {
        $result = $this->plugin->dispatch('forward', [
            'action' => 'test-matches',
            'param1' => 'foobar',
        ]);
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
        $result = $this->plugin->dispatch('forward', [
            'action' => 'test-matches',
            'param1' => 'foobar',
        ]);
        $testMatch            = $this->controller->getEvent()->getRouteMatch();
        $testParams           = $testMatch->getParams();
        $testMatchedRouteName = $testMatch->getMatchedRouteName();

        $this->assertSame($routeMatch, $testMatch);
        $this->assertEquals($matchParams, $testParams);
        $this->assertEquals($matchMatchedRouteName, $testMatchedRouteName);
    }

    public function testAllowsPassingEmptyArrayOfRouteParams()
    {
        $result = $this->plugin->dispatch('forward', []);
        $this->assertInternalType('array', $result);
        $this->assertTrue(isset($result['status']));
        $this->assertEquals('not-found', $result['status']);
        $this->assertTrue(isset($result['params']));
        $this->assertEquals([], $result['params']);
    }

    /**
     * @group 6398
     */
    public function testSetListenersToDetachIsFluent()
    {
        $this->assertSame($this->plugin, $this->plugin->setListenersToDetach([]));
    }
}
