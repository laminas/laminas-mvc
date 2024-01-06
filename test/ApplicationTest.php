<?php

declare(strict_types=1);

namespace LaminasTest\Mvc;

use Laminas\EventManager\EventManager;
use Laminas\EventManager\SharedEventManager;
use Laminas\EventManager\Test\EventListenerIntrospectionTrait;
use Laminas\Http\PhpEnvironment\Request;
use Laminas\Http\PhpEnvironment\Response;
use Laminas\Mvc\Application;
use Laminas\Mvc\ConfigProvider;
use Laminas\Mvc\Controller\ControllerManager;
use Laminas\Mvc\MvcEvent;
use Laminas\Router;
use Laminas\Router\Http\Literal;
use Laminas\Router\RouteMatch;
use Laminas\Router\SimpleRouteStack;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Stdlib\ArrayUtils;
use Laminas\Stdlib\ResponseInterface;
use Laminas\View\Model\ViewModel;
use LaminasTest\Mvc\Controller\TestAsset\BadController;
use LaminasTest\Mvc\Controller\TestAsset\SampleController;
use LaminasTest\Mvc\TestAsset\MockSendResponseListener;
use LaminasTest\Mvc\TestAsset\MockViewManager;
use LaminasTest\Mvc\TestAsset\PathController;
use LaminasTest\Mvc\TestAsset\StubBootstrapListener;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;
use stdClass;

use function array_values;
use function sprintf;

class ApplicationTest extends TestCase
{
    use EventListenerIntrospectionTrait;

    /** @var ServiceManager */
    protected $serviceManager;

    /** @var Application */
    protected $application;

    public function setUp(): void
    {
        $testConfig = [
            'dependencies' => [
                'invokables' => [
                    'Request'              => Request::class,
                    'Response'             => Response::class,
                    'ViewManager'          => MockViewManager::class,
                    'SendResponseListener' => MockSendResponseListener::class,
                    'BootstrapListener'    => StubBootstrapListener::class,
                ],
                'services'   => [
                    'config' => [],
                ],
            ],
        ];

        $config                                       = ArrayUtils::merge(
            ArrayUtils::merge(
                (new ConfigProvider())(),
                (new Router\ConfigProvider())(),
            ),
            $testConfig
        );
        $config['dependencies']['services']['config'] = $config;

        $this->serviceManager = new ServiceManager($config['dependencies']);
        $this->serviceManager->setAllowOverride(true);
        $this->application = $this->serviceManager->get('Application');
    }

    public function testEventManagerIsPopulated(): void
    {
        $events       = $this->serviceManager->get('EventManager');
        $sharedEvents = $events->getSharedManager();
        $appEvents    = $this->application->getEventManager();
        $this->assertInstanceOf(EventManager::class, $appEvents);
        $this->assertNotSame($events, $appEvents);
        $this->assertSame($sharedEvents, $appEvents->getSharedManager());
    }

    public function testEventManagerListensOnApplicationContext(): void
    {
        $events      = $this->application->getEventManager();
        $identifiers = $events->getIdentifiers();
        $expected    = [Application::class];
        $this->assertEquals($expected, array_values($identifiers));
    }

    public function testServiceManagerIsPopulated(): void
    {
        $this->assertSame($this->serviceManager, $this->application->getServiceManager());
    }

    private function getIdentifiersFromSharedEventManager(SharedEventManager $events): array
    {
        $r = new ReflectionProperty($events, 'identifiers');
        $r->setAccessible(true);
        return $r->getValue($events);
    }

    /**
     * @dataProvider defaultApplicationListenersProvider
     */
    public function testHasRegisteredDefaultListeners(string $listenerServiceName, string $event, string $method): void
    {
        $listenerService = $this->serviceManager->get($listenerServiceName);
        $this->application->bootstrap();
        $events = $this->application->getEventManager();

        $listeners = $this->getArrayOfListenersForEvent($event, $events);
        $this->assertContains([$listenerService, $method], $listeners);
    }

