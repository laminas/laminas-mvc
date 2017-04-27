<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Mvc;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;
use stdClass;
use Zend\EventManager\EventManager;
use Zend\EventManager\SharedEventManager;
use Zend\EventManager\Test\EventListenerIntrospectionTrait;
use Zend\Http\PhpEnvironment;
use Zend\Http\PhpEnvironment\Response;
use Zend\ModuleManager\Listener\ConfigListener;
use Zend\ModuleManager\ModuleEvent;
use Zend\Mvc\Application;
use Zend\Mvc\Controller\ControllerManager;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Service\ServiceManagerConfig;
use Zend\Mvc\Service\ServiceListenerFactory;
use Zend\Router;
use Zend\ServiceManager\ServiceManager;
use Zend\Stdlib\ArrayUtils;
use Zend\Stdlib\ResponseInterface;
use Zend\View\Model\ViewModel;

class ApplicationTest extends TestCase
{
    use EventListenerIntrospectionTrait;

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
            (new Router\ConfigProvider())->getDependencyConfig()
        );

        $serviceConfig = ArrayUtils::merge(
            $serviceConfig,
            [
                'invokables' => [
                    'Request'              => PhpEnvironment\Request::class,
                    'Response'             => PhpEnvironment\Response::class,
                    'ViewManager'          => TestAsset\MockViewManager::class,
                    'SendResponseListener' => TestAsset\MockSendResponseListener::class,
                    'BootstrapListener'    => TestAsset\StubBootstrapListener::class,
                ],
                'factories' => [
                    'Router' => Router\RouterFactory::class,
                ],
                'services' => [
                    'config' => [],
                    'ApplicationConfig' => [
                        'modules' => [
                            'Zend\Router',
                        ],
                        'module_listener_options' => [
                            'config_cache_enabled' => false,
                            'cache_dir'            => 'data/cache',
                            'module_paths'         => [],
                        ],
                    ],
                ],
            ]
        );
        $this->serviceManager = new ServiceManager();
        (new ServiceManagerConfig($serviceConfig))->configureServiceManager($this->serviceManager);
        $this->serviceManager->setAllowOverride(true);
        $this->application = $this->serviceManager->get('Application');
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
        $this->assertInstanceOf(EventManager::class, $appEvents);
        $this->assertNotSame($events, $appEvents);
        $this->assertSame($sharedEvents, $appEvents->getSharedManager());
    }

    public function testEventManagerListensOnApplicationContext()
    {
        $events      = $this->application->getEventManager();
        $identifiers = $events->getIdentifiers();
        $expected    = [Application::class];
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
        $this->assertInstanceOf(SharedEventManager::class, $sharedEvents);
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
        $this->assertInstanceOf(MvcEvent::class, $event);

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
        $this->serviceManager->setService('HttpRouter', $router);
        $this->serviceManager->setService('Router', $router);

        if ($addService) {
            $this->services->addFactory('ControllerManager', function ($services) {
                return new ControllerManager($services, ['factories' => [
                    'path' => function () {
                        return new TestAsset\PathController;
                    },
                ]]);
            });
        }

        $this->application->bootstrap();
        return $this->application;
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

        $this->serviceManager->setFactory('ControllerManager', function ($services) {
            return new ControllerManager($services, ['factories' => [
                'sample' => function () {
                    return new Controller\TestAsset\SampleController();
                },
            ]]);
        });

        $this->application->bootstrap();
        return $this->application;
    }

    public function setupBadController($addService = true, $action = 'test')
    {
        $request = $this->serviceManager->get('Request');
        $request->setUri('http://example.local/bad');

        $router = $this->serviceManager->get('HttpRouter');
        $route  = Router\Http\Literal::factory([
            'route'    => '/bad',
            'defaults' => [
                'controller' => 'bad',
                'action'     => $action,
            ],
        ]);
        $router->addRoute('bad', $route);

        if ($addService) {
            $this->serviceManager->setFactory('ControllerManager', function ($services) {
                return new ControllerManager($services, ['factories' => [
                    'bad' => function () {
                        return new Controller\TestAsset\BadController();
                    },
                ]]);
            });
        }

        $this->application->bootstrap();
        return $this->application;
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
     * @requires PHP 7.0
     * @group error-handling
     */
    public function testPhp7ErrorRaisedInDispatchableShouldRaiseDispatchErrorEvent()
    {
        $this->setupBadController(true, 'test-php7-error');
        $response = $this->application->getResponse();
        $events   = $this->application->getEventManager();
        $events->attach(MvcEvent::EVENT_DISPATCH_ERROR, function ($e) use ($response) {
            $exception = $e->getParam('exception');
            $response->setContent($exception->getMessage());
            return $response;
        });

        $this->application->run();
        $this->assertContains('Raised an error', $response->getContent());
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
        $this->assertContains(Application::class, $response->getContent());
    }

    public function testOnDispatchErrorEventPassedToTriggersShouldBeTheOriginalOne()
    {
        $application = $this->setupPathController(false);
        $controllerManager = $application->getServiceManager()->get('ControllerManager');
        $model = $this->createMock(ViewModel::class);
        $application->getEventManager()->attach(MvcEvent::EVENT_DISPATCH_ERROR, function ($e) use ($model) {
            $e->setResult($model);
        });

        $application->run();
        $event = $application->getMvcEvent();
        $this->assertInstanceOf(ViewModel::class, $event->getResult());
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

        $response     = $this->createMock(ResponseInterface::class);
        $finishMock   = $this->getMockBuilder(stdClass::class)
            ->setMethods(['__invoke'])
            ->getMock();
        $routeMock    = $this->getMockBuilder(stdClass::class)
            ->setMethods(['__invoke'])
            ->getMock();
        $dispatchMock = $this->getMockBuilder(stdClass::class)
            ->setMethods(['__invoke'])
            ->getMock();

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

        $response     = $this->createMock(ResponseInterface::class);
        $errorMock    = $this->getMockBuilder(stdClass::class)
            ->setMethods(['__invoke'])
            ->getMock();
        $finishMock   = $this->getMockBuilder(stdClass::class)
            ->setMethods(['__invoke'])
            ->getMock();
        $routeMock    = $this->getMockBuilder(stdClass::class)
            ->setMethods(['__invoke'])
            ->getMock();
        $dispatchMock = $this->getMockBuilder(stdClass::class)
            ->setMethods(['__invoke'])
            ->getMock();

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
