<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Mvc\Controller\Plugin;

use PHPUnit_Framework_TestCase as TestCase;
use ReflectionClass;
use stdClass;
use Zend\EventManager\EventManager;
use Zend\EventManager\SharedEventManager;
use Zend\Http\Request;
use Zend\Http\Response;
use Zend\Mvc\Controller\ControllerManager;
use Zend\Mvc\Controller\PluginManager;
use Zend\Mvc\Controller\Plugin\Forward as ForwardPlugin;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Router\RouteMatch;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\ServiceManager;
use ZendTest\Mvc\Controller\TestAsset\ForwardController;
use ZendTest\Mvc\Controller\TestAsset\SampleController;
use ZendTest\Mvc\Controller\TestAsset\UneventfulController;

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
     * @var \Zend\Mvc\Controller\Plugin\Forward
     */
    private $plugin;

    public function setUp()
    {
        $eventManager = $this->createEventManager(new SharedEventManager());
        $mockApplication = $this->getMock('Zend\Mvc\ApplicationInterface');
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
     * Create an event manager instance based on zend-eventmanager version
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
        $this->setExpectedException('Zend\Mvc\Exception\DomainException', 'InjectApplicationEventInterface');
        $plugin->dispatch('forward');
    }

    public function testPluginWithoutControllerLocatorRaisesServiceNotCreatedException()
    {
        $controller = new SampleController();
        $this->setExpectedException('Zend\ServiceManager\Exception\ServiceNotCreatedException');
        $plugin     = $controller->plugin('forward');
    }

    public function testDispatchRaisesDomainExceptionIfDiscoveredControllerIsNotDispatchable()
    {
        $controllers = $this->controllers->setFactory('bogus', function () {
            return new stdClass;
        });
        $plugin = new ForwardPlugin($controllers);
        $plugin->setController($this->controller);

        // Vary exception expected based on zend-servicemanager version
        $expectedException = method_exists($controllers, 'configure')
            ? 'Zend\ServiceManager\Exception\InvalidServiceException' // v3
            : 'Zend\Mvc\Exception\InvalidControllerException';        // v2

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

        $this->setExpectedException('Zend\Mvc\Exception\DomainException', 'Circular forwarding');
        $forward->dispatch('sample', ['action' => 'test-circular']);
    }

    public function testPluginDispatchsRequestedControllerWhenFound()
    {
        $result = $this->plugin->dispatch('forward');
        $this->assertInternalType('array', $result);
        $this->assertEquals(['content' => 'ZendTest\Mvc\Controller\TestAsset\ForwardController::testAction'], $result);
    }

    /**
     * @group 5432
     */
    public function testNonArrayListenerDoesNotRaiseErrorWhenPluginDispatchsRequestedController()
    {
        $services = $this->services;
        $events   = $services->get('EventManager');
        $sharedEvents = $this->getMock('Zend\EventManager\SharedEventManagerInterface');
        // @codingStandardsIgnoreStart
        $sharedEvents->expects($this->any())->method('getListeners')->will($this->returnValue([
            function ($e) {}
        ]));
        // @codingStandardsIgnoreEnd
        $events = $this->createEventManager($sharedEvents);
        $application = $this->getMock('Zend\Mvc\ApplicationInterface');
        $application->expects($this->any())->method('getEventManager')->will($this->returnValue($events));
        $event = $this->controller->getEvent();
        $event->setApplication($application);

        $result = $this->plugin->dispatch('forward');
        $this->assertInternalType('array', $result);
        $this->assertEquals(['content' => 'ZendTest\Mvc\Controller\TestAsset\ForwardController::testAction'], $result);
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