    public function defaultApplicationListenersProvider(): array
    {
        // @codingStandardsIgnoreStart
        //                     [ Service Name,           Event,                       Method,        isCustom ]
        return [
            'route'         => ['RouteListener'        , MvcEvent::EVENT_ROUTE     , 'onRoute',      false],
            'dispatch'      => ['DispatchListener'     , MvcEvent::EVENT_DISPATCH  , 'onDispatch',   false],
            'send_response' => ['SendResponseListener' , MvcEvent::EVENT_FINISH    , 'sendResponse', false],
            'view_manager'  => ['ViewManager'          , MvcEvent::EVENT_BOOTSTRAP , 'onBootstrap',  false],
            'http_method'   => ['HttpMethodListener'   , MvcEvent::EVENT_ROUTE     , 'onRoute',      false],
        ];
        // @codingStandardsIgnoreEnd
    }

    public function testBootstrapRegistersConfiguredMvcEvent(): void
    {
        $this->assertNull($this->application->getMvcEvent());
        $this->application->bootstrap();
        $event = $this->application->getMvcEvent();
        $this->assertInstanceOf(MvcEvent::class, $event);

        $router = $this->serviceManager->get('HttpRouter');

        $this->assertFalse($event->isError());
        $this->assertNull($event->getRequest());
        $this->assertNull($event->getResponse());
        $this->assertSame($router, $event->getRouter());
        $this->assertSame($this->application, $event->getApplication());
        $this->assertSame($this->application, $event->getTarget());
    }

    public function setupPathController(bool $addService = true): Application
    {
        $request = $this->serviceManager->get('Request');
        $request->setUri('http://example.local/path');

        $router = $this->serviceManager->get('HttpRouter');
        $route  = Literal::factory([
            'route'    => '/path',
            'defaults' => [
                'controller' => 'path',
            ],
        ]);
        $router->addRoute('path', $route);
        $this->serviceManager->setService('HttpRouter', $router);
        $this->serviceManager->setService('Router', $router);

        if ($addService) {
            $this->services->addFactory('ControllerManager', static fn($services) => new ControllerManager($services, [
                'factories' => [
                    'path' => static fn(): PathController => new PathController(),
                ],
            ]));
        }

        $this->application->bootstrap();
        return $this->application;
    }

    public function setupActionController(): Application
    {
        $request = $this->serviceManager->get('Request');
        $request->setUri('http://example.local/sample');

        $router = $this->serviceManager->get('HttpRouter');
        $route  = Literal::factory([
            'route'    => '/sample',
            'defaults' => [
                'controller' => 'sample',
                'action'     => 'test',
            ],
        ]);
        $router->addRoute('sample', $route);

        $this->serviceManager->setFactory(
            'ControllerManager',
            static fn($services) => new ControllerManager($services, [
                'factories' => [
                    'sample' => static fn(): SampleController => new SampleController(),
                ],
            ])
        );

        $this->application->bootstrap();
        return $this->application;
    }

    public function setupBadController(bool $addService = true, string $action = 'test'): Application
    {
        $request = $this->serviceManager->get('Request');
        $request->setUri('http://example.local/bad');

        $router = $this->serviceManager->get('HttpRouter');
        $route  = Literal::factory([
            'route'    => '/bad',
            'defaults' => [
                'controller' => 'bad',
                'action'     => $action,
            ],
        ]);
        $router->addRoute('bad', $route);

        if ($addService) {
            $this->serviceManager->get('ControllerManager')->setFactory(
                'bad',
                static fn(): BadController => new BadController()
            );
        }

        $this->application->bootstrap();
        return $this->application;
    }

    public function testFinishEventIsTriggeredAfterDispatching(): void
    {
        $application = $this->setupActionController();
        $application->getEventManager()->attach(
            MvcEvent::EVENT_FINISH,
            static fn($e) => $e->getResponse()->setContent($e->getResponse()->getBody() . 'foobar')
        );
        $application->run();
        $this->assertStringContainsString(
            'foobar',
            $application->getMvcEvent()->getResponse()->getBody(),
            'The "finish" event was not triggered ("foobar" not in response)'
        );
    }

    public function testRoutingFailureShouldTriggerDispatchError(): void
    {
        $application = $this->setupBadController();
        $router      = new SimpleRouteStack();
        $event       = $application->getMvcEvent();
        $event->setRouter($router);

        $events = $application->getEventManager();
        $events->attach(MvcEvent::EVENT_DISPATCH_ERROR, static function (MvcEvent $e): ResponseInterface {
            $error    = $e->getError();
            $response = $e->getResponse();
            $response->setContent("Code: " . $error);
            return $response;
        });

        $application->run();

        $response = $event->getResponse();
        self::assertInstanceOf(ResponseInterface::class, $response);
        $this->assertTrue($event->isError());
        $this->assertStringContainsString(Application::ERROR_ROUTER_NO_MATCH, $response->getContent());
    }

