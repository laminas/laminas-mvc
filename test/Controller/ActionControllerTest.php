<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\Controller;

use Laminas\EventManager\EventManager;
use Laminas\EventManager\SharedEventManager;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\Controller\AbstractController;
use Laminas\Mvc\Controller\Plugin\Url;
use Laminas\Mvc\Controller\PluginManager;
use Laminas\Mvc\InjectApplicationEventInterface;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\RouteMatch;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Stdlib\DispatchableInterface;
use Laminas\View\Model\ModelInterface;
use LaminasTest\Mvc\Controller\TestAsset\SampleController;
use LaminasTest\Mvc\Controller\TestAsset\SampleInterface;
use PHPUnit\Framework\TestCase;

use function method_exists;
use function var_export;

class ActionControllerTest extends TestCase
{
    public AbstractController|null $controller;
    public MvcEvent|null $event;
    public Request|null $request;
    public Response|null $response;
    private RouteMatch $routeMatch;
    private SharedEventManager $sharedEvents;
    private EventManager $events;

    public function setUp(): void
    {
        $this->controller = new SampleController();
        $this->request    = new Request();
        $this->response   = null;
        $this->routeMatch = new RouteMatch(['controller' => 'controller-sample']);
        $this->event      = new MvcEvent();
        $this->event->setRouteMatch($this->routeMatch);
        $this->controller->setEvent($this->event);

        $this->sharedEvents = new SharedEventManager();
        $this->events       = $this->createEventManager($this->sharedEvents);
        $this->controller->setEventManager($this->events);
    }

    protected function createEventManager(SharedEventManagerInterface $sharedManager): EventManager
    {
        return new EventManager($sharedManager);
    }

    public function testDispatchInvokesNotFoundActionWhenNoActionPresentInRouteMatch(): void
    {
        $result   = $this->controller->dispatch($this->request, $this->response);
        $response = $this->controller->getResponse();
        self::assertEquals(404, $response->getStatusCode());
        self::assertInstanceOf(ModelInterface::class, $result);
        self::assertEquals('content', $result->captureTo());
        $vars = $result->getVariables();
        self::assertArrayHasKey('content', $vars, var_export($vars, true));
        self::assertStringContainsString('Page not found', $vars['content']);
    }

    public function testDispatchInvokesNotFoundActionWhenInvalidActionPresentInRouteMatch(): void
    {
        $this->routeMatch->setParam('action', 'totally-made-up-action');
        $result   = $this->controller->dispatch($this->request, $this->response);
        $response = $this->controller->getResponse();
        self::assertEquals(404, $response->getStatusCode());
        self::assertInstanceOf(ModelInterface::class, $result);
        self::assertEquals('content', $result->captureTo());
        $vars = $result->getVariables();
        self::assertArrayHasKey('content', $vars, var_export($vars, true));
        self::assertStringContainsString('Page not found', $vars['content']);
    }

    public function testDispatchInvokesProvidedActionWhenMethodExists(): void
    {
        $this->routeMatch->setParam('action', 'test');
        $result = $this->controller->dispatch($this->request, $this->response);
        self::assertTrue(isset($result['content']));
        self::assertStringContainsString('test', $result['content']);
    }

    public function testDispatchCallsActionMethodBasedOnNormalizingAction(): void
    {
        $this->routeMatch->setParam('action', 'test.some-strangely_separated.words');
        $result = $this->controller->dispatch($this->request, $this->response);
        self::assertTrue(isset($result['content']));
        self::assertStringContainsString('Test Some Strangely Separated Words', $result['content']);
    }

    public function testShortCircuitsBeforeActionIfPreDispatchReturnsAResponse(): void
    {
        $response = new Response();
        $response->setContent('short circuited!');
        $this->controller->getEventManager()->attach(
            MvcEvent::EVENT_DISPATCH,
            static fn($e): Response => $response,
            100
        );
        $result = $this->controller->dispatch($this->request, $this->response);
        self::assertSame($response, $result);
    }

