<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Mvc\View\Console;

use PHPUnit_Framework_TestCase as TestCase;
use ReflectionClass;
use ReflectionProperty;
use Zend\Console\Request as ConsoleRequest;
use Zend\Console\Response as ConsoleResponse;
use Zend\EventManager\EventManager;
use Zend\EventManager\SharedEventManager;
use Zend\Mvc\Application;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Service\ConsoleViewManagerFactory;
use Zend\Mvc\Service\ServiceListenerFactory;
use Zend\Mvc\Service\ServiceManagerConfig;
use Zend\Mvc\View\Console\ViewManager;
use Zend\ServiceManager\ServiceManager;

/**
 * Tests for {@see \Zend\Mvc\View\Console\ViewManager}
 *
 * @covers \Zend\Mvc\View\Console\ViewManager
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
     * Create an event manager instance based on zend-eventmanager version
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
        $this->assertInstanceOf('Zend\Mvc\View\Console\ExceptionStrategy', $exceptionStrategy);
        $this->assertTrue($exceptionStrategy->displayExceptions());

        $routeNotFoundStrategy = $this->services->get('ConsoleRouteNotFoundStrategy');
        $this->assertInstanceOf('Zend\Mvc\View\Console\RouteNotFoundStrategy', $routeNotFoundStrategy);
        $this->assertTrue($routeNotFoundStrategy->displayNotFoundReason());
    }
}
