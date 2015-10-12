<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Mvc;

use PHPUnit_Framework_TestCase as TestCase;
use ReflectionMethod;
use ReflectionProperty;
use stdClass;
use Zend\Http\PhpEnvironment\Response;
use Zend\ModuleManager\Listener\ConfigListener;
use Zend\ModuleManager\ModuleEvent;
use Zend\Mvc\Application;
use Zend\Mvc\Controller\ControllerManager;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Router;
use Zend\Mvc\Service\ServiceManagerConfig;
use Zend\Mvc\Service\ServiceListenerFactory;
use Zend\ServiceManager\ServiceManager;
use Zend\Stdlib\ArrayUtils;
use Zend\Stdlib\ResponseInterface;

class ApplicationTest extends TestCase
{
    use EventManagerIntrospectionTrait;

    /**
     * @var ServiceManager
     */
    protected $serviceManager;

    /**
     * @var Application
     */
    protected $application;

    public function setUp()
    {
        $serviceListener = new ServiceListenerFactory();
        $r = new ReflectionProperty($serviceListener, 'defaultServiceConfig');
        $r->setAccessible(true);
        $serviceConfig = $r->getValue($serviceListener);

        $serviceConfig = ArrayUtils::merge(
            $serviceConfig,
            [
                'invokables' => [
                    'Request'              => 'Zend\Http\PhpEnvironment\Request',
                    'Response'             => 'Zend\Http\PhpEnvironment\Response',
                    'ViewManager'          => 'ZendTest\Mvc\TestAsset\MockViewManager',
                    'SendResponseListener' => 'ZendTest\Mvc\TestAsset\MockSendResponseListener',
                    'BootstrapListener'    => 'ZendTest\Mvc\TestAsset\StubBootstrapListener',
                ],
                'aliases' => [
                    'Router'                 => 'HttpRouter',
                ],
                'services' => [
                    'config' => [],
                    'ApplicationConfig' => [
                        'modules' => [],
                        'module_listener_options' => [
                            'config_cache_enabled' => false,
                            'cache_dir'            => 'data/cache',
                            'module_paths'         => [],
                        ],
                    ],
                ],
            ]
        );
        $this->serviceManager = new ServiceManager((new ServiceManagerConfig($serviceConfig))->toArray());
        $this->application = $this->serviceManager->get('Application');
    }

    /**
     * Re-inject the application service manager instance
     *
     * @param Application $application
     * @param ServiceManager $services
     * @return Application
     */
    public function setApplicationServiceManager(Application $application, ServiceManager $services)
    {
        $r = new ReflectionProperty($application, 'serviceManager');
        $r->setAccessible(true);
        $r->setValue($application, $services);
        return $application;
    }

    public function getConfigListener()
    {
        $manager   = $this->serviceManager->get('ModuleManager');
        $listeners = $this->getArrayOfListenersForEvent(ModuleEvent::EVENT_LOAD_MODULE, $manager->getEventManager());
        return array_reduce($listeners, function ($found, $listener) {
            if ($found || ! is_array($listener)) {
                return $found;
            }
            $listener = array_shift($listener);
            if ($listener instanceof ConfigListener) {
                return $listener;
            }
        });
    }

    public function testRequestIsPopulatedFromServiceManager()
    {
        $request = $this->serviceManager->get('Request');
        $this->assertSame($request, $this->application->getRequest());
    }

    public function testResponseIsPopulatedFromServiceManager()
    {
        $response = $this->serviceManager->get('Response');
        $this->assertSame($response, $this->application->getResponse());
    }

    public function testEventManagerIsPopulated()
    {
        $events       = $this->serviceManager->get('EventManager');
        $sharedEvents = $events->getSharedManager();
        $appEvents    = $this->application->getEventManager();
        $this->assertInstanceOf('Zend\EventManager\EventManager', $appEvents);
        $this->assertNotSame($events, $appEvents);
        $this->assertSame($sharedEvents, $appEvents->getSharedManager());
    }

    public function testEventManagerListensOnApplicationContext()
    {
        $events      = $this->application->getEventManager();
        $identifiers = $events->getIdentifiers();
        $expected    = ['Zend\Mvc\Application'];
        $this->assertEquals($expected, array_values($identifiers));
    }

    public function testServiceManagerIsPopulated()
    {
        $this->assertSame($this->serviceManager, $this->application->getServiceManager());
    }

    public function testConfigIsPopulated()
    {
        $smConfig  = $this->serviceManager->get('config');
        $appConfig = $this->application->getConfig();
        $this->assertEquals(
            $smConfig,
            $appConfig,
            sprintf('SM config: %s; App config: %s', var_export($smConfig, 1), var_export($appConfig, 1))
        );
    }

