<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\View;

use Laminas\EventManager\EventManager;
use Laminas\EventManager\Test\EventListenerIntrospectionTrait;
use Laminas\Mvc\MvcEvent;
use Laminas\Mvc\View\Http\InjectTemplateListener;
use Laminas\Router\RouteMatch;
use Laminas\View\Model\ViewModel;
use LaminasTest\Mvc\Controller\TestAsset\SampleController;
use PHPUnit\Framework\TestCase;

class InjectTemplateListenerTest extends TestCase
{
    use EventListenerIntrospectionTrait;

    private InjectTemplateListener $listener;
    private MvcEvent $event;
    private RouteMatch $routeMatch;

    public function setUp(): void
    {
        $controllerMap  = [
            'MappedNs'             => true,
            'LaminasTest\MappedNs' => true,
        ];
        $this->listener = new InjectTemplateListener();
        $this->listener->setControllerMap($controllerMap);
        $this->event      = new MvcEvent();
        $this->routeMatch = new RouteMatch([]);
        $this->event->setRouteMatch($this->routeMatch);
    }

    public function testSetsTemplateBasedOnRouteMatchIfNoTemplateIsSetOnViewModel(): void
    {
        $this->routeMatch->setParam('controller', 'Foo\Controller\SomewhatController');
        $this->routeMatch->setParam('action', 'useful');

        $model = new ViewModel();
        $this->event->setResult($model);

        $this->listener->injectTemplate($this->event);

        $this->assertEquals('foo/somewhat/useful', $model->getTemplate());
    }

    public function testUsesModuleAndControllerOnlyIfNoActionInRouteMatch(): void
    {
        $this->routeMatch->setParam('controller', 'Foo\Controller\SomewhatController');

        $model = new ViewModel();
        $this->event->setResult($model);

        $this->listener->injectTemplate($this->event);

        $this->assertEquals('foo/somewhat', $model->getTemplate());
    }

    public function testNormalizesLiteralControllerNameIfNoNamespaceSeparatorPresent(): void
    {
        $this->routeMatch->setParam('controller', 'SomewhatController');

        $model = new ViewModel();
        $this->event->setResult($model);

        $this->listener->injectTemplate($this->event);

        $this->assertEquals('somewhat', $model->getTemplate());
    }

    public function testNormalizesNamesToLowercase(): void
    {
        $this->routeMatch->setParam('controller', 'Somewhat.DerivedController');
        $this->routeMatch->setParam('action', 'some-UberCool');

        $model = new ViewModel();
        $this->event->setResult($model);

        $this->listener->injectTemplate($this->event);

        $this->assertEquals('somewhat.derived/some-uber-cool', $model->getTemplate());
    }

    public function testLackOfViewModelInResultBypassesTemplateInjection(): void
    {
        $this->assertNull($this->listener->injectTemplate($this->event));
        $this->assertNull($this->event->getResult());
    }

    public function testBypassesTemplateInjectionIfResultViewModelAlreadyHasATemplate(): void
    {
        $this->routeMatch->setParam('controller', 'Foo\Controller\SomewhatController');
        $this->routeMatch->setParam('action', 'useful');

        $model = new ViewModel();
        $model->setTemplate('custom');
        $this->event->setResult($model);

        $this->listener->injectTemplate($this->event);

        $this->assertEquals('custom', $model->getTemplate());
    }

    public function testMapsSubNamespaceToSubDirectory(): void
    {
        $myViewModel  = new ViewModel();
        $myController = new SampleController();
        $this->event->setTarget($myController);
        $this->event->setResult($myViewModel);

        $this->listener->injectTemplate($this->event);

        $this->assertEquals('laminas-test/mvc/test-asset/sample', $myViewModel->getTemplate());
    }

    public function testControllerMatchedByMapIsInflected(): void
    {
        $this->routeMatch->setParam('controller', 'MappedNs\SubNs\Controller\Sample');
        $myViewModel = new ViewModel();

        $this->event->setResult($myViewModel);
        $this->listener->injectTemplate($this->event);

        $this->assertEquals('mapped-ns/sub-ns/sample', $myViewModel->getTemplate());

        $this->listener->setControllerMap(['LaminasTest' => true]);
        $myViewModel  = new ViewModel();
        $myController = new SampleController();
        $this->event->setTarget($myController);
        $this->event->setResult($myViewModel);

        $this->listener->injectTemplate($this->event);

        $this->assertEquals('laminas-test/mvc/test-asset/sample', $myViewModel->getTemplate());
    }