    public function testLocatorExceptionShouldTriggerDispatchError(): void
    {
        $application = $this->setupPathController(false);
        $response    = new Response();
        $application->getEventManager()->attach(MvcEvent::EVENT_DISPATCH_ERROR, static fn($e): Response => $response);

        $application->run();
        $this->assertSame($response, $application->getMvcEvent()->getResponse());
    }

    public function testFailureForRouteToReturnRouteMatchShouldPopulateEventError(): void
    {
        $application = $this->setupBadController();
        $router      = new SimpleRouteStack();
        $event       = $application->getMvcEvent();
        $event->setRouter($router);

        $events = $application->getEventManager();
        $events->attach(MvcEvent::EVENT_DISPATCH_ERROR, static function (MvcEvent $e): ResponseInterface {
            $error    = $e->getError();
            $response = $e->getResponse();
            $response->setContent("Code: " . $error);
            return $response;
        });

        $application->run();
        $this->assertTrue($event->isError());
        $this->assertEquals(Application::ERROR_ROUTER_NO_MATCH, $event->getError());
    }

    public function testFinishShouldRunEvenIfRouteEventReturnsResponse(): void
    {
        $this->application->bootstrap();
        $response = new Response();
        $events   = $this->application->getEventManager();
        $events->attach(MvcEvent::EVENT_ROUTE, static fn($e): ResponseInterface => $response, 100);

        $token = new stdClass();
        $events->attach(MvcEvent::EVENT_FINISH, static function ($e) use ($token): void {
            $token->foo = 'bar';
        });

        $this->application->run();
        $this->assertTrue(isset($token->foo));
        $this->assertEquals('bar', $token->foo);
    }

    public function testFinishShouldRunEvenIfDispatchEventReturnsResponse(): void
    {
        $this->application->bootstrap();
        $response = new Response();
        $events   = $this->application->getEventManager();
        $events->clearListeners(MvcEvent::EVENT_ROUTE);
        $events->attach(MvcEvent::EVENT_DISPATCH, static fn($e): ResponseInterface => $response, 100);

        $token = new stdClass();
        $events->attach(MvcEvent::EVENT_FINISH, static function ($e) use ($token): void {
            $token->foo = 'bar';
        });

        $this->application->run();
        $this->assertTrue(isset($token->foo));
        $this->assertEquals('bar', $token->foo);
    }

    public function testApplicationShouldBeEventTargetAtFinishEvent(): void
    {
        $application = $this->setupActionController();

        $events = $application->getEventManager();
        $events->attach(MvcEvent::EVENT_FINISH, static function (MvcEvent $e): ResponseInterface {
            $response = $e->getResponse();
            $response->setContent("EventClass: " . $e->getTarget()::class);
            return $response;
        });

        $application->run();
        $response = $application->getMvcEvent()->getResponse();
        self::assertInstanceOf(ResponseInterface::class, $response);
        $this->assertStringContainsString(Application::class, $response->getContent());
    }

    public function testOnDispatchErrorEventPassedToTriggersShouldBeTheOriginalOne(): void
    {
        $application = $this->setupPathController(false);
        $model       = $this->createMock(ViewModel::class);
        $application->getEventManager()->attach(
            MvcEvent::EVENT_DISPATCH_ERROR,
            static function ($e) use ($model): void {
                $e->setResult($model);
            }
        );

        $application->run();
        $event = $application->getMvcEvent();
        $this->assertInstanceOf(ViewModel::class, $event->getResult());
    }

    public function testReturnsResponseFromListenerWhenRouteEventShortCircuits(): void
    {
        $this->application->bootstrap();
        $testResponse = new Response();
        $events       = $this->application->getEventManager();
        $events->clearListeners(MvcEvent::EVENT_DISPATCH);
        $events->attach(MvcEvent::EVENT_ROUTE, static function ($e) use ($testResponse): Response {
            $testResponse->setContent('triggered');
            return $testResponse;
        }, 100);

        $triggered = false;
        $events->attach(MvcEvent::EVENT_FINISH, function ($e) use ($testResponse, &$triggered): void {
            $this->assertSame($testResponse, $e->getResponse());
            $triggered = true;
        });

        $this->application->run();
        $this->assertTrue($triggered);
    }