    public function testEventsAreEmptyAtFirst()
    {
        $events = $this->application->getEventManager();
        $registeredEvents = $this->getEventsFromEventManager($events);
        $this->assertEquals([], $registeredEvents);

        $sharedEvents = $events->getSharedManager();
        $this->assertAttributeEquals([], 'identifiers', $sharedEvents);
    }

    /**
     * @param string $listenerServiceName
     * @param string $event
     * @param string $method
     *
     * @dataProvider bootstrapRegistersListenersProvider
     */
    public function testBootstrapRegistersListeners($listenerServiceName, $event, $method, $isCustom = false)
    {
        $listenerService = $this->serviceManager->get($listenerServiceName);
        $this->application->bootstrap($isCustom ? (array) $listenerServiceName : []);
        $events = $this->application->getEventManager();

        $foundListener = false;
        $listeners = $this->getArrayOfListenersForEvent($event, $events);
        $this->assertContains([$listenerService, $method], $listeners);
    }

    public function bootstrapRegistersListenersProvider()
    {
        // @codingStandardsIgnoreStart
        //                     [ Service Name,           Event,                       Method,        isCustom ]
        return [
            'route'         => ['RouteListener'        , MvcEvent::EVENT_ROUTE     , 'onRoute',      false],
            'dispatch'      => ['DispatchListener'     , MvcEvent::EVENT_DISPATCH  , 'onDispatch',   false],
            'middleware'    => ['MiddlewareListener'   , MvcEvent::EVENT_DISPATCH  , 'onDispatch',   false],
            'send_response' => ['SendResponseListener' , MvcEvent::EVENT_FINISH    , 'sendResponse', false],
            'view_manager'  => ['ViewManager'          , MvcEvent::EVENT_BOOTSTRAP , 'onBootstrap',  false],
            'http_method'   => ['HttpMethodListener'   , MvcEvent::EVENT_ROUTE     , 'onRoute',      false],
            'bootstrap'     => ['BootstrapListener'    , MvcEvent::EVENT_BOOTSTRAP , 'onBootstrap',  true ],
        ];
        // @codingStandardsIgnoreEnd
    }

    public function testBootstrapAlwaysRegistersDefaultListeners()
    {
        $r = new ReflectionProperty($this->application, 'defaultListeners');
        $r->setAccessible(true);
        $defaultListenersNames = $r->getValue($this->application);
        $defaultListeners = [];
        foreach ($defaultListenersNames as $defaultListenerName) {
            $defaultListeners[] = $this->serviceManager->get($defaultListenerName);
        }

        $this->application->bootstrap(['BootstrapListener']);
        $eventManager = $this->application->getEventManager();

        $registeredListeners = [];
        foreach ($this->getEventsFromEventManager($eventManager) as $event) {
            foreach ($this->getListenersForEvent($event, $eventManager) as $listener) {
                if (is_array($listener)) {
                    $listener = array_shift($listener);
                }
                $registeredListeners[] = $listener;
            }
        }

        foreach ($defaultListeners as $defaultListener) {
            $this->assertContains($defaultListener, $registeredListeners);
        }
    }

    public function testBootstrapRegistersConfiguredMvcEvent()
    {
        $this->assertNull($this->application->getMvcEvent());
        $this->application->bootstrap();
        $event = $this->application->getMvcEvent();
        $this->assertInstanceOf('Zend\Mvc\MvcEvent', $event);

        $request  = $this->application->getRequest();
        $response = $this->application->getResponse();
        $router   = $this->serviceManager->get('HttpRouter');

        $this->assertFalse($event->isError());
        $this->assertSame($request, $event->getRequest());
        $this->assertSame($response, $event->getResponse());
        $this->assertSame($router, $event->getRouter());
        $this->assertSame($this->application, $event->getApplication());
        $this->assertSame($this->application, $event->getTarget());
    }

    public function setupPathController($addService = true)
    {
        $request = $this->serviceManager->get('Request');
        $request->setUri('http://example.local/path');

        $router = $this->serviceManager->get('HttpRouter');
        $route  = Router\Http\Literal::factory([
            'route'    => '/path',
            'defaults' => [
                'controller' => 'path',
            ],
        ]);
        $router->addRoute('path', $route);
        $services = $this->serviceManager->withConfig([
            'aliases' => [
                'Router'     => 'HttpRouter',
            ],
            'services' => [
                'HttpRouter' => $router,
            ],
        ]);

        $application = $this->setApplicationServiceManager($this->application, $services);
        if ($addService) {
            $services = $services->withConfig(['factories' => [
                'ControllerManager' => function ($services) {
                    return new ControllerManager($services, ['factories' => [
                        'path' => function () {
                            return new TestAsset\PathController;
                        },
                    ]]);
                },
            ]]);
            $application = $this->setApplicationServiceManager($application, $services);
        }

        $application->bootstrap();
        return $application;
    }

