<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc;

use Interop\Container\ContainerInterface;
use Laminas\EventManager\EventManager;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\Application;
use Laminas\Mvc\MiddlewareListener;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\RouteMatch;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit_Framework_TestCase as TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

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
     * @return MvcEven
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

        $this->assertInstanceOf('Laminas\Http\Response', $return);
        $this->assertSame(200, $return->getStatusCode());
        $this->assertEquals('Test!', $return->getBody());
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

        $this->assertInstanceOf('Laminas\Http\Response', $return);
        $this->assertSame(200, $return->getStatusCode());
        $this->assertEquals(TestAsset\Middleware::class, $return->getBody());
    }
}
