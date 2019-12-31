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
use Laminas\Mvc\Service\ServiceManagerConfig;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit_Framework_TestCase as TestCase;

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
        $this->config = new ServiceManagerConfig();
        $this->services = new ServiceManager();
        $this->factory = new ConsoleViewManagerFactory();
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
    public function testConsoleKeyWillOverrideDisplayExceptionAndDisplayNotFoundReason($config)
    {
        $eventManager = new EventManager();
        $eventManager->setSharedManager(new SharedEventManager());

        $this->services->setService('Config', $config);
        $this->services->setService('Request', new ConsoleRequest());
        $this->services->setService('EventManager', $eventManager);
        $this->services->setService('Response', new ConsoleResponse());

        $manager = $this->factory->createService($this->services);

        $application = new Application($config, $this->services);

        $event = new MvcEvent();
        $event->setApplication($application);
        $manager->onBootstrap($event);

        $this->assertFalse($manager->getExceptionStrategy()->displayExceptions());
        $this->assertFalse($manager->getRouteNotFoundStrategy()->displayNotFoundReason());
    }

    /**
     * @group 6866
     */
    public function testConsoleDisplayExceptionIsTrue()
    {
        $eventManager = new EventManager();
        $eventManager->setSharedManager(new SharedEventManager());

        $this->services->setService('Config', []);
        $this->services->setService('Request', new ConsoleRequest());
        $this->services->setService('EventManager', $eventManager);
        $this->services->setService('Response', new ConsoleResponse());

        $manager = $this->factory->createService($this->services);

        $application = new Application([], $this->services);

        $event = new MvcEvent();
        $event->setApplication($application);
        $manager->onBootstrap($event);

        $this->assertTrue($manager->getExceptionStrategy()->displayExceptions());
        $this->assertTrue($manager->getRouteNotFoundStrategy()->displayNotFoundReason());
    }
}