    public function testReturnsResponseFromListenerWhenDispatchEventShortCircuits(): void
    {
        $this->application->bootstrap();
        $testResponse = new Response();
        $events       = $this->application->getEventManager();
        $events->clearListeners(MvcEvent::EVENT_ROUTE);
        $events->attach(MvcEvent::EVENT_DISPATCH, static function ($e) use ($testResponse): Response {
            $testResponse->setContent('triggered');
            return $testResponse;
        }, 100);

        $triggered = false;
        $events->attach(MvcEvent::EVENT_FINISH, function ($e) use ($testResponse, &$triggered): void {
            $this->assertSame($testResponse, $e->getResponse());
            $triggered = true;
        });

        $this->application->run();
        $this->assertTrue($triggered);
    }

    public function testCompleteRequestShouldReturnApplicationInstance(): void
    {
        $r = new ReflectionMethod($this->application, 'completeRequest');
        $r->setAccessible(true);

        $this->application->bootstrap();
        $event  = $this->application->getMvcEvent();
        $result = $r->invoke($this->application, $event);
        $this->assertSame($this->application, $result);
    }

    public function testFailedRoutingShouldBePreventable(): void
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

        $routeMock->expects($this->once())->method('__invoke')->willReturnCallback(
            static function (MvcEvent $event): void {
                $event->stopPropagation(true);
                $event->setRouteMatch(new RouteMatch([]));
            }
        );
        $dispatchMock->expects($this->once())->method('__invoke')->willReturn($response);
        $finishMock->expects($this->once())->method('__invoke')->willReturnCallback(
            static function (MvcEvent $event): void {
                $event->stopPropagation(true);
            }
        );

        $this->application->getEventManager()->attach(MvcEvent::EVENT_ROUTE, $routeMock, 100);
        $this->application->getEventManager()->attach(MvcEvent::EVENT_DISPATCH, $dispatchMock, 100);
        $this->application->getEventManager()->attach(MvcEvent::EVENT_FINISH, $finishMock, 100);

        $this->application->run();
        $this->assertSame($response, $this->application->getMvcEvent()->getResponse());
    }

    public function testCanRecoverFromApplicationError(): void
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

        $errorMock->expects($this->once())->method('__invoke')->willReturnCallback(
            static function (MvcEvent $event): void {
                $event->stopPropagation(true);
                $event->setRouteMatch(new RouteMatch([]));
                $event->setError('');
            }
        );
        $routeMock->expects($this->once())->method('__invoke')->willReturnCallback(static function (MvcEvent $event) {
                $event->stopPropagation(true);
                $event->setName(MvcEvent::EVENT_DISPATCH_ERROR);
                $event->setError(Application::ERROR_ROUTER_NO_MATCH);
                return $event->getApplication()->getEventManager()->triggerEvent($event)->last();
        });
        $dispatchMock->expects($this->once())->method('__invoke')->willReturn($response);
        $finishMock->expects($this->once())->method('__invoke')->willReturnCallback(
            static function (MvcEvent $event): void {
                $event->stopPropagation(true);
            }
        );

        $this->application->getEventManager()->attach(MvcEvent::EVENT_DISPATCH_ERROR, $errorMock, 100);
        $this->application->getEventManager()->attach(MvcEvent::EVENT_ROUTE, $routeMock, 100);
        $this->application->getEventManager()->attach(MvcEvent::EVENT_DISPATCH, $dispatchMock, 100);
        $this->application->getEventManager()->attach(MvcEvent::EVENT_FINISH, $finishMock, 100);

        $this->application->run();
        $this->assertSame($response, $this->application->getMvcEvent()->getResponse());
    }

    public function eventPropagation(): array
    {
        return [
            'route'    => [[MvcEvent::EVENT_ROUTE]],
            'dispatch' => [[MvcEvent::EVENT_DISPATCH, MvcEvent::EVENT_RENDER, MvcEvent::EVENT_FINISH]],
        ];
    }

    /**
     * @dataProvider eventPropagation
     */
    public function testEventPropagationStatusIsClearedBetweenEventsDuringRun(array $events): void
    {
        $this->markTestIncomplete('Test is of bad quality and requires rewrite');
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
        $marker   = (object) $marker;
        $listener = static function ($e) use ($marker): void {
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
