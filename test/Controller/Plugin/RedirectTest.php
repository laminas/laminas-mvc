<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\Controller\Plugin;

use Laminas\Http\Response;
use Laminas\Mvc\Controller\Plugin\Redirect as RedirectPlugin;
use Laminas\Mvc\Exception\DomainException;
use Laminas\Mvc\Exception\RuntimeException;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\Http\Literal as LiteralRoute;
use Laminas\Router\Http\Segment as SegmentRoute;
use Laminas\Router\RouteMatch;
use Laminas\Router\SimpleRouteStack;
use LaminasTest\Mvc\Controller\TestAsset\SampleController;
use PHPUnit\Framework\TestCase;

class RedirectTest extends TestCase
{
    public function setUp(): void
    {
        $this->response = new Response();

        $router = new SimpleRouteStack();
        $router->addRoute('home', LiteralRoute::factory([
            'route'    => '/',
            'defaults' => [
                'controller' => SampleController::class,
            ],
        ]));
        $this->router = $router;

        $routeMatch = new RouteMatch([]);
        $routeMatch->setMatchedRouteName('home');
        $this->routeMatch = $routeMatch;

        $event = new MvcEvent();
        $event->setRouter($router);
        $event->setResponse($this->response);
        $this->event = $event;

        $this->controller = new SampleController();
        $this->controller->setEvent($event);

        $this->plugin = $this->controller->plugin('redirect');
    }

    public function testPluginCanRedirectToRouteWhenProperlyConfigured(): void
    {
        $response = $this->plugin->toRoute('home');
        $this->assertTrue($response->isRedirect());
        $headers  = $response->getHeaders();
        $location = $headers->get('Location');
        $this->assertEquals('/', $location->getFieldValue());
    }

    public function testPluginCanRedirectToUrlWhenProperlyConfigured(): void
    {
        $response = $this->plugin->toUrl('/foo');
        $this->assertTrue($response->isRedirect());
        $headers  = $response->getHeaders();
        $location = $headers->get('Location');
        $this->assertEquals('/foo', $location->getFieldValue());
    }

    public function testPluginWithoutControllerRaisesDomainException(): void
    {
        $plugin = new RedirectPlugin();
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('requires a controller');
        $plugin->toRoute('home');
    }

    public function testPluginWithoutControllerEventRaisesDomainException(): void
    {
        $controller = new SampleController();
        $plugin     = $controller->plugin('redirect');
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('event compose');
        $plugin->toRoute('home');
    }

    public function testPluginWithoutResponseInEventRaisesDomainException(): void
    {
        $controller = new SampleController();
        $event      = new MvcEvent();
        $controller->setEvent($event);
        $plugin = $controller->plugin('redirect');
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('event compose');
        $plugin->toRoute('home');
    }

    public function testRedirectToRouteWithoutRouterInEventRaisesDomainException(): void
    {
        $controller = new SampleController();
        $event      = new MvcEvent();
        $event->setResponse($this->response);
        $controller->setEvent($event);
        $plugin = $controller->plugin('redirect');
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('event compose a router');
        $plugin->toRoute('home');
    }

    public function testPluginWithoutRouteMatchesInEventRaisesExceptionWhenNoRouteProvided(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('RouteMatch');
        $url = $this->plugin->toRoute();
    }

    public function testPassingNoArgumentsWithValidRouteMatchGeneratesUrl(): void
    {
        $routeMatch = new RouteMatch([]);
        $routeMatch->setMatchedRouteName('home');
        $this->controller->getEvent()->setRouteMatch($routeMatch);
        $response = $this->plugin->toRoute();
        $headers  = $response->getHeaders();
        $location = $headers->get('Location');
        $this->assertEquals('/', $location->getFieldValue());
    }

    public function testCanReuseMatchedParameters(): void
    {
        $this->router->addRoute('replace', SegmentRoute::factory([
            'route'    => '/:controller/:action',
            'defaults' => [
                'controller' => SampleController::class,
            ],
        ]));
        $routeMatch = new RouteMatch([
            'controller' => 'foo',
        ]);
        $routeMatch->setMatchedRouteName('replace');
        $this->controller->getEvent()->setRouteMatch($routeMatch);
        $response = $this->plugin->toRoute('replace', ['action' => 'bar'], [], true);
        $headers  = $response->getHeaders();
        $location = $headers->get('Location');
        $this->assertEquals('/foo/bar', $location->getFieldValue());
    }

    public function testCanPassBooleanValueForThirdArgumentToAllowReusingRouteMatches(): void
    {
        $this->router->addRoute('replace', SegmentRoute::factory([
            'route'    => '/:controller/:action',
            'defaults' => [
                'controller' => SampleController::class,
            ],
        ]));
        $routeMatch = new RouteMatch([
            'controller' => 'foo',
        ]);
        $routeMatch->setMatchedRouteName('replace');
        $this->controller->getEvent()->setRouteMatch($routeMatch);
        $response = $this->plugin->toRoute('replace', ['action' => 'bar'], true);
        $headers  = $response->getHeaders();
        $location = $headers->get('Location');
        $this->assertEquals('/foo/bar', $location->getFieldValue());
    }

    public function testPluginCanRefreshToRouteWhenProperlyConfigured(): void
    {
        $this->event->setRouteMatch($this->routeMatch);
        $response = $this->plugin->refresh();
        $this->assertTrue($response->isRedirect());
        $headers  = $response->getHeaders();
        $location = $headers->get('Location');
        $this->assertEquals('/', $location->getFieldValue());
    }

    public function testPluginCanRedirectToRouteWithNullWhenProperlyConfigured(): void
    {
        $this->event->setRouteMatch($this->routeMatch);
        $response = $this->plugin->toRoute();
        $this->assertTrue($response->isRedirect());
        $headers  = $response->getHeaders();
        $location = $headers->get('Location');
        $this->assertEquals('/', $location->getFieldValue());
    }
}
