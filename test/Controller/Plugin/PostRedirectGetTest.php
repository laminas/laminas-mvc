<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Controller\Plugin;

use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\MvcEvent;
use Laminas\Mvc\Router\Http\Literal as LiteralRoute;
use Laminas\Mvc\Router\Http\Segment as SegmentRoute;
use Laminas\Mvc\Router\RouteMatch;
use Laminas\Mvc\Router\SimpleRouteStack;
use Laminas\Stdlib\Parameters;
use LaminasTest\Mvc\Controller\TestAsset\SampleController;
use LaminasTest\Session\TestAsset\TestManager as SessionManager;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * @category   Laminas
 * @package    Laminas_Mvc
 * @subpackage UnitTests
 */
class PostRedirectGetTest extends TestCase
{
    public $controller;
    public $event;
    public $request;
    public $response;

    public function setUp()
    {
        $router = new SimpleRouteStack;
        $router->addRoute('home', LiteralRoute::factory(array(
            'route'    => '/',
            'defaults' => array(
                'controller' => 'LaminasTest\Mvc\Controller\TestAsset\SampleController',
            )
        )));

        $router->addRoute('sub', SegmentRoute::factory(array(
            'route' => '/foo/:param',
            'defaults' => array(
                'param' => 1
            )
        )));

        $this->controller = new SampleController();
        $this->request    = new Request();
        $this->event      = new MvcEvent();
        $this->routeMatch = new RouteMatch(array('controller' => 'controller-sample', 'action' => 'postPage'));

        $this->event->setRequest($this->request);
        $this->event->setRouteMatch($this->routeMatch);
        $this->event->setRouter($router);

        $this->sessionManager = new SessionManager();
        $this->sessionManager->destroy();

        $this->controller->setEvent($this->event);
        $plugins = $this->controller->getPluginManager();
        $plugins->get('flashmessenger')->setSessionManager($this->sessionManager);
    }

    public function testReturnsFalseOnIntialGet()
    {
        $result    = $this->controller->dispatch($this->request, $this->response);
        $prgResult = $this->controller->prg('home');

        $this->assertFalse($prgResult);
    }

    public function testRedirectsToUrlOnPost()
    {
        $this->request->setMethod('POST');
        $this->request->setPost(new Parameters(array(
            'postval1' => 'value'
        )));

        $result         = $this->controller->dispatch($this->request, $this->response);
        $prgResultUrl   = $this->controller->prg('/test/getPage', true);

        $this->assertInstanceOf('Laminas\Http\Response', $prgResultUrl);
        $this->assertTrue($prgResultUrl->getHeaders()->has('Location'));
        $this->assertEquals('/test/getPage', $prgResultUrl->getHeaders()->get('Location')->getUri());
        $this->assertEquals(303, $prgResultUrl->getStatusCode());
    }

    public function testRedirectsToRouteOnPost()
    {
        $this->request->setMethod('POST');
        $this->request->setPost(new Parameters(array(
            'postval1' => 'value1'
        )));

        $result         = $this->controller->dispatch($this->request, $this->response);
        $prgResultRoute = $this->controller->prg('home');

        $this->assertInstanceOf('Laminas\Http\Response', $prgResultRoute);
        $this->assertTrue($prgResultRoute->getHeaders()->has('Location'));
        $this->assertEquals('/', $prgResultRoute->getHeaders()->get('Location')->getUri());
        $this->assertEquals(303, $prgResultRoute->getStatusCode());
    }

    public function testReturnsPostOnRedirectGet()
    {
        $params = array(
            'postval1' => 'value1'
        );
        $this->request->setMethod('POST');
        $this->request->setPost(new Parameters($params));

        $result         = $this->controller->dispatch($this->request, $this->response);
        $prgResultRoute = $this->controller->prg('home');

        $this->assertInstanceOf('Laminas\Http\Response', $prgResultRoute);
        $this->assertTrue($prgResultRoute->getHeaders()->has('Location'));
        $this->assertEquals('/', $prgResultRoute->getHeaders()->get('Location')->getUri());
        $this->assertEquals(303, $prgResultRoute->getStatusCode());

        // Do GET
        $this->request = new Request();
        $this->controller->dispatch($this->request, $this->response);
        $prgResult = $this->controller->prg('home');

        $this->assertEquals($params, $prgResult);

        // Do GET again to make sure data is empty
        $this->request = new Request();
        $this->controller->dispatch($this->request, $this->response);
        $prgResult = $this->controller->prg('home');

        $this->assertFalse($prgResult);
    }

    /**
     * @expectedException Laminas\Mvc\Exception\RuntimeException
     */
    public function testThrowsExceptionOnRouteWithoutRouter()
    {
        $controller = $this->controller;
        $controller = $controller->getEvent()->setRouter(new SimpleRouteStack);

        $this->request->setMethod('POST');
        $this->request->setPost(new Parameters(array(
            'postval1' => 'value'
        )));

        $result = $this->controller->dispatch($this->request, $this->response);
        $this->controller->prg('some/route');
    }

    public function testNullRouteUsesMatchedRouteName()
    {
        $this->controller->getEvent()->getRouteMatch()->setMatchedRouteName('home');

        $this->request->setMethod('POST');
        $this->request->setPost(new Parameters(array(
            'postval1' => 'value1'
        )));

        $result         = $this->controller->dispatch($this->request, $this->response);
        $prgResultRoute = $this->controller->prg();

        $this->assertInstanceOf('Laminas\Http\Response', $prgResultRoute);
        $this->assertTrue($prgResultRoute->getHeaders()->has('Location'));
        $this->assertEquals('/', $prgResultRoute->getHeaders()->get('Location')->getUri());
        $this->assertEquals(303, $prgResultRoute->getStatusCode());
    }

    public function testReuseMatchedParameters()
    {
        $this->controller->getEvent()->getRouteMatch()->setMatchedRouteName('sub');

        $this->request->setMethod('POST');
        $this->request->setPost(new Parameters(array(
            'postval1' => 'value1'
        )));

        $this->controller->dispatch($this->request, $this->response);
        $prgResultRoute = $this->controller->prg();

        $this->assertInstanceOf('Laminas\Http\Response', $prgResultRoute);
        $this->assertTrue($prgResultRoute->getHeaders()->has('Location'));
        $this->assertEquals('/foo/1', $prgResultRoute->getHeaders()->get('Location')->getUri());
        $this->assertEquals(303, $prgResultRoute->getStatusCode());
    }
}
