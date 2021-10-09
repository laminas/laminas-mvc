<?php

namespace LaminasTest\Mvc\Controller\Plugin;

use Laminas\Mvc\Controller\Plugin\Url as UrlPlugin;
use Laminas\Mvc\Exception\DomainException;
use Laminas\Mvc\Exception\RuntimeException;
use Laminas\Mvc\ModuleRouteListener;
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
        $router = new SimpleRouteStack;
        $router->addRoute('home', LiteralRoute::factory([
            'route'    => '/',
            'defaults' => [
                'controller' => SampleController::class,
            ],
        ]));
        $router->addRoute('default', [
            'type' => Segment::class,
            'options' => [
                'route' => '/:controller[/:action]',
            ]
        ]);
        $this->router = $router;

        $event = new MvcEvent();
        $event->setRouter($router);

        $this->controller = new SampleController();
        $this->controller->setEvent($event);

        $this->plugin = $this->controller->plugin('url');
    }

    public function testPluginCanGenerateUrlWhenProperlyConfigured()
    {
        $url = $this->plugin->fromRoute('home');
        $this->assertEquals('/', $url);
    }

    public function testModel()
    {
        $it = new \ArrayIterator(['controller' => 'ctrl', 'action' => 'act']);

        $url = $this->plugin->fromRoute('default', $it);
        $this->assertEquals('/ctrl/act', $url);
    }

    public function testPluginWithoutControllerRaisesDomainException()
    {
        $plugin = new UrlPlugin();
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('requires a controller');
        $plugin->fromRoute('home');
    }

    public function testPluginWithoutControllerEventRaisesDomainException()
    {
        $controller = new SampleController();
        $plugin     = $controller->plugin('url');
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('event compose a router');
        $plugin->fromRoute('home');
    }

    public function testPluginWithoutRouterInEventRaisesDomainException()
    {
        $controller = new SampleController();
        $event      = new MvcEvent();
        $controller->setEvent($event);
        $plugin = $controller->plugin('url');
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('event compose a router');
        $plugin->fromRoute('home');
    }

    public function testPluginWithoutRouteMatchesInEventRaisesExceptionWhenNoRouteProvided()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('RouteMatch');
        $url = $this->plugin->fromRoute();
    }

    public function testPluginWithRouteMatchesReturningNoMatchedRouteNameRaisesExceptionWhenNoRouteProvided()
    {
        $event = $this->controller->getEvent();
        $event->setRouteMatch(new RouteMatch([]));
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('matched');
        $url = $this->plugin->fromRoute();
    }

    public function testPassingNoArgumentsWithValidRouteMatchGeneratesUrl()
    {
        $routeMatch = new RouteMatch([]);
        $routeMatch->setMatchedRouteName('home');
        $this->controller->getEvent()->setRouteMatch($routeMatch);
        $url = $this->plugin->fromRoute();
        $this->assertEquals('/', $url);
    }

    public function testCanReuseMatchedParameters()
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

    public function testCanPassBooleanValueForThirdArgumentToAllowReusingRouteMatches()
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

    /**
     *
     */
    public function testRemovesModuleRouteListenerParamsWhenReusingMatchedParameters()
    {
        $router = new TreeRouteStack;
        $router->addRoute('default', [
            'type' => Segment::class,
            'options' => [
                'route'    => '/:controller/:action',
                'defaults' => [
                    ModuleRouteListener::MODULE_NAMESPACE => 'LaminasTest\Mvc\Controller\TestAsset',
                    'controller' => 'SampleController',
                    'action'     => 'Dash'
                ]
            ],
            'child_routes' => [
                'wildcard' => [
                    'type'    => Wildcard::class,
                    'options' => [
                        'param_delimiter'     => '=',
                        'key_value_delimiter' => '%'
                    ]
                ]
            ]
        ]);

        $routeMatch = new RouteMatch([
            ModuleRouteListener::MODULE_NAMESPACE => 'LaminasTest\Mvc\Controller\TestAsset',
            'controller' => 'Rainbow'
        ]);
        $routeMatch->setMatchedRouteName('default/wildcard');

        $event = new MvcEvent();
        $event->setRouter($router)
              ->setRouteMatch($routeMatch);

        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->onRoute($event);

        $controller = new SampleController();
        $controller->setEvent($event);
        $url = $controller->plugin('url')->fromRoute('default/wildcard', ['Twenty' => 'Cooler'], true);

        $this->assertEquals('/Rainbow/Dash=Twenty%Cooler', $url);
    }
}
