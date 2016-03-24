<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Mvc\Controller\Plugin;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\Form\Element\Collection;
use Zend\Form\Form;
use Zend\Http\Request;
use Zend\Http\Response;
use Zend\InputFilter\InputFilter;
use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use Zend\Router\Http\Literal as LiteralRoute;
use Zend\Router\Http\Segment as SegmentRoute;
use Zend\Router\RouteMatch;
use Zend\Router\SimpleRouteStack;
use Zend\Stdlib\Parameters;
use Zend\Validator\NotEmpty;
use ZendTest\Mvc\Controller\TestAsset\SampleController;

class FilePostRedirectGetTest extends TestCase
{
    public $form;
    public $controller;
    public $event;
    public $request;
    public $response;
    public $collection;

    public function setUp()
    {
        $this->markTestIncomplete('Re-enable when zend-form has been updated to zend-servicemanager v3');

        $this->form = new Form();

        $this->collection = new Collection('links', [
                'count' => 1,
                'allow_add' => true,
                'target_element' => [
                    'type' => 'ZendTest\Mvc\Controller\Plugin\TestAsset\LinksFieldset',
                ],
        ]);

        $router = new SimpleRouteStack;
        $router->addRoute('home', LiteralRoute::factory([
            'route'    => '/',
            'defaults' => [
                'controller' => 'ZendTest\Mvc\Controller\TestAsset\SampleController',
            ]
        ]));

        $router->addRoute('sub', SegmentRoute::factory([
            'route' => '/foo/:param',
            'defaults' => [
                'param' => 1
            ]
        ]));

        $router->addRoute('ctl', SegmentRoute::factory([
            'route' => '/ctl/:controller',
            'defaults' => [
                '__NAMESPACE__' => 'ZendTest\Mvc\Controller\TestAsset',
            ]
        ]));

        $this->controller = new SampleController();
        $this->request    = new Request();
        $this->event      = new MvcEvent();
        $this->routeMatch = new RouteMatch(['controller' => 'controller-sample', 'action' => 'postPage']);

        $this->event->setRequest($this->request);
        $this->event->setRouteMatch($this->routeMatch);
        $this->event->setRouter($router);

        $this->controller->setEvent($this->event);
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
        $this->request->setPost(new Parameters([
            'postval1' => 'value'
        ]));

        $this->controller->dispatch($this->request, $this->response);
        $prgResultUrl = $this->controller->fileprg($this->form, '/test/getPage', true);

        $this->assertInstanceOf('Zend\Http\Response', $prgResultUrl);
        $this->assertTrue($prgResultUrl->getHeaders()->has('Location'));
        $this->assertEquals('/test/getPage', $prgResultUrl->getHeaders()->get('Location')->getUri());
        $this->assertEquals(303, $prgResultUrl->getStatusCode());
    }

    public function testRedirectsToRouteOnPost()
    {
        $this->request->setMethod('POST');
        $this->request->setPost(new Parameters([
            'postval1' => 'value1'
        ]));

        $this->controller->dispatch($this->request, $this->response);
        $prgResultRoute = $this->controller->fileprg($this->form, 'home');

        $this->assertInstanceOf('Zend\Http\Response', $prgResultRoute);
        $this->assertTrue($prgResultRoute->getHeaders()->has('Location'));
        $this->assertEquals('/', $prgResultRoute->getHeaders()->get('Location')->getUri());
        $this->assertEquals(303, $prgResultRoute->getStatusCode());
    }

    /**
     * @expectedException Zend\Mvc\Exception\RuntimeException
     */
    public function testThrowsExceptionOnRouteWithoutRouter()
    {
        $controller = $this->controller;
        $controller = $controller->getEvent()->setRouter(new SimpleRouteStack);

        $this->request->setMethod('POST');
        $this->request->setPost(new Parameters([
            'postval1' => 'value'
        ]));

        $this->controller->dispatch($this->request, $this->response);
        $this->controller->fileprg($this->form, 'some/route');
    }

    public function testNullRouteUsesMatchedRouteName()
    {
        $this->controller->getEvent()->getRouteMatch()->setMatchedRouteName('home');

        $this->request->setMethod('POST');
        $this->request->setPost(new Parameters([
            'postval1' => 'value1'
        ]));

        $this->controller->dispatch($this->request, $this->response);
        $prgResultRoute = $this->controller->fileprg($this->form);

        $this->assertInstanceOf('Zend\Http\Response', $prgResultRoute);
        $this->assertTrue($prgResultRoute->getHeaders()->has('Location'));
        $this->assertEquals('/', $prgResultRoute->getHeaders()->get('Location')->getUri());
        $this->assertEquals(303, $prgResultRoute->getStatusCode());
    }

    public function testReuseMatchedParameters()
    {
        $this->controller->getEvent()->getRouteMatch()->setMatchedRouteName('sub');

        $this->request->setMethod('POST');
        $this->request->setPost(new Parameters([
            'postval1' => 'value1'
        ]));

        $this->controller->dispatch($this->request, $this->response);
        $prgResultRoute = $this->controller->fileprg($this->form);

        $this->assertInstanceOf('Zend\Http\Response', $prgResultRoute);
        $this->assertTrue($prgResultRoute->getHeaders()->has('Location'));
        $this->assertEquals('/foo/1', $prgResultRoute->getHeaders()->get('Location')->getUri());
        $this->assertEquals(303, $prgResultRoute->getStatusCode());
    }

