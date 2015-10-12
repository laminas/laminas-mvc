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
use Zend\Stdlib\ArrayUtils;

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
        $this->services = new ServiceManager($this->prepareServiceManagerConfig());
        $this->factory  = new ConsoleViewManagerFactory();
    }

    private function prepareServiceManagerConfig()
    {
        $serviceListener = new ServiceListenerFactory();
        $r = new ReflectionProperty($serviceListener, 'defaultServiceConfig');
        $r->setAccessible(true);

        $config = $r->getValue($serviceListener);
        return ArrayUtils::merge((new ServiceManagerConfig())->toArray(), $config);
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
        $eventManager = new EventManager(new SharedEventManager());
        $request      = new ConsoleRequest();
        $response     = new ConsoleResponse();

        $services = $this->services->withConfig(['services' => [
            'config'       => $config,
            'Request'      => $request,
            'EventManager' => $eventManager,
            'Response'     => $response,
        ]]);

        $manager = $this->factory->__invoke($services, 'ConsoleViewRenderer');

        $application = new Application($config, $services, $eventManager, $request, $response);

        $event = new MvcEvent();
        $event->setApplication($application);
        $manager->onBootstrap($event);

        $this->assertFalse($services->get('ConsoleExceptionStrategy')->displayExceptions());
        $this->assertFalse($services->get('ConsoleRouteNotFoundStrategy')->displayNotFoundReason());
    }

    /**
     * @group 6866
     */
    public function testConsoleDisplayExceptionIsTrue()
    {
        $eventManager = new EventManager(new SharedEventManager());
        $request      = new ConsoleRequest();
        $response     = new ConsoleResponse();

        $services = $this->services->withConfig([
            'services' => [
                'config'       => [],
                'Request'      => $request,
                'EventManager' => $eventManager,
                'Response'     => $response,
            ],
        ]);

        $manager     = new ViewManager;
        $application = new Application([], $services, $eventManager, $request, $response);
        $event       = new MvcEvent();
        $event->setApplication($application);

        $manager->onBootstrap($event);

        $exceptionStrategy = $services->get('ConsoleExceptionStrategy');
        $this->assertInstanceOf('Zend\Mvc\View\Console\ExceptionStrategy', $exceptionStrategy);
        $this->assertTrue($exceptionStrategy->displayExceptions());

        $routeNotFoundStrategy = $services->get('ConsoleRouteNotFoundStrategy');
        $this->assertInstanceOf('Zend\Mvc\View\Console\RouteNotFoundStrategy', $routeNotFoundStrategy);
        $this->assertTrue($routeNotFoundStrategy->displayNotFoundReason());
    }
}
