<?php
/**
 * @see       https://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2015-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Mvc;

use Interop\Container\ContainerInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response as DiactorosResponse;
use Zend\EventManager\EventManager;
use Zend\EventManager\SharedEventManager;
use Zend\Http\Request;
use Zend\Http\Response;
use Zend\Mvc\Application;
use Zend\Mvc\Exception\InvalidMiddlewareException;
use Zend\Mvc\Exception\ReachedFinalHandlerException;
use Zend\Mvc\MiddlewareListener;
use Zend\Mvc\MvcEvent;
use Zend\Router\RouteMatch;
use Zend\ServiceManager\ServiceManager;
use Zend\Stdlib\DispatchableInterface;
use Zend\View\Model\ModelInterface;

class MiddlewareListenerTest extends TestCase
{
    /**
     * @var \Prophecy\Prophecy\ObjectProphecy
     */
    private $routeMatch;

    /**
     * Create an MvcEvent, populated with everything it needs.
     *
     * @param string $middlewareMatched Middleware service matched by routing
     * @param mixed $middleware Value to return for middleware service
     * @return MvcEvent
     */
    public function createMvcEvent($middlewareMatched, $middleware = null)
    {
        $response   = new Response();
        $this->routeMatch = $this->prophesize(RouteMatch::class);
        $this->routeMatch->getParam('middleware', false)->willReturn($middlewareMatched);
        $this->routeMatch->getParams()->willReturn([]);

        $eventManager   = new EventManager();
        $serviceManager = new ServiceManager([
            'factories' => [
                'EventManager' => function () {
                    return new EventManager();
                },
            ],
            'services' => [
                $middlewareMatched => $middleware,
            ],
        ]);

        $application = $this->prophesize(Application::class);
        $application->getEventManager()->willReturn($eventManager);
        $application->getServiceManager()->willReturn($serviceManager);
        $application->getResponse()->willReturn($response);

        $event = new MvcEvent();
        $event->setRequest(new Request());
        $event->setResponse($response);
        $event->setApplication($application->reveal());
        $event->setRouteMatch($this->routeMatch->reveal());

        return $event;
    }

    public function testSuccessfullyDispatchesMiddleware()
    {
        $event = $this->createMvcEvent('path', function ($request, $response) {
            $this->assertInstanceOf(ServerRequestInterface::class, $request);
            $this->assertInstanceOf(ResponseInterface::class, $response);
            $response->getBody()->write('Test!');
            return $response;
        });
        $application = $event->getApplication();

        $application->getEventManager()->attach(MvcEvent::EVENT_DISPATCH_ERROR, function ($e) {
            $this->fail(sprintf('dispatch.error triggered when it should not be: %s', var_export($e->getError(), 1)));
        });

        $listener = new MiddlewareListener();
        $return   = $listener->onDispatch($event);
        $this->assertInstanceOf(Response::class, $return);

        $this->assertInstanceOf(Response::class, $return);
        $this->assertSame(200, $return->getStatusCode());
        $this->assertEquals('Test!', $return->getBody());
    }

    public function testSuccessfullyDispatchesHttpInteropMiddleware()
    {
        $expectedOutput = uniqid('expectedOutput', true);

        $middleware = $this->createMock(MiddlewareInterface::class);
        $middleware->expects($this->once())->method('process')->willReturn(new HtmlResponse($expectedOutput));

        $event = $this->createMvcEvent('path', $middleware);
        $application = $event->getApplication();

        $application->getEventManager()->attach(MvcEvent::EVENT_DISPATCH_ERROR, function ($e) {
            $this->fail(sprintf('dispatch.error triggered when it should not be: %s', var_export($e->getError(), 1)));
        });

        $listener = new MiddlewareListener();
        $return   = $listener->onDispatch($event);
        $this->assertInstanceOf(Response::class, $return);

        $this->assertInstanceOf(Response::class, $return);
        $this->assertSame(200, $return->getStatusCode());
        $this->assertEquals($expectedOutput, $return->getBody());
    }

    public function testMatchedRouteParamsAreInjectedToRequestAsAttributes()
    {
        $matchedRouteParam = uniqid('matched param', true);
        $routeAttribute = null;

        $event = $this->createMvcEvent(
            'foo',
            function (ServerRequestInterface $request, ResponseInterface $response) use (&$routeAttribute) {
                $routeAttribute = $request->getAttribute(RouteMatch::class);
                $response->getBody()->write($request->getAttribute('myParam', 'param did not exist'));
                return $response;
            }
        );

        $this->routeMatch->getParams()->willReturn([
            'myParam' => $matchedRouteParam,
        ]);

        $listener = new MiddlewareListener();
        $return   = $listener->onDispatch($event);
        $this->assertInstanceOf(Response::class, $return);
        $this->assertSame($matchedRouteParam, $return->getBody());
        $this->assertSame($this->routeMatch->reveal(), $routeAttribute);
    }

    public function testSuccessfullyDispatchesPipeOfCallableAndHttpInteropStyleMiddlewares()
    {
        $response   = new Response();
        $routeMatch = $this->prophesize(RouteMatch::class);
        $routeMatch->getParams()->willReturn([]);
        $routeMatch->getParam('middleware', false)->willReturn([
            'firstMiddleware',
            'secondMiddleware',
        ]);

        $eventManager = new EventManager();

        $serviceManager = $this->prophesize(ContainerInterface::class);
        $serviceManager->get('EventManager')->willReturn($eventManager);
        $serviceManager->has('firstMiddleware')->willReturn(true);
        $serviceManager->get('firstMiddleware')->willReturn(function ($request, $response, $next) {
            $this->assertInstanceOf(ServerRequestInterface::class, $request);
            $this->assertInstanceOf(ResponseInterface::class, $response);
            $this->assertTrue(is_callable($next));
            return $next($request->withAttribute('firstMiddlewareAttribute', 'firstMiddlewareValue'), $response);
        });

        $secondMiddleware = $this->createMock(MiddlewareInterface::class);
        $secondMiddleware->expects($this->once())
            ->method('process')
            ->willReturnCallback(function (ServerRequestInterface $request) {
                return new HtmlResponse($request->getAttribute('firstMiddlewareAttribute'));
            });

        $serviceManager->has('secondMiddleware')->willReturn(true);
        $serviceManager->get('secondMiddleware')->willReturn($secondMiddleware);

        $application = $this->prophesize(Application::class);
        $application->getEventManager()->willReturn($eventManager);
        $application->getServiceManager()->will(function () use ($serviceManager) {
            return $serviceManager->reveal();
        });
        $application->getResponse()->willReturn($response);

        $event = new MvcEvent();
        $event->setRequest(new Request());
        $event->setResponse($response);
        $event->setApplication($application->reveal());
        $event->setRouteMatch($routeMatch->reveal());

        $event->getApplication()->getEventManager()->attach(MvcEvent::EVENT_DISPATCH_ERROR, function ($e) {
            $this->fail(sprintf('dispatch.error triggered when it should not be: %s', var_export($e->getError(), 1)));
        });

        $listener = new MiddlewareListener();
        $return   = $listener->onDispatch($event);
        $this->assertInstanceOf(Response::class, $return);

        $this->assertInstanceOf('Zend\Http\Response', $return);
        $this->assertSame(200, $return->getStatusCode());
        $this->assertEquals('firstMiddlewareValue', $return->getBody());
    }

    public function testTriggersErrorForUncallableMiddleware()
    {
        $event       = $this->createMvcEvent('path');
        $application = $event->getApplication();

        $application->getEventManager()->attach(MvcEvent::EVENT_DISPATCH_ERROR, function ($e) {
            $this->assertEquals(Application::ERROR_MIDDLEWARE_CANNOT_DISPATCH, $e->getError());
            $this->assertEquals('path', $e->getController());
            return 'FAILED';
        });

        $listener = new MiddlewareListener();
        $return   = $listener->onDispatch($event);
        $this->assertEquals('FAILED', $return);
    }

    public function testTriggersErrorForExceptionRaisedInMiddleware()
    {
        $exception   = new \Exception();
        $event       = $this->createMvcEvent('path', function ($request, $response) use ($exception) {
            throw $exception;
        });

        $application = $event->getApplication();
        $application->getEventManager()->attach(MvcEvent::EVENT_DISPATCH_ERROR, function ($e) use ($exception) {
            $this->assertEquals(Application::ERROR_EXCEPTION, $e->getError());
            $this->assertSame($exception, $e->getParam('exception'));
            return 'FAILED';
        });

        $listener = new MiddlewareListener();
        $return   = $listener->onDispatch($event);
        $this->assertEquals('FAILED', $return);
    }

    /**
     * Ensure that the listener tests for services in abstract factories.
     */
    public function testCanLoadFromAbstractFactory()
    {
        $response   = new Response();
        $routeMatch = $this->prophesize(RouteMatch::class);
        $routeMatch->getParam('middleware', false)->willReturn('test');
        $routeMatch->getParams()->willReturn([]);

        $eventManager = new EventManager();

        $serviceManager = new ServiceManager();
        $serviceManager->addAbstractFactory(TestAsset\MiddlewareAbstractFactory::class);
        $serviceManager->setFactory(
            'EventManager',
            function () {
                return new EventManager();
            }
        );

        $application = $this->prophesize(Application::class);
        $application->getEventManager()->willReturn($eventManager);
        $application->getServiceManager()->willReturn($serviceManager);
        $application->getResponse()->willReturn($response);

        $event = new MvcEvent();
        $event->setRequest(new Request());
        $event->setResponse($response);
        $event->setApplication($application->reveal());
        $event->setRouteMatch($routeMatch->reveal());

        $eventManager->attach(MvcEvent::EVENT_DISPATCH_ERROR, function ($e) {
            $this->fail(sprintf('dispatch.error triggered when it should not be: %s', var_export($e->getError(), 1)));
        });

        $listener = new MiddlewareListener();
        $return   = $listener->onDispatch($event);

        $this->assertInstanceOf(Response::class, $return);
        $this->assertSame(200, $return->getStatusCode());
        $this->assertEquals(TestAsset\Middleware::class, $return->getBody());
    }

    public function testMiddlewareWithNothingPipedReachesFinalHandlerException()
    {
        $response   = new Response();
        $routeMatch = $this->prophesize(RouteMatch::class);
        $routeMatch->getParams()->willReturn([]);
        $routeMatch->getParam('middleware', false)->willReturn([]);

        $eventManager = new EventManager();

        $serviceManager = $this->prophesize(ContainerInterface::class);
        $application = $this->prophesize(Application::class);
        $application->getEventManager()->willReturn($eventManager);
        $application->getServiceManager()->will(function () use ($serviceManager) {
            return $serviceManager->reveal();
        });
        $application->getResponse()->willReturn($response);

        $serviceManager->get('EventManager')->willReturn($eventManager);

        $event = new MvcEvent();
        $event->setRequest(new Request());
        $event->setResponse($response);
        $event->setApplication($application->reveal());
        $event->setRouteMatch($routeMatch->reveal());

        $event->getApplication()->getEventManager()->attach(MvcEvent::EVENT_DISPATCH_ERROR, function ($e) {
            $this->assertEquals(Application::ERROR_EXCEPTION, $e->getError());
            $this->assertInstanceOf(ReachedFinalHandlerException::class, $e->getParam('exception'));
            return 'FAILED';
        });

        $listener = new MiddlewareListener();
        $return   = $listener->onDispatch($event);
        $this->assertEquals('FAILED', $return);
    }

    public function testNullMiddlewareThrowsInvalidMiddlewareException()
    {
        $response   = new Response();
        $routeMatch = $this->prophesize(RouteMatch::class);
        $routeMatch->getParams()->willReturn([]);
        $routeMatch->getParam('middleware', false)->willReturn([null]);

        $eventManager = new EventManager();

        $serviceManager = $this->prophesize(ContainerInterface::class);
        $application = $this->prophesize(Application::class);
        $application->getEventManager()->willReturn($eventManager);
        $application->getServiceManager()->will(function () use ($serviceManager) {
            return $serviceManager->reveal();
        });
        $application->getResponse()->willReturn($response);

        $event = new MvcEvent();
        $event->setRequest(new Request());
        $event->setResponse($response);
        $event->setApplication($application->reveal());
        $event->setRouteMatch($routeMatch->reveal());

        $event->getApplication()->getEventManager()->attach(MvcEvent::EVENT_DISPATCH_ERROR, function ($e) {
            $this->assertEquals(Application::ERROR_MIDDLEWARE_CANNOT_DISPATCH, $e->getError());
            $this->assertInstanceOf(InvalidMiddlewareException::class, $e->getParam('exception'));
            return 'FAILED';
        });

        $listener = new MiddlewareListener();

        $return = $listener->onDispatch($event);
        $this->assertEquals('FAILED', $return);
    }

    public function testValidMiddlewareDispatchCancelsPreviousDispatchFailures()
    {
        $middlewareName = uniqid('middleware', true);
        $routeMatch     = new RouteMatch(['middleware' => $middlewareName]);
        $response       = new DiactorosResponse();
        /* @var $application Application|\PHPUnit_Framework_MockObject_MockObject */
        $application    = $this->createMock(Application::class);
        $eventManager   = new EventManager();
        $middleware     = $this->getMockBuilder(\stdClass::class)->setMethods(['__invoke'])->getMock();
        $serviceManager = new ServiceManager([
            'factories' => [
                'EventManager' => function () {
                    return new EventManager();
                },
            ],
            'services' => [
                $middlewareName => $middleware,
            ],
        ]);

        $application->expects(self::any())->method('getRequest')->willReturn(new Request());
        $application->expects(self::any())->method('getEventManager')->willReturn($eventManager);
        $application->expects(self::any())->method('getServiceManager')->willReturn($serviceManager);
        $application->expects(self::any())->method('getResponse')->willReturn(new Response());
        $middleware->expects(self::once())->method('__invoke')->willReturn($response);

        $event = new MvcEvent();

        $event->setRequest(new Request());
        $event->setApplication($application);
        $event->setError(Application::ERROR_CONTROLLER_CANNOT_DISPATCH);
        $event->setRouteMatch($routeMatch);

        $listener = new MiddlewareListener();
        $result   = $listener->onDispatch($event);

        self::assertInstanceOf(Response::class, $result);
        self::assertInstanceOf(Response::class, $event->getResult());
        self::assertEmpty($event->getError(), 'Previously set MVC errors are canceled by a successful dispatch');
    }

    public function testValidMiddlewareFiresDispatchableInterfaceEventListeners()
    {
        $middlewareName = uniqid('middleware', true);
        $routeMatch     = new RouteMatch(['middleware' => $middlewareName]);
        $response       = new DiactorosResponse();
        /* @var $application Application|\PHPUnit_Framework_MockObject_MockObject */
        $application    = $this->createMock(Application::class);
        $sharedManager  = new SharedEventManager();
        /* @var $sharedListener callable|\PHPUnit_Framework_MockObject_MockObject */
        $sharedListener = $this->getMockBuilder(\stdClass::class)->setMethods(['__invoke'])->getMock();
        $eventManager   = new EventManager();
        $middleware     = $this->getMockBuilder(\stdClass::class)->setMethods(['__invoke'])->getMock();
        $serviceManager = new ServiceManager([
            'factories' => [
                'EventManager' => function () use ($sharedManager) {
                    return new EventManager($sharedManager);
                },
            ],
            'services' => [
                $middlewareName => $middleware,
            ],
        ]);

        $application->expects(self::any())->method('getRequest')->willReturn(new Request());
        $application->expects(self::any())->method('getEventManager')->willReturn($eventManager);
        $application->expects(self::any())->method('getServiceManager')->willReturn($serviceManager);
        $application->expects(self::any())->method('getResponse')->willReturn(new Response());
        $middleware->expects(self::once())->method('__invoke')->willReturn($response);

        $event = new MvcEvent();

        $event->setRequest(new Request());
        $event->setApplication($application);
        $event->setError(Application::ERROR_CONTROLLER_CANNOT_DISPATCH);
        $event->setRouteMatch($routeMatch);

        $listener = new MiddlewareListener();

        $sharedManager->attach(DispatchableInterface::class, MvcEvent::EVENT_DISPATCH, $sharedListener);
        $sharedListener->expects(self::once())->method('__invoke')->with($event);

        $listener->onDispatch($event);
    }

    /**
     * @dataProvider alreadySetMvcEventResultProvider
     *
     * @param mixed $alreadySetResult
     */
    public function testWillNotDispatchWhenAnMvcEventResultIsAlreadySet($alreadySetResult)
    {
        $middlewareName = uniqid('middleware', true);
        $routeMatch     = new RouteMatch(['middleware' => $middlewareName]);
        /* @var $application Application|\PHPUnit_Framework_MockObject_MockObject */
        $application    = $this->createMock(Application::class);
        $eventManager   = new EventManager();
        $middleware     = $this->getMockBuilder(\stdClass::class)->setMethods(['__invoke'])->getMock();
        $serviceManager = new ServiceManager([
            'factories' => [
                'EventManager' => function () {
                    return new EventManager();
                },
            ],
            'services' => [
                $middlewareName => $middleware,
            ],
        ]);

        $application->expects(self::any())->method('getRequest')->willReturn(new Request());
        $application->expects(self::any())->method('getEventManager')->willReturn($eventManager);
        $application->expects(self::any())->method('getServiceManager')->willReturn($serviceManager);
        $application->expects(self::any())->method('getResponse')->willReturn(new Response());
        $middleware->expects(self::never())->method('__invoke');

        $event = new MvcEvent();

        $event->setResult($alreadySetResult); // a result is already there - listener should bail out early
        $event->setRequest(new Request());
        $event->setApplication($application);
        $event->setError(Application::ERROR_CONTROLLER_CANNOT_DISPATCH);
        $event->setRouteMatch($routeMatch);

        $listener = new MiddlewareListener();

        $eventManager->attach(MvcEvent::EVENT_DISPATCH_ERROR, function () {
            self::fail('No dispatch failures should be raised - dispatch should be skipped');
        });

        $listener->onDispatch($event);

        self::assertSame($alreadySetResult, $event->getResult(), 'The event result was not replaced');
    }

    /**
     * @return mixed[][]
     */
    public function alreadySetMvcEventResultProvider()
    {
        return [
            [123],
            [true],
            [false],
            [[]],
            [new \stdClass()],
            [$this],
            [$this->createMock(ModelInterface::class)],
            [$this->createMock(Response::class)],
            [['view model data' => 'as an array']],
            [['foo' => new \stdClass()]],
            ['a response string'],
        ];
    }
}