    public function setupActionController()
    {
        $request = $this->serviceManager->get('Request');
        $request->setUri('http://example.local/sample');

        $router = $this->serviceManager->get('HttpRouter');
        $route  = Router\Http\Literal::factory([
            'route'    => '/sample',
            'defaults' => [
                'controller' => 'sample',
                'action'     => 'test',
            ],
        ]);
        $router->addRoute('sample', $route);

        $services = $this->serviceManager->withConfig(['factories' => [
            'ControllerManager' => function ($services) {
                return new ControllerManager($services, ['factories' => [
                    'sample' => function () {
                        return new Controller\TestAsset\SampleController();
                    },
                ]]);
            },
        ]]);
        $application = $this->setApplicationServiceManager($this->application, $services);

        $application->bootstrap();
        return $application;
    }

    public function setupBadController($addService = true)
    {
        $request = $this->serviceManager->get('Request');
        $request->setUri('http://example.local/bad');

        $router = $this->serviceManager->get('HttpRouter');
        $route  = Router\Http\Literal::factory([
            'route'    => '/bad',
            'defaults' => [
                'controller' => 'bad',
                'action'     => 'test',
            ],
        ]);
        $router->addRoute('bad', $route);

        $application = $this->application;
        if ($addService) {
            $services = $this->serviceManager->withConfig(['factories' => [
                'ControllerManager' => function ($services) {
                    return new ControllerManager($services, ['factories' => [
                        'bad' => function () {
                            return new Controller\TestAsset\BadController();
                        },
                    ]]);
                },
            ]]);
            $application = $this->setApplicationServiceManager($application, $services);
        }

        $application->bootstrap();
        return $application;
    }

    public function testFinishEventIsTriggeredAfterDispatching()
    {
        $application = $this->setupActionController();
        $application->getEventManager()->attach(MvcEvent::EVENT_FINISH, function ($e) {
            return $e->getResponse()->setContent($e->getResponse()->getBody() . 'foobar');
        });
        $application->run();
        $this->assertContains(
            'foobar',
            $this->application->getResponse()->getBody(),
            'The "finish" event was not triggered ("foobar" not in response)'
        );
    }

    /**
     * @group error-handling
     */
    public function testRoutingFailureShouldTriggerDispatchError()
    {
        $application = $this->setupBadController();
        $router      = new Router\SimpleRouteStack();
        $event       = $application->getMvcEvent();
        $event->setRouter($router);

        $response = $application->getResponse();
        $events   = $application->getEventManager();
        $events->attach(MvcEvent::EVENT_DISPATCH_ERROR, function ($e) use ($response) {
            $error      = $e->getError();
            $response->setContent("Code: " . $error);
            return $response;
        });

        $application->run();
        $this->assertTrue($event->isError());
        $this->assertContains(Application::ERROR_ROUTER_NO_MATCH, $response->getContent());
    }

    /**
     * @group error-handling
     */
    public function testLocatorExceptionShouldTriggerDispatchError()
    {
        $application = $this->setupPathController(false);
        $controllerLoader = $application->getServiceManager()->get('ControllerManager');
        $response = new Response();
        $application->getEventManager()->attach(MvcEvent::EVENT_DISPATCH_ERROR, function ($e) use ($response) {
            return $response;
        });

        $result = $application->run();
        $this->assertSame($application, $result, get_class($result));
        $this->assertSame($response, $result->getResponse(), get_class($result));
    }

    /**
     * @group error-handling
     */
    public function testFailureForRouteToReturnRouteMatchShouldPopulateEventError()
    {
        $application = $this->setupBadController();
        $router      = new Router\SimpleRouteStack();
        $event       = $application->getMvcEvent();
        $event->setRouter($router);

        $response = $application->getResponse();
        $events   = $application->getEventManager();
        $events->attach(MvcEvent::EVENT_DISPATCH_ERROR, function ($e) use ($response) {
            $error      = $e->getError();
            $response->setContent("Code: " . $error);
            return $response;
        });

        $application->run();
        $this->assertTrue($event->isError());
        $this->assertEquals(Application::ERROR_ROUTER_NO_MATCH, $event->getError());
    }

