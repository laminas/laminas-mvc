<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Mvc\Controller;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Zend\EventManager\EventManager;
use Zend\EventManager\SharedEventManager;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\Http\Request;
use Zend\Http\Response;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Mvc\Controller\Plugin\Url;
use Zend\Mvc\Controller\PluginManager;
use Zend\Mvc\InjectApplicationEventInterface;
use Zend\Mvc\MvcEvent;
use Zend\Router\RouteMatch;
use Zend\ServiceManager\ServiceManager;
use Zend\Stdlib\DispatchableInterface;
use Zend\View\Model\ModelInterface;
use ZendTest\Mvc\Controller\TestAsset\SampleController;
use ZendTest\Mvc\Controller\TestAsset\SampleInterface;

class ActionControllerTest extends TestCase
{
    public $controller;
    public $event;
    public $request;
    public $response;

    public function setUp()
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

    /**
     * @param SharedEventManager
     * @return EventManager
     */
    protected function createEventManager(SharedEventManagerInterface $sharedManager)
    {
        return new EventManager($sharedManager);
    }

    public function testDispatchInvokesNotFoundActionWhenNoActionPresentInRouteMatch()
    {
        $result = $this->controller->dispatch($this->request, $this->response);
        $response = $this->controller->getResponse();
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertInstanceOf(ModelInterface::class, $result);
        $this->assertEquals('content', $result->captureTo());
        $vars = $result->getVariables();
        $this->assertArrayHasKey('content', $vars, var_export($vars, 1));
        $this->assertContains('Page not found', $vars['content']);
    }

    public function testDispatchInvokesNotFoundActionWhenInvalidActionPresentInRouteMatch()
    {
        $this->routeMatch->setParam('action', 'totally-made-up-action');
        $result = $this->controller->dispatch($this->request, $this->response);
        $response = $this->controller->getResponse();
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertInstanceOf(ModelInterface::class, $result);
        $this->assertEquals('content', $result->captureTo());
        $vars = $result->getVariables();
        $this->assertArrayHasKey('content', $vars, var_export($vars, 1));
        $this->assertContains('Page not found', $vars['content']);
    }

    public function testDispatchInvokesProvidedActionWhenMethodExists()
    {
        $this->routeMatch->setParam('action', 'test');
        $result = $this->controller->dispatch($this->request, $this->response);
        $this->assertTrue(isset($result['content']));
        $this->assertContains('test', $result['content']);
    }

    public function testDispatchCallsActionMethodBasedOnNormalizingAction()
    {
        $this->routeMatch->setParam('action', 'test.some-strangely_separated.words');
        $result = $this->controller->dispatch($this->request, $this->response);
        $this->assertTrue(isset($result['content']));
        $this->assertContains('Test Some Strangely Separated Words', $result['content']);
    }

    public function testShortCircuitsBeforeActionIfPreDispatchReturnsAResponse()
    {
        $response = new Response();
        $response->setContent('short circuited!');
        $this->controller->getEventManager()->attach(MvcEvent::EVENT_DISPATCH, function ($e) use ($response) {
            return $response;
        }, 100);
        $result = $this->controller->dispatch($this->request, $this->response);
        $this->assertSame($response, $result);
    }

    public function testPostDispatchEventAllowsReplacingResponse()
    {
        $response = new Response();
        $response->setContent('short circuited!');
        $this->controller->getEventManager()->attach(MvcEvent::EVENT_DISPATCH, function ($e) use ($response) {
            return $response;
        }, -10);
        $result = $this->controller->dispatch($this->request, $this->response);
        $this->assertSame($response, $result);
    }

    public function testEventManagerListensOnDispatchableInterfaceByDefault()
    {
        $response = new Response();
        $response->setContent('short circuited!');
        $sharedEvents = $this->controller->getEventManager()->getSharedManager();
        $sharedEvents->attach(DispatchableInterface::class, MvcEvent::EVENT_DISPATCH, function ($e) use ($response) {
            return $response;
        }, 10);
        $result = $this->controller->dispatch($this->request, $this->response);
        $this->assertSame($response, $result);
    }

    public function testEventManagerListensOnActionControllerClassByDefault()
    {
        $response = new Response();
        $response->setContent('short circuited!');
        $sharedEvents = $this->controller->getEventManager()->getSharedManager();
        $sharedEvents->attach(AbstractActionController::class, MvcEvent::EVENT_DISPATCH, function ($e) use ($response) {
            return $response;
        }, 10);
        $result = $this->controller->dispatch($this->request, $this->response);
        $this->assertSame($response, $result);
    }

    public function testEventManagerListensOnClassNameByDefault()
    {
        $response = new Response();
        $response->setContent('short circuited!');
        $sharedEvents = $this->controller->getEventManager()->getSharedManager();
        $sharedEvents->attach(get_class($this->controller), MvcEvent::EVENT_DISPATCH, function ($e) use ($response) {
            return $response;
        }, 10);
        $result = $this->controller->dispatch($this->request, $this->response);
        $this->assertSame($response, $result);
    }

    public function testEventManagerListensOnInterfaceName()
    {
        $response = new Response();
        $response->setContent('short circuited!');
        $sharedEvents = $this->controller->getEventManager()->getSharedManager();
        $sharedEvents->attach(SampleInterface::class, MvcEvent::EVENT_DISPATCH, function ($e) use ($response) {
            return $response;
        }, 10);
        $result = $this->controller->dispatch($this->request, $this->response);
        $this->assertSame($response, $result);
    }

    public function testDispatchInjectsEventIntoController()
    {
        $this->controller->dispatch($this->request, $this->response);
        $event = $this->controller->getEvent();
        $this->assertNotNull($event);
        $this->assertSame($this->event, $event);
    }

    public function testControllerIsEventAware()
    {
        $this->assertInstanceOf(InjectApplicationEventInterface::class, $this->controller);
    }

    public function testControllerIsPluggable()
    {
        $this->assertTrue(method_exists($this->controller, 'plugin'));
    }

    public function testComposesPluginManagerByDefault()
    {
        $plugins = $this->controller->getPluginManager();
        $this->assertInstanceOf(PluginManager::class, $plugins);
    }

    public function testPluginManagerComposesController()
    {
        $plugins    = $this->controller->getPluginManager();
        $controller = $plugins->getController();
        $this->assertSame($this->controller, $controller);
    }

    public function testInjectingPluginManagerSetsControllerWhenPossible()
    {
        $plugins = new PluginManager(new ServiceManager());
        $this->assertNull($plugins->getController());
        $this->controller->setPluginManager($plugins);
        $this->assertSame($this->controller, $plugins->getController());
        $this->assertSame($plugins, $this->controller->getPluginManager());
    }

    public function testMethodOverloadingShouldReturnPluginWhenFound()
    {
        $plugin = $this->controller->url();
        $this->assertInstanceOf(Url::class, $plugin);
    }

    public function testMethodOverloadingShouldInvokePluginAsFunctorIfPossible()
    {
        $model = $this->event->getViewModel();
        $this->controller->layout('alternate/layout');
        $this->assertEquals('alternate/layout', $model->getTemplate());
    }
}