    public function testFullControllerNameMatchIsMapped(): void
    {
        $this->listener->setControllerMap([
            'Foo\Bar\Controller\IndexController' => 'string-value',
        ]);
        $template = $this->listener->mapController('Foo\Bar\Controller\IndexController');
        $this->assertEquals('string-value', $template);
    }

    public function testOnlyFullNamespaceMatchIsMapped(): void
    {
        $this->listener->setControllerMap([
            'Foo'     => 'foo-matched',
            'Foo\Bar' => 'foo-bar-matched',
        ]);
        $template = $this->listener->mapController('Foo\BarBaz\Controller\IndexController');
        $this->assertEquals('foo-matched/bar-baz/index', $template);
    }

    public function testControllerMapMatchedPrefixReplacedByStringValue(): void
    {
        $this->listener->setControllerMap([
            'Foo\Bar' => 'string-value',
        ]);
        $template = $this->listener->mapController('Foo\Bar\Controller\IndexController');
        $this->assertEquals('string-value/index', $template);
    }

    public function testControllerMapOnlyFullNamespaceMatches(): void
    {
        $this->listener->setControllerMap([
            'Foo'     => 'foo-matched',
            'Foo\Bar' => 'foo-bar-matched',
        ]);
        $template = $this->listener->mapController('Foo\BarBaz\Controller\IndexController');
        $this->assertEquals('foo-matched/bar-baz/index', $template);
    }

    public function testControllerMapRuleSetToFalseIsIgnored(): void
    {
        $this->listener->setControllerMap([
            'Foo'     => 'foo-matched',
            'Foo\Bar' => false,
        ]);
        $template = $this->listener->mapController('Foo\Bar\Controller\IndexController');
        $this->assertEquals('foo-matched/bar/index', $template);
    }

    public function testControllerMapMoreSpecificRuleMatchesFirst(): void
    {
        $this->listener->setControllerMap([
            'Foo'     => true,
            'Foo\Bar' => 'bar/baz',
        ]);
        $template = $this->listener->mapController('Foo\Bar\Controller\IndexController');
        $this->assertEquals('bar/baz/index', $template);

        $this->listener->setControllerMap([
            'Foo\Bar' => 'bar/baz',
            'Foo'     => true,
        ]);
        $template = $this->listener->mapController('Foo\Bar\Controller\IndexController');
        $this->assertEquals('bar/baz/index', $template);
    }

    public function testAttachesListenerAtExpectedPriority(): void
    {
        $events = new EventManager();
        $this->listener->attach($events);
        $this->assertListenerAtPriority(
            [$this->listener, 'injectTemplate'],
            -90,
            MvcEvent::EVENT_DISPATCH,
            $events
        );
    }

    public function testDetachesListeners(): void
    {
        $events = new EventManager();
        $this->listener->attach($events);
        $listeners = $this->getArrayOfListenersForEvent(MvcEvent::EVENT_DISPATCH, $events);
        $this->assertCount(1, $listeners);

        $this->listener->detach($events);
        $listeners = $this->getArrayOfListenersForEvent(MvcEvent::EVENT_DISPATCH, $events);
        $this->assertCount(0, $listeners);
    }

    public function testPrefersRouteMatchController(): void
    {
        $this->assertFalse($this->listener->isPreferRouteMatchController());
        $this->listener->setPreferRouteMatchController(true);
        $this->routeMatch->setParam('controller', 'Some\Other\Service\Namespace\Controller\Sample');
        $myViewModel  = new ViewModel();
        $myController = new SampleController();

        $this->event->setTarget($myController);
        $this->event->setResult($myViewModel);
        $this->listener->injectTemplate($this->event);

        $this->assertEquals('some/other/service/namespace/sample', $myViewModel->getTemplate());
    }

    public function testPrefersRouteMatchControllerWithRouteMatchAndControllerMap(): void
    {
        $this->assertFalse($this->listener->isPreferRouteMatchController());
        $controllerMap = [
            'Some\Other\Service\Namespace\Controller\Sample' => 'another/sample',
        ];

        $this->routeMatch->setParam('prefer_route_match_controller', true);
        $this->routeMatch->setParam('controller', 'Some\Other\Service\Namespace\Controller\Sample');

        $preferRouteMatchControllerRouteMatchConfig = $this->routeMatch->getParam(
            'prefer_route_match_controller',
            false
        );
        $this->listener->setPreferRouteMatchController($preferRouteMatchControllerRouteMatchConfig);
        $this->listener->setControllerMap($controllerMap);

        $myViewModel  = new ViewModel();
        $myController = new SampleController();

        $this->event->setTarget($myController);
        $this->event->setResult($myViewModel);
        $this->listener->injectTemplate($this->event);

        $this->assertEquals('another/sample', $myViewModel->getTemplate());
    }
}
