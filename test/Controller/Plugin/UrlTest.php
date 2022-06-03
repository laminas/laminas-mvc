<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\Controller\Plugin;

use ArrayIterator;
use Laminas\Mvc\Controller\Plugin\Url as UrlPlugin;
use Laminas\Mvc\Exception\DomainException;
use Laminas\Mvc\Exception\RuntimeException;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\Http\Literal as LiteralRoute;
use Laminas\Router\Http\Segment;
use Laminas\Router\Http\Segment as SegmentRoute;
use Laminas\Router\Http\TreeRouteStack;
use Laminas\Router\Http\Wildcard;
use Laminas\Router\RouteMatch;
use Laminas\Router\SimpleRouteStack;
use LaminasTest\Mvc\Controller\TestAsset\SampleController;
use PHPUnit\Framework\TestCase;

class UrlTest extends TestCase
{
    public function setUp(): void
    {
        $router = new SimpleRouteStack();
        $router->addRoute('home', LiteralRoute::factory([
            'route'    => '/',
            'defaults' => [
                'controller' => SampleController::class,
            ],
        ]));
        $router->addRoute('default', [
            'type'    => Segment::class,
            'options' => [
                'route' => '/:controller[/:action]',
            ],
        ]);
        $this->router = $router;

        $event = new MvcEvent();
        $event->setRouter($router);

        $this->controller = new SampleController();
        $this->controller->setEvent($event);

        $this->plugin = $this->controller->plugin('url');
    }

    public function testPluginCanGenerateUrlWhenProperlyConfigured(): void
    {
        $url = $this->plugin->fromRoute('home');
        $this->assertEquals('/', $url);
    }

    public function testModel(): void
    {
        $it = new ArrayIterator(['controller' => 'ctrl', 'action' => 'act']);

        $url = $this->plugin->fromRoute('default', $it);
        $this->assertEquals('/ctrl/act', $url);
    }

    public function testPluginWithoutControllerRaisesDomainException(): void
    {
        $plugin = new UrlPlugin();
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('requires a controller');
        $plugin->fromRoute('home');
    }

    public function testPluginWithoutControllerEventRaisesDomainException(): void
    {
        $controller = new SampleController();
        $plugin     = $controller->plugin('url');
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('event compose a router');
        $plugin->fromRoute('home');
    }

    public function testPluginWithoutRouterInEventRaisesDomainException(): void
    {
        $controller = new SampleController();
        $event      = new MvcEvent();
        $controller->setEvent($event);
        $plugin = $controller->plugin('url');
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('event compose a router');
        $plugin->fromRoute('home');
    }

    public function testPluginWithoutRouteMatchesInEventRaisesExceptionWhenNoRouteProvided(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('RouteMatch');
        $url = $this->plugin->fromRoute();
    }

    public function testPluginWithRouteMatchesReturningNoMatchedRouteNameRaisesExceptionWhenNoRouteProvided(): void
    {
        $event = $this->controller->getEvent();
        $event->setRouteMatch(new RouteMatch([]));
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('matched');
        $url = $this->plugin->fromRoute();
    }

    public function testPassingNoArgumentsWithValidRouteMatchGeneratesUrl(): void
    {
        $routeMatch = new RouteMatch([]);
        $routeMatch->setMatchedRouteName('home');
        $this->controller->getEvent()->setRouteMatch($routeMatch);
        $url = $this->plugin->fromRoute();
        $this->assertEquals('/', $url);
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
        $url = $this->plugin->fromRoute('replace', ['action' => 'bar'], [], true);
        $this->assertEquals('/foo/bar', $url);
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
        $url = $this->plugin->fromRoute('replace', ['action' => 'bar'], true);
        $this->assertEquals('/foo/bar', $url);
    }
}