    /**
     * @group ZF2-171
     */
    public function testFinishShouldRunEvenIfRouteEventReturnsResponse()
    {
        $this->application->bootstrap();
        $response = $this->application->getResponse();
        $events   = $this->application->getEventManager();
        $events->attach(MvcEvent::EVENT_ROUTE, function ($e) use ($response) {
            return $response;
        }, 100);

        $token = new stdClass;
        $events->attach(MvcEvent::EVENT_FINISH, function ($e) use ($token) {
            $token->foo = 'bar';
        });

        $this->application->run();
        $this->assertTrue(isset($token->foo));
        $this->assertEquals('bar', $token->foo);
    }

    /**
     * @group ZF2-171
     */
    public function testFinishShouldRunEvenIfDispatchEventReturnsResponse()
    {
        $this->application->bootstrap();
        $response = $this->application->getResponse();
        $events   = $this->application->getEventManager();
        $events->clearListeners(MvcEvent::EVENT_ROUTE);
        $events->attach(MvcEvent::EVENT_DISPATCH, function ($e) use ($response) {
            return $response;
        }, 100);

        $token = new stdClass;
        $events->attach(MvcEvent::EVENT_FINISH, function ($e) use ($token) {
            $token->foo = 'bar';
        });

        $this->application->run();
        $this->assertTrue(isset($token->foo));
        $this->assertEquals('bar', $token->foo);
    }

    public function testApplicationShouldBeEventTargetAtFinishEvent()
    {
        $application = $this->setupActionController();

        $events   = $application->getEventManager();
        $response = $application->getResponse();
        $events->attach(MvcEvent::EVENT_FINISH, function ($e) use ($response) {
            $response->setContent("EventClass: " . get_class($e->getTarget()));
            return $response;
        });

        $application->run();
        $this->assertContains('Zend\Mvc\Application', $response->getContent());
    }

    public function testOnDispatchErrorEventPassedToTriggersShouldBeTheOriginalOne()
    {
        $application = $this->setupPathController(false);
        $controllerManager = $application->getServiceManager()->get('ControllerManager');
        $model = $this->getMock('Zend\View\Model\ViewModel');
        $application->getEventManager()->attach(MvcEvent::EVENT_DISPATCH_ERROR, function ($e) use ($model) {
            $e->setResult($model);
        });

        $application->run();
        $event = $application->getMvcEvent();
        $this->assertInstanceOf('Zend\View\Model\ViewModel', $event->getResult());
    }

    /**
     * @group 2981
     */
    public function testReturnsResponseFromListenerWhenRouteEventShortCircuits()
    {
        $this->application->bootstrap();
        $testResponse = new Response();
        $response = $this->application->getResponse();
        $events   = $this->application->getEventManager();
        $events->clearListeners(MvcEvent::EVENT_DISPATCH);
        $events->attach(MvcEvent::EVENT_ROUTE, function ($e) use ($testResponse) {
            $testResponse->setContent('triggered');
            return $testResponse;
        }, 100);

        $triggered = false;
        $events->attach(MvcEvent::EVENT_FINISH, function ($e) use ($testResponse, &$triggered) {
            $this->assertSame($testResponse, $e->getResponse());
            $triggered = true;
        });

        $this->application->run();
        $this->assertTrue($triggered);
    }

    /**
     * @group 2981
     */
    public function testReturnsResponseFromListenerWhenDispatchEventShortCircuits()
    {
        $this->application->bootstrap();
        $testResponse = new Response();
        $response = $this->application->getResponse();
        $events   = $this->application->getEventManager();
        $events->clearListeners(MvcEvent::EVENT_ROUTE);
        $events->attach(MvcEvent::EVENT_DISPATCH, function ($e) use ($testResponse) {
            $testResponse->setContent('triggered');
            return $testResponse;
        }, 100);

        $triggered = false;
        $events->attach(MvcEvent::EVENT_FINISH, function ($e) use ($testResponse, &$triggered) {
            $this->assertSame($testResponse, $e->getResponse());
            $triggered = true;
        });

        $this->application->run();
        $this->assertTrue($triggered);
    }

    public function testCompleteRequestShouldReturnApplicationInstance()
    {
        $r = new ReflectionMethod($this->application, 'completeRequest');
        $r->setAccessible(true);

        $this->application->bootstrap();
        $event  = $this->application->getMvcEvent();
        $result = $r->invoke($this->application, $event);
        $this->assertSame($this->application, $result);
    }