    public function testPostDispatchEventAllowsReplacingResponse(): void
    {
        $response = new Response();
        $response->setContent('short circuited!');
        $this->controller->getEventManager()->attach(
            MvcEvent::EVENT_DISPATCH,
            static fn($e): Response => $response,
            -10
        );
        $result = $this->controller->dispatch($this->request, $this->response);
        self::assertSame($response, $result);
    }

    public function testEventManagerListensOnDispatchableInterfaceByDefault(): void
    {
        $response = new Response();
        $response->setContent('short circuited!');
        $sharedEvents = $this->controller->getEventManager()->getSharedManager();
        $sharedEvents->attach(
            DispatchableInterface::class,
            MvcEvent::EVENT_DISPATCH,
            static fn($e): Response => $response,
            10
        );
        $result = $this->controller->dispatch($this->request, $this->response);
        self::assertSame($response, $result);
    }

    public function testEventManagerListensOnActionControllerClassByDefault(): void
    {
        $response = new Response();
        $response->setContent('short circuited!');
        $sharedEvents = $this->controller->getEventManager()->getSharedManager();
        $sharedEvents->attach(
            AbstractActionController::class,
            MvcEvent::EVENT_DISPATCH,
            static fn($e): Response => $response,
            10
        );
        $result = $this->controller->dispatch($this->request, $this->response);
        self::assertSame($response, $result);
    }

    public function testEventManagerListensOnClassNameByDefault(): void
    {
        $response = new Response();
        $response->setContent('short circuited!');
        $sharedEvents = $this->controller->getEventManager()->getSharedManager();
        $sharedEvents->attach(
            $this->controller::class,
            MvcEvent::EVENT_DISPATCH,
            static fn($e): Response => $response,
            10
        );
        $result = $this->controller->dispatch($this->request, $this->response);
        self::assertSame($response, $result);
    }

    public function testEventManagerListensOnInterfaceName(): void
    {
        $response = new Response();
        $response->setContent('short circuited!');
        $sharedEvents = $this->controller->getEventManager()->getSharedManager();
        $sharedEvents->attach(
            SampleInterface::class,
            MvcEvent::EVENT_DISPATCH,
            static fn($e): Response => $response,
            10
        );
        $result = $this->controller->dispatch($this->request, $this->response);
        self::assertSame($response, $result);
    }

    public function testDispatchInjectsEventIntoController(): void
    {
        $this->controller->dispatch($this->request, $this->response);
        $event = $this->controller->getEvent();
        self::assertNotNull($event);
        self::assertSame($this->event, $event);
    }

    public function testControllerIsEventAware(): void
    {
        self::assertInstanceOf(InjectApplicationEventInterface::class, $this->controller);
    }

    public function testControllerIsPluggable(): void
    {
        self::assertTrue(method_exists($this->controller, 'plugin'));
    }

    public function testComposesPluginManagerByDefault(): void
    {
        $plugins = $this->controller->getPluginManager();
        self::assertInstanceOf(PluginManager::class, $plugins);
    }

    public function testPluginManagerComposesController(): void
    {
        $plugins    = $this->controller->getPluginManager();
        $controller = $plugins->getController();
        self::assertSame($this->controller, $controller);
    }

    public function testInjectingPluginManagerSetsControllerWhenPossible(): void
    {
        $plugins = new PluginManager(new ServiceManager());
        self::assertNull($plugins->getController());
        $this->controller->setPluginManager($plugins);
        self::assertSame($this->controller, $plugins->getController());
        self::assertSame($plugins, $this->controller->getPluginManager());
    }

    public function testMethodOverloadingShouldReturnPluginWhenFound(): void
    {
        $plugin = $this->controller->url();
        self::assertInstanceOf(Url::class, $plugin);
    }

    public function testMethodOverloadingShouldInvokePluginAsFunctorIfPossible(): void
    {
        $model = $this->event->getViewModel();
        $this->controller->layout('alternate/layout');
        self::assertEquals('alternate/layout', $model->getTemplate());
    }
}