    public function testReturnsPostOnRedirectGet()
    {
        // Do POST
        $params = [
            'postval1' => 'value'
        ];
        $this->request->setMethod('POST');
        $this->request->setPost(new Parameters($params));

        $this->form->add([
            'name' => 'postval1'
        ]);

        $this->controller->dispatch($this->request, $this->response);
        $prgResultUrl = $this->controller->fileprg($this->form, '/test/getPage', true);

        $this->assertInstanceOf('Zend\Http\Response', $prgResultUrl);
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
        $params = [];
        $this->request->setMethod('POST');
        $this->request->setPost(new Parameters($params));

        $this->form->add([
            'name' => 'postval1'
        ]);
        $inputFilter = new InputFilter();
        $inputFilter->add([
            'name'     => 'postval1',
            'required' => true,
        ]);
        $this->form->setInputFilter($inputFilter);

        $this->controller->dispatch($this->request, $this->response);
        $prgResultUrl = $this->controller->fileprg($this->form, '/test/getPage', true);
        $this->assertInstanceOf('Zend\Http\Response', $prgResultUrl);
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
        $this->request->setPost(new Parameters([
            'postval1' => 'value1'
        ]));

        $routeMatch = $this->event->getRouter()->match($this->request);
        $this->event->setRouteMatch($routeMatch);

        $moduleRouteListener = new ModuleRouteListener;
        $moduleRouteListener->onRoute($this->event);

        $this->controller->dispatch($this->request, $this->response);
        $prgResultRoute = $this->controller->fileprg($this->form);

        $this->assertInstanceOf('Zend\Http\Response', $prgResultRoute);
        $this->assertTrue($prgResultRoute->getHeaders()->has('Location'));
        $this->assertEquals($expects, $prgResultRoute->getHeaders()->get('Location')->getUri(), 'redirect to the same url');
        $this->assertEquals(303, $prgResultRoute->getStatusCode());
    }

    public function testCollectionInputFilterIsInitializedBeforePluginRetrievesIt()
    {
        $fieldset = new TestAsset\InputFilterProviderFieldset();
        $collectionSpec = [
            'name' => 'test_collection',
            'type' => 'collection',
            'options' => [
                'target_element' => $fieldset
            ],
        ];

        $form = new Form();
        $form->add($collectionSpec);

        $postData = [
            'test_collection' => [
                [
                    'test_field' => 'foo'
                ],
                [
                    'test_field' => 'bar'
                ]
            ]
        ];

        // test POST
        $request = new Request();
        $request->setMethod('POST');
        $request->setPost(new Parameters($postData));
        $this->controller->dispatch($request, $this->response);

        $this->controller->fileprg($form, '/someurl', true);

        $data = $form->getData();

        $this->assertArrayHasKey(0, $data['test_collection']);
        $this->assertArrayHasKey(1, $data['test_collection']);

        $this->assertSame('FOO', $data['test_collection'][0]['test_field']);
        $this->assertSame('BAR', $data['test_collection'][1]['test_field']);

        // now test GET with a brand new form instance
        $form = new Form();
        $form->add($collectionSpec);

        $request = new Request();
        $this->controller->dispatch($request, $this->response);

        $this->controller->fileprg($form, '/someurl', true);

        $data = $form->getData();

        $this->assertArrayHasKey(0, $data['test_collection']);
        $this->assertArrayHasKey(1, $data['test_collection']);

        $this->assertSame('FOO', $data['test_collection'][0]['test_field']);
        $this->assertSame('BAR', $data['test_collection'][1]['test_field']);
    }

    public function testCorrectInputDataMerging()
    {
        require_once __DIR__ . '/TestAsset/DisablePhpUploadChecks.php';
        require_once __DIR__ . '/TestAsset/DisablePhpMoveUploadedFileChecks.php';

        $form = new Form();
        $form->add([
            'name' => 'collection',
            'type' => 'collection',
            'options' => [
                'target_element' => new TestAsset\TestFieldset('target'),
                'count' => 2,
            ]
        ]);

        copy(__DIR__ . '/TestAsset/nullfile', __DIR__ . '/TestAsset/nullfile_copy');

        $request = $this->request;
        $request->setMethod('POST');
        $request->setPost(new Parameters([
            'collection' => [
                0 => [
                    'text' => 'testvalue1',
                ],
                1 => [
                    'text' => '',
                ]
            ]
        ]));
        $request->setFiles(new Parameters([
            'collection' => [
                0 => [
                    'file' => [
                        'name' => 'test.jpg',
                        'type' => 'image/jpeg',
                        'size' => 20480,
                        'tmp_name' => __DIR__ . '/TestAsset/nullfile_copy',
                        'error' => UPLOAD_ERR_OK
                    ],
                ],
            ]
        ]));

        $this->controller->dispatch($this->request, $this->response);
        $this->controller->fileprg($form, '/test/getPage', true);

        $this->assertFalse($form->isValid());
        $data = $form->getData();

        $this->assertEquals([
            'collection' => [
                0 => [
                    'text' => 'testvalue1',
                    'file' => [
                        'name' => 'test.jpg',
                        'type' => 'image/jpeg',
                        'size' => 20480,
                        'tmp_name' => __DIR__ . DIRECTORY_SEPARATOR . 'TestAsset' . DIRECTORY_SEPARATOR . 'testfile.jpg',
                        'error' => 0
                    ],
                ],
                1 => [
                    'text' => null,
                    'file' => null,
                ]
            ]
        ], $data);

        $this->assertFileExists($data['collection'][0]['file']['tmp_name']);

        unlink($data['collection'][0]['file']['tmp_name']);

        $messages = $form->getMessages();
        $this->assertTrue(isset($messages['collection'][1]['text'][NotEmpty::IS_EMPTY]));

        $requiredFound = false;
        foreach ($messages['collection'][1]['file'] as $message) {
            if (strpos($message, 'Value is required') === 0) {
                $requiredFound = true;
                break;
            }
        }
        $this->assertTrue($requiredFound, '"Required" message was not found in validation failure messages');
    }
}