    public function testFailedRoutingShouldBePreventable()
    {
        $this->application->bootstrap();

        $response     = $this->getMock(ResponseInterface::class);
        $finishMock   = $this->getMock('stdClass', ['__invoke']);
        $routeMock    = $this->getMock('stdClass', ['__invoke']);
        $dispatchMock = $this->getMock('stdClass', ['__invoke']);

        $routeMock->expects($this->once())->method('__invoke')->will($this->returnCallback(function (MvcEvent $event) {
            $event->stopPropagation(true);
            $event->setRouteMatch(new Router\RouteMatch([]));
        }));
        $dispatchMock->expects($this->once())->method('__invoke')->will($this->returnValue($response));
        $finishMock->expects($this->once())->method('__invoke')->will($this->returnCallback(function (MvcEvent $event) {
            $event->stopPropagation(true);
        }));

        $this->application->getEventManager()->attach(MvcEvent::EVENT_ROUTE, $routeMock, 100);
        $this->application->getEventManager()->attach(MvcEvent::EVENT_DISPATCH, $dispatchMock, 100);
        $this->application->getEventManager()->attach(MvcEvent::EVENT_FINISH, $finishMock, 100);

        $this->application->run();
        $this->assertSame($response, $this->application->getMvcEvent()->getResponse());
    }

    public function testCanRecoverFromApplicationError()
    {
        $this->application->bootstrap();

        $response     = $this->getMock(ResponseInterface::class);
        $errorMock    = $this->getMock('stdClass', ['__invoke']);
        $finishMock   = $this->getMock('stdClass', ['__invoke']);
        $routeMock    = $this->getMock('stdClass', ['__invoke']);
        $dispatchMock = $this->getMock('stdClass', ['__invoke']);

        $errorMock->expects($this->once())->method('__invoke')->will($this->returnCallback(function (MvcEvent $event) {
            $event->stopPropagation(true);
            $event->setRouteMatch(new Router\RouteMatch([]));
            $event->setError('');
        }));
        $routeMock->expects($this->once())->method('__invoke')->will($this->returnCallback(function (MvcEvent $event) {
            $event->stopPropagation(true);
            $event->setName(MvcEvent::EVENT_DISPATCH_ERROR);
            $event->setError(Application::ERROR_ROUTER_NO_MATCH);
            return $event->getApplication()->getEventManager()->triggerEvent($event)->last();
        }));
        $dispatchMock->expects($this->once())->method('__invoke')->will($this->returnValue($response));
        $finishMock->expects($this->once())->method('__invoke')->will($this->returnCallback(function (MvcEvent $event) {
            $event->stopPropagation(true);
        }));

        $this->application->getEventManager()->attach(MvcEvent::EVENT_DISPATCH_ERROR, $errorMock, 100);
        $this->application->getEventManager()->attach(MvcEvent::EVENT_ROUTE, $routeMock, 100);
        $this->application->getEventManager()->attach(MvcEvent::EVENT_DISPATCH, $dispatchMock, 100);
        $this->application->getEventManager()->attach(MvcEvent::EVENT_FINISH, $finishMock, 100);

        $this->application->run();
        $this->assertSame($response, $this->application->getMvcEvent()->getResponse());
    }

    public function eventPropagation()
    {
        return [
            'route'    => [[MvcEvent::EVENT_ROUTE]],
            'dispatch' => [[MvcEvent::EVENT_DISPATCH, MvcEvent::EVENT_RENDER, MvcEvent::EVENT_FINISH]],
        ];
    }

    /**
     * @dataProvider eventPropagation
     */
    public function testEventPropagationStatusIsClearedBetweenEventsDuringRun($events)
    {
        $event = new MvcEvent();
        $event->setTarget($this->application);
        $event->setApplication($this->application)
              ->setRequest($this->application->getRequest())
              ->setResponse($this->application->getResponse())
              ->setRouter($this->serviceManager->get('Router'));
        $event->stopPropagation(true);

        // Intentionally not calling bootstrap; setting mvc event
        $r = new ReflectionProperty($this->application, 'event');
        $r->setAccessible(true);
        $r->setValue($this->application, $event);

        // Setup listeners that stop propagation, but do nothing else
        $marker = [];
        foreach ($events as $event) {
            $marker[$event] = true;
        }
        $marker = (object) $marker;
        $listener = function ($e) use ($marker) {
            $marker->{$e->getName()} = $e->propagationIsStopped();
            $e->stopPropagation(true);
        };
        foreach ($events as $event) {
            $this->application->getEventManager()->attach($event, $listener);
        }

        $this->application->run();

        foreach ($events as $event) {
            $this->assertFalse($marker->{$event}, sprintf('Assertion failed for event "%s"', $event));
        }
    }
}
