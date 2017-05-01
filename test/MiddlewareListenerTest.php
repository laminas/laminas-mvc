<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Mvc;

use Interop\Container\ContainerInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\EventManager\EventManager;
use Zend\Http\Request;
use Zend\Http\Response;
use Zend\Mvc\Application;
use Zend\Mvc\Exception\InvalidMiddlewareException;
use Zend\Mvc\Exception\ReachedFinalHandlerException;
use Zend\Mvc\MiddlewareListener;
use Zend\Mvc\MvcEvent;
use Zend\Router\RouteMatch;
use Zend\ServiceManager\ServiceManager;

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

        $eventManager = new EventManager();

        $serviceManager = $this->prophesize(ContainerInterface::class);
        $serviceManager->has($middlewareMatched)->willReturn(true);
        $serviceManager->get($middlewareMatched)->willReturn($middleware);

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
}
