<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\View\Console;

use Laminas\Console\Request as ConsoleRequest;
use Laminas\Console\Response as ConsoleResponse;
use Laminas\EventManager\EventManager;
use Laminas\EventManager\SharedEventManager;
use Laminas\Mvc\Application;
use Laminas\Mvc\MvcEvent;
use Laminas\Mvc\Service\ConsoleViewManagerFactory;
use Laminas\Mvc\Service\ServiceListenerFactory;
use Laminas\Mvc\Service\ServiceManagerConfig;
use Laminas\Mvc\View\Console\ViewManager;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit_Framework_TestCase as TestCase;
use ReflectionClass;
use ReflectionProperty;

/**
 * Tests for {@see \Laminas\Mvc\View\Console\ViewManager}
 *
 * @covers \Laminas\Mvc\View\Console\ViewManager
 */
class ViewManagerTest extends TestCase
{
    /**
     * @var ServiceManager
     */
    private $services;

    /**
     * @var ServiceManagerConfig
     */
    private $config;

    /**
     * @var ConsoleViewManagerFactory
     */
    private $factory;

    public function setUp()
    {
        $this->services = new ServiceManager();
        $this->prepareServiceManagerConfig()->configureServiceManager($this->services);
        $this->factory  = new ConsoleViewManagerFactory();
    }

    /**
     * Create an event manager instance based on laminas-eventmanager version
     *
     * @return EventManager
     */
    protected function createEventManager()
    {
        $r = new ReflectionClass(EventManager::class);

        if ($r->hasMethod('setSharedManager')) {
            $events = new EventManager();
            $events->setSharedManager(new SharedEventManager());
            return $events;
        }

        return new EventManager(new SharedEventManager());
    }

    private function prepareServiceManagerConfig()
    {
        $serviceListener = new ServiceListenerFactory();
        $r = new ReflectionProperty($serviceListener, 'defaultServiceConfig');
        $r->setAccessible(true);

        $config = $r->getValue($serviceListener);
        return new ServiceManagerConfig($config);
    }

    /**
     * @return array
     */
    public function viewManagerConfiguration()
    {
        return [
            'standard' => [
                [
                    'view_manager' => [
                        'display_exceptions' => false,
                        'display_not_found_reason' => false,
                    ],
                ]
            ],
            'with-console' => [
                [
                    'view_manager' => [
                        'display_exceptions' => true,
                        'display_not_found_reason' => true
                    ],
                    'console' => [
                        'view_manager' => [
                            'display_exceptions' => false,
                            'display_not_found_reason' => false,
                        ]
                    ]
                ]
            ],
            'without-console' => [
                [
                    'view_manager' => [
                        'display_exceptions' => false,
                        'display_not_found_reason' => false
                    ],
                ]
            ],
            'console-only' => [
                [
                    'console' => [
                        'view_manager' => [
                            'display_exceptions' => false,
                            'display_not_found_reason' => false
                        ]
                    ],
                ]
            ],
        ];
    }

    /**
     * @dataProvider viewManagerConfiguration
     *
     * @param array $config
     *
     * @group 6866
     */
    public function testConsoleKeyWillOverrideDisplayExceptionAndExceptionMessage($config)
    {
        $eventManager = $this->createEventManager();
        $request      = new ConsoleRequest();
        $response     = new ConsoleResponse();

        $this->services->setAllowOverride(true);
        $this->services->setService('config', $config);
        $this->services->setService('EventManager', $eventManager);
        $this->services->setService('Request', $request);
        $this->services->setService('Response', $response);
        $this->services->setAllowOverride(false);

        $manager = $this->factory->__invoke($this->services, 'ConsoleViewRenderer');

        $application = new Application($config, $this->services, $eventManager, $request, $response);

        $event = new MvcEvent();
        $event->setApplication($application);
        $manager->onBootstrap($event);

        $this->assertFalse($this->services->get('ConsoleExceptionStrategy')->displayExceptions());
        $this->assertFalse($this->services->get('ConsoleRouteNotFoundStrategy')->displayNotFoundReason());
    }

    /**
     * @group 6866
     */
    public function testConsoleDisplayExceptionIsTrue()
    {
        $eventManager = $this->createEventManager();
        $request      = new ConsoleRequest();
        $response     = new ConsoleResponse();

        $this->services->setAllowOverride(true);
        $this->services->setService('config', []);
        $this->services->setService('EventManager', $eventManager);
        $this->services->setService('Request', $request);
        $this->services->setService('Response', $response);
        $this->services->setAllowOverride(false);

        $manager     = new ViewManager;
        $application = new Application([], $this->services, $eventManager, $request, $response);
        $event       = new MvcEvent();
        $event->setApplication($application);

        $manager->onBootstrap($event);

        $exceptionStrategy = $this->services->get('ConsoleExceptionStrategy');
        $this->assertInstanceOf('Laminas\Mvc\View\Console\ExceptionStrategy', $exceptionStrategy);
        $this->assertTrue($exceptionStrategy->displayExceptions());

        $routeNotFoundStrategy = $this->services->get('ConsoleRouteNotFoundStrategy');
        $this->assertInstanceOf('Laminas\Mvc\View\Console\RouteNotFoundStrategy', $routeNotFoundStrategy);
        $this->assertTrue($routeNotFoundStrategy->displayNotFoundReason());
    }
}
