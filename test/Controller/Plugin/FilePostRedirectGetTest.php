<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Controller\Plugin;

use Laminas\Form\Form;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\InputFilter\InputFilter;
use Laminas\Mvc\ModuleRouteListener;
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
class FilePostRedirectGetTest extends TestCase
{
    public $form;
    public $controller;
    public $event;
    public $request;
    public $response;

    public function setUp()
    {
        $this->form = new Form();

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

        $router->addRoute('ctl', SegmentRoute::factory(array(
            'route' => '/ctl/:controller',
            'defaults' => array(
                '__NAMESPACE__' => 'LaminasTest\Mvc\Controller\TestAsset',
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
        $this->controller->flashMessenger()->setSessionManager($this->sessionManager);
    }

    public function testReturnsFalseOnIntialGet()
    {
        $result    = $this->controller->dispatch($this->request, $this->response);
        $prgResult = $this->controller->fileprg($this->form, 'home');

        $this->assertFalse($prgResult);
    }

    public function testRedirectsToUrlOnPost()
    {
        $this->request->setMethod('POST');
        $this->request->setPost(new Parameters(array(
            'postval1' => 'value'
        )));

        $this->controller->dispatch($this->request, $this->response);
        $prgResultUrl = $this->controller->fileprg($this->form, '/test/getPage', true);

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

        $this->controller->dispatch($this->request, $this->response);
        $prgResultRoute = $this->controller->fileprg($this->form, 'home');

        $this->assertInstanceOf('Laminas\Http\Response', $prgResultRoute);
        $this->assertTrue($prgResultRoute->getHeaders()->has('Location'));
        $this->assertEquals('/', $prgResultRoute->getHeaders()->get('Location')->getUri());
        $this->assertEquals(303, $prgResultRoute->getStatusCode());
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

        $this->controller->dispatch($this->request, $this->response);
        $this->controller->fileprg($this->form, 'some/route');
    }

    public function testNullRouteUsesMatchedRouteName()
    {
        $this->controller->getEvent()->getRouteMatch()->setMatchedRouteName('home');

        $this->request->setMethod('POST');
        $this->request->setPost(new Parameters(array(
            'postval1' => 'value1'
        )));

        $this->controller->dispatch($this->request, $this->response);
        $prgResultRoute = $this->controller->fileprg($this->form);

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
        $prgResultRoute = $this->controller->fileprg($this->form);

        $this->assertInstanceOf('Laminas\Http\Response', $prgResultRoute);
        $this->assertTrue($prgResultRoute->getHeaders()->has('Location'));
        $this->assertEquals('/foo/1', $prgResultRoute->getHeaders()->get('Location')->getUri());
        $this->assertEquals(303, $prgResultRoute->getStatusCode());
    }

    public function testReturnsPostOnRedirectGet()
    {
        // Do POST
        $params = array(
            'postval1' => 'value'
        );
        $this->request->setMethod('POST');
        $this->request->setPost(new Parameters($params));

        $this->form->add(array(
            'name' => 'postval1'
        ));

        $this->controller->dispatch($this->request, $this->response);
        $prgResultUrl = $this->controller->fileprg($this->form, '/test/getPage', true);

        $this->assertInstanceOf('Laminas\Http\Response', $prgResultUrl);
        $this->assertTrue($prgResultUrl->getHeaders()->has('Location'));
        $this->assertEquals('/test/getPage', $prgResultUrl->getHeaders()->get('Location')->getUri());
        $this->assertEquals(303, $prgResultUrl->getStatusCode());

        // Do GET
        $this->request = new Request();
        $this->controller->dispatch($this->request, $this->response);
        $prgResult = $this->controller->fileprg($this->form, '/test/getPage', true);

        $this->assertEquals($params, $prgResult);
        $this->assertEquals($params['postval1'], $this->form->get('postval1')->getValue());

        // Do GET again to make sure data is empty
        $this->request = new Request();
        $this->controller->dispatch($this->request, $this->response);
        $prgResult = $this->controller->fileprg($this->form, '/test/getPage', true);

        $this->assertFalse($prgResult);
    }

    public function testAppliesFormErrorsOnPostRedirectGet()
    {
        // Do POST
        $params = array();
        $this->request->setMethod('POST');
        $this->request->setPost(new Parameters($params));

        $this->form->add(array(
            'name' => 'postval1'
        ));
        $inputFilter = new InputFilter();
        $inputFilter->add(array(
            'name'     => 'postval1',
            'required' => true,
        ));
        $this->form->setInputFilter($inputFilter);

        $this->controller->dispatch($this->request, $this->response);
        $prgResultUrl = $this->controller->fileprg($this->form, '/test/getPage', true);
        $this->assertInstanceOf('Laminas\Http\Response', $prgResultUrl);
        $this->assertTrue($prgResultUrl->getHeaders()->has('Location'));
        $this->assertEquals('/test/getPage', $prgResultUrl->getHeaders()->get('Location')->getUri());
        $this->assertEquals(303, $prgResultUrl->getStatusCode());

        // Do GET
        $this->request = new Request();
        $this->controller->dispatch($this->request, $this->response);
        $prgResult = $this->controller->fileprg($this->form, '/test/getPage', true);
        $messages  = $this->form->getMessages();

        $this->assertEquals($params, $prgResult);
        $this->assertNotEmpty($messages['postval1']['isEmpty']);
    }

    public function testReuseMatchedParametersWithSegmentController()
    {
        $expects = '/ctl/sample';
        $this->request->setMethod('POST');
        $this->request->setUri($expects);
        $this->request->setPost(new Parameters(array(
            'postval1' => 'value1'
        )));

        $routeMatch = $this->event->getRouter()->match($this->request);
        $this->event->setRouteMatch($routeMatch);

        $moduleRouteListener = new ModuleRouteListener;
        $moduleRouteListener->onRoute($this->event);

        $this->controller->dispatch($this->request, $this->response);
        $prgResultRoute = $this->controller->fileprg($this->form);

        $this->assertInstanceOf('Laminas\Http\Response', $prgResultRoute);
        $this->assertTrue($prgResultRoute->getHeaders()->has('Location'));
        $this->assertEquals($expects, $prgResultRoute->getHeaders()->get('Location')->getUri() , 'redirect to the same url');
        $this->assertEquals(303, $prgResultRoute->getStatusCode());
    }
}
