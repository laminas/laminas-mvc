<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\Controller;

use Laminas\EventManager\EventManager;
use Laminas\EventManager\SharedEventManager;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractRestfulController;
use Laminas\Mvc\Controller\Plugin\Url;
use Laminas\Mvc\InjectApplicationEventInterface;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\RouteMatch;
use Laminas\Stdlib\DispatchableInterface;
use LaminasTest\Mvc\Controller\TestAsset\Request;
use LaminasTest\Mvc\Controller\TestAsset\RestfulContentTypeTestController;
use LaminasTest\Mvc\Controller\TestAsset\RestfulMethodNotAllowedTestController;
use LaminasTest\Mvc\Controller\TestAsset\RestfulTestController;
use PHPUnit\Framework\TestCase;
use ReflectionObject;
use stdClass;

use function explode;
use function get_class;
use function http_build_query;
use function json_encode;
use function method_exists;
use function sort;
use function uniqid;

class RestfulControllerTest extends TestCase
{
    /** @var RestfulTestController */
    public $controller;
    /** @var RestfulMethodNotAllowedTestController */
    public $emptyController;
    /** @var Request */
    public $request;
    /** @var Response */
    public $response;
    /** @var RouteMatch */
    public $routeMatch;
    /** @var MvcEvent */
    public $event;

    public function setUp(): void
    {
        $this->controller      = new RestfulTestController();
        $this->emptyController = new RestfulMethodNotAllowedTestController();
        $this->request         = new Request();
        $this->response        = new Response();
        $this->routeMatch      = new RouteMatch(['controller' => 'controller-restful']);
        $this->event           = new MvcEvent();
        $this->event->setRouteMatch($this->routeMatch);
        $this->controller->setEvent($this->event);
        $this->emptyController->setEvent($this->event);

        $this->sharedEvents = new SharedEventManager();
        $this->events       = $this->createEventManager($this->sharedEvents);
        $this->controller->setEventManager($this->events);
    }

    protected function createEventManager(SharedEventManagerInterface $sharedManager): EventManager
    {
        return new EventManager($sharedManager);
    }

    public function testDispatchInvokesListWhenNoActionPresentAndNoIdentifierOnGet(): void
    {
        $entities                   = [
            new stdClass(),
            new stdClass(),
            new stdClass(),
        ];
        $this->controller->entities = $entities;
        $result                     = $this->controller->dispatch($this->request, $this->response);
        $this->assertArrayHasKey('entities', $result);
        $this->assertEquals($entities, $result['entities']);
        $this->assertEquals('getList', $this->routeMatch->getParam('action'));
    }

    public function testDispatchInvokesGetMethodWhenNoActionPresentAndIdentifierPresentOnGet(): void
    {
        $entity                   = new stdClass();
        $this->controller->entity = $entity;
        $this->routeMatch->setParam('id', 1);
        $result = $this->controller->dispatch($this->request, $this->response);
        $this->assertArrayHasKey('entity', $result);
        $this->assertEquals($entity, $result['entity']);
        $this->assertEquals('get', $this->routeMatch->getParam('action'));
    }

    public function testDispatchInvokesCreateMethodWhenNoActionPresentAndPostInvoked(): void
    {
        $entity = ['id' => 1, 'name' => __FUNCTION__];
        $this->request->setMethod('POST');
        $post = $this->request->getPost();
        $post->fromArray($entity);
        $result = $this->controller->dispatch($this->request, $this->response);
        $this->assertArrayHasKey('entity', $result);
        $this->assertEquals($entity, $result['entity']);
        $this->assertEquals('create', $this->routeMatch->getParam('action'));
    }

    public function testCanReceiveStringAsRequestContent(): void
    {
        $string = "any content";
        $this->request->setMethod('PUT');
        $this->request->setContent($string);
        $this->routeMatch->setParam('id', $id = 1);

        $controller = new RestfulContentTypeTestController();
        $controller->setEvent($this->event);
        $result = $controller->dispatch($this->request, $this->response);

        $this->assertEquals($id, $result['id']);
        $this->assertEquals($string, $result['data']);
        $this->assertEquals('update', $this->routeMatch->getParam('action'));
    }

    public function testDispatchInvokesUpdateMethodWhenNoActionPresentAndPutInvokedWithIdentifier(): void
    {
        $entity = ['name' => __FUNCTION__];
        $string = http_build_query($entity);
        $this->request->setMethod('PUT')
                      ->setContent($string);
        $this->routeMatch->setParam('id', 1);
        $result = $this->controller->dispatch($this->request, $this->response);
        $this->assertArrayHasKey('entity', $result);
        $test = $result['entity'];
        $this->assertArrayHasKey('id', $test);
        $this->assertEquals(1, $test['id']);
        $this->assertArrayHasKey('name', $test);
        $this->assertEquals(__FUNCTION__, $test['name']);
        $this->assertEquals('update', $this->routeMatch->getParam('action'));
    }

    public function testDispatchInvokesReplaceListMethodWhenNoActionPresentAndPutInvokedWithoutIdentifier(): void
    {
        $entities = [
            ['id' => uniqid('', true), 'name' => __FUNCTION__],
            ['id' => uniqid('', true), 'name' => __FUNCTION__],
            ['id' => uniqid('', true), 'name' => __FUNCTION__],
        ];
        $string   = http_build_query($entities);
        $this->request->setMethod('PUT')
                      ->setContent($string);
        $result = $this->controller->dispatch($this->request, $this->response);
        $this->assertEquals($entities, $result);
        $this->assertEquals('replaceList', $this->routeMatch->getParam('action'));
    }

    public function testDispatchInvokesPatchListMethodWhenNoActionPresentAndPatchInvokedWithoutIdentifier(): void
    {
        $entities = [
            ['id' => uniqid('', true), 'name' => __FUNCTION__],
            ['id' => uniqid('', true), 'name' => __FUNCTION__],
            ['id' => uniqid('', true), 'name' => __FUNCTION__],
        ];
        $string   = http_build_query($entities);
        $this->request->setMethod('PATCH')
                      ->setContent($string);
        $result = $this->controller->dispatch($this->request, $this->response);
        $this->assertEquals($entities, $result);
        $this->assertEquals('patchList', $this->routeMatch->getParam('action'));
    }

    public function testDispatchInvokesDeleteMethodWhenNoActionPresentAndDeleteInvokedWithIdentifier(): void
    {
        $entity                   = ['id' => 1, 'name' => __FUNCTION__];
        $this->controller->entity = $entity;
        $this->request->setMethod('DELETE');
        $this->routeMatch->setParam('id', 1);
        $result = $this->controller->dispatch($this->request, $this->response);
        $this->assertEquals([], $result);
        $this->assertEquals([], $this->controller->entity);
        $this->assertEquals('delete', $this->routeMatch->getParam('action'));
    }

    public function testDispatchInvokesDeleteListMethodWhenNoActionPresentAndDeleteInvokedWithoutIdentifier(): void
    {
        $entities = [
            ['id' => uniqid('', true), 'name' => __FUNCTION__],
            ['id' => uniqid('', true), 'name' => __FUNCTION__],
            ['id' => uniqid('', true), 'name' => __FUNCTION__],
        ];

        $this->controller->entity = $entities;

        $string = http_build_query($entities);
        $this->request->setMethod('DELETE')
                      ->setContent($string);
        $result = $this->controller->dispatch($this->request, $this->response);
        $this->assertEmpty($this->controller->entity);
        $this->assertEquals(204, $result->getStatusCode());
        $this->assertTrue($result->getHeaders()->has('X-Deleted'));
        $this->assertEquals('deleteList', $this->routeMatch->getParam('action'));
    }

    public function testDispatchInvokesOptionsMethodWhenNoActionPresentAndOptionsInvoked(): void
    {
        $this->request->setMethod('OPTIONS');
        $result = $this->controller->dispatch($this->request, $this->response);
        $this->assertSame($this->response, $result);
        $this->assertEquals('options', $this->routeMatch->getParam('action'));
        $headers = $result->getHeaders();
        $this->assertTrue($headers->has('Allow'));
        $allow    = $headers->get('Allow');
        $expected = explode(', ', 'GET, POST, PUT, DELETE, PATCH, HEAD, TRACE');
        sort($expected);
        $test = explode(', ', $allow->getFieldValue());
        sort($test);
        $this->assertEquals($expected, $test);
    }

    public function testDispatchInvokesPatchMethodWhenNoActionPresentAndPatchInvokedWithIdentifier(): void
    {
        $entity                   = new stdClass();
        $entity->name             = 'foo';
        $entity->type             = 'standard';
        $this->controller->entity = $entity;
        $entity                   = ['name' => __FUNCTION__];
        $string                   = http_build_query($entity);
        $this->request->setMethod('PATCH')
                      ->setContent($string);
        $this->routeMatch->setParam('id', 1);
        $result = $this->controller->dispatch($this->request, $this->response);
        $this->assertArrayHasKey('entity', $result);
        $test = $result['entity'];
        $this->assertArrayHasKey('id', $test);
        $this->assertEquals(1, $test['id']);
        $this->assertArrayHasKey('name', $test);
        $this->assertEquals(__FUNCTION__, $test['name']);
        $this->assertArrayHasKey('type', $test);
        $this->assertEquals('standard', $test['type']);
        $this->assertEquals('patch', $this->routeMatch->getParam('action'));
    }

    /**
     * @group 7086
     */
    public function testOnDispatchHonorsStatusCodeWithHeadMethod(): void
    {
        $this->controller->headResponse = new Response();
        $this->controller->headResponse->setStatusCode(418);
        $this->controller->headResponse->getHeaders()->addHeaderLine('Custom-Header', 'Header Value');
        $this->routeMatch->setParam('id', 1);
        $this->request->setMethod('HEAD');
        $result = $this->controller->dispatch($this->request, $this->response);

        $this->assertEquals(418, $result->getStatusCode());
        $this->assertEquals('', $result->getContent());
        $this->assertEquals('head', $this->routeMatch->getParam('action'));
        $this->assertEquals('Header Value', $result->getHeaders()->get('Custom-Header')->getFieldValue());
    }

    public function testDispatchInvokesHeadMethodWhenNoActionPresentAndHeadInvokedWithoutIdentifier(): void
    {
        $entities                   = [
            new stdClass(),
            new stdClass(),
            new stdClass(),
        ];
        $this->controller->entities = $entities;
        $this->request->setMethod('HEAD');
        $result = $this->controller->dispatch($this->request, $this->response);
        $this->assertSame($this->response, $result);
        $content = $result->getContent();
        $this->assertEquals('', $content);
        $this->assertEquals('head', $this->routeMatch->getParam('action'));
    }

    public function testDispatchInvokesHeadMethodWhenNoActionPresentAndHeadInvokedWithIdentifier(): void
    {
        $entity                   = new stdClass();
        $this->controller->entity = $entity;
        $this->routeMatch->setParam('id', 1);
        $this->request->setMethod('HEAD');
        $result = $this->controller->dispatch($this->request, $this->response);
        $this->assertSame($this->response, $result);
        $content = $result->getContent();
        $this->assertEquals('', $content);
        $this->assertEquals('head', $this->routeMatch->getParam('action'));

        $headers = $this->controller->getResponse()->getHeaders();
        $this->assertTrue($headers->has('X-Laminas-Id'));
        $header = $headers->get('X-Laminas-Id');
        $this->assertEquals(1, $header->getFieldValue());
    }

    public function testAllowsRegisteringCustomHttpMethodsWithHandlers(): void
    {
        $this->controller->addHttpMethodHandler('DESCRIBE', [$this->controller, 'describe']);
        $this->request->setMethod('DESCRIBE');
        $result = $this->controller->dispatch($this->request, $this->response);
        $this->assertArrayHasKey('description', $result);
        $this->assertStringContainsString('::describe', $result['description']);
    }

    public function testDispatchCallsActionMethodBasedOnNormalizingAction(): void
    {
        $this->routeMatch->setParam('action', 'test.some-strangely_separated.words');
        $result = $this->controller->dispatch($this->request, $this->response);
        $this->assertArrayHasKey('content', $result);
        $this->assertStringContainsString('Test Some Strangely Separated Words', $result['content']);
    }

    public function testDispatchCallsNotFoundActionWhenActionPassedThatCannotBeMatched(): void
    {
        $this->routeMatch->setParam('action', 'test-some-made-up-action');
        $result   = $this->controller->dispatch($this->request, $this->response);
        $response = $this->controller->getResponse();
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertArrayHasKey('content', $result);
        $this->assertStringContainsString('Page not found', $result['content']);
    }

    public function testShortCircuitsBeforeActionIfPreDispatchReturnsAResponse(): void
    {
        $response = new Response();
        $response->setContent('short circuited!');
        $this->controller->getEventManager()->attach(MvcEvent::EVENT_DISPATCH, function ($e) use ($response) {
            return $response;
        }, 10);
        $result = $this->controller->dispatch($this->request, $this->response);
        $this->assertSame($response, $result);
    }

    public function testPostDispatchEventAllowsReplacingResponse(): void
    {
        $response = new Response();
        $response->setContent('short circuited!');
        $this->controller->getEventManager()->attach(MvcEvent::EVENT_DISPATCH, function ($e) use ($response) {
            return $response;
        }, -10);
        $result = $this->controller->dispatch($this->request, $this->response);
        $this->assertSame($response, $result);
    }

    public function testEventManagerListensOnDispatchableInterfaceByDefault(): void
    {
        $response = new Response();
        $response->setContent('short circuited!');
        $this->sharedEvents->attach(
            DispatchableInterface::class,
            MvcEvent::EVENT_DISPATCH,
            function ($e) use ($response) {
                return $response;
            },
            10
        );
        $result = $this->controller->dispatch($this->request, $this->response);
        $this->assertSame($response, $result);
    }

    public function testEventManagerListensOnRestfulControllerClassByDefault(): void
    {
        $response = new Response();
        $response->setContent('short circuited!');
        $this->sharedEvents->attach(
            AbstractRestfulController::class,
            MvcEvent::EVENT_DISPATCH,
            function ($e) use ($response) {
                return $response;
            },
            10
        );
        $result = $this->controller->dispatch($this->request, $this->response);
        $this->assertSame($response, $result);
    }

    public function testEventManagerListensOnClassNameByDefault(): void
    {
        $response = new Response();
        $response->setContent('short circuited!');
        $this->sharedEvents->attach(
            get_class($this->controller),
            MvcEvent::EVENT_DISPATCH,
            function ($e) use ($response) {
                return $response;
            },
            10
        );
        $result = $this->controller->dispatch($this->request, $this->response);
        $this->assertSame($response, $result);
    }

    public function testDispatchInjectsEventIntoController(): void
    {
        $this->controller->dispatch($this->request, $this->response);
        $event = $this->controller->getEvent();
        $this->assertNotNull($event);
        $this->assertSame($this->event, $event);
    }

    public function testControllerIsEventAware(): void
    {
        $this->assertInstanceOf(InjectApplicationEventInterface::class, $this->controller);
    }

    public function testControllerIsPluggable(): void
    {
        $this->assertTrue(method_exists($this->controller, 'plugin'));
    }

    public function testMethodOverloadingShouldReturnPluginWhenFound(): void
    {
        $plugin = $this->controller->url();
        $this->assertInstanceOf(Url::class, $plugin);
    }

    public function testMethodOverloadingShouldInvokePluginAsFunctorIfPossible(): void
    {
        $model = $this->event->getViewModel();
        $this->controller->layout('alternate/layout');
        $this->assertEquals('alternate/layout', $model->getTemplate());
    }

    public function testParsingDataAsJsonWillReturnAsArray(): void
    {
        $this->request->setMethod('POST');
        $this->request->getHeaders()->addHeaderLine('Content-type', 'application/json');
        $this->request->setContent('{"foo":"bar"}');

        $result = $this->controller->dispatch($this->request, $this->response);
        $this->assertIsArray($result);
        $this->assertEquals(['entity' => ['foo' => 'bar']], $result);
    }

    public function matchingContentTypes(): array
    {
        return [
            'exact-first'     => ['application/hal+json'],
            'exact-second'    => ['application/json'],
            'with-charset'    => ['application/json; charset=utf-8'],
            'with-whitespace' => ['application/json '],
        ];
    }

    /**
     * @dataProvider matchingContentTypes
     */
    public function testRequestingContentTypeReturnsTrueForValidMatches(string $contentType): void
    {
        $this->request->getHeaders()->addHeaderLine('Content-Type', $contentType);
        $this->assertTrue($this->controller->requestHasContentType(
            $this->request,
            RestfulTestController::CONTENT_TYPE_JSON
        ));
    }

    public function nonMatchingContentTypes(): array
    {
        return [
            'specific-type' => ['application/xml'],
            'generic-type'  => ['text/json'],
        ];
    }

    /**
     * @dataProvider nonMatchingContentTypes
     */
    public function testRequestingContentTypeReturnsFalseForInvalidMatches(string $contentType): void
    {
        $this->request->getHeaders()->addHeaderLine('Content-Type', $contentType);
        $this->assertFalse($this->controller->requestHasContentType(
            $this->request,
            RestfulTestController::CONTENT_TYPE_JSON
        ));
    }

    public function testDispatchWithUnrecognizedMethodReturns405Response(): void
    {
        $this->request->setMethod('PROPFIND');
        $result = $this->controller->dispatch($this->request, $this->response);
        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(405, $result->getStatusCode());
    }

    public function testDispatchInvokesGetMethodWhenNoActionPresentAndZeroIdentifierPresentOnGet(): void
    {
        $entity                   = new stdClass();
        $this->controller->entity = $entity;
        $this->routeMatch->setParam('id', 0);
        $result = $this->controller->dispatch($this->request, $this->response);
        $this->assertArrayHasKey('entity', $result);
        $this->assertEquals($entity, $result['entity']);
        $this->assertEquals('get', $this->routeMatch->getParam('action'));
    }

    public function testIdentifierNameDefaultsToId(): void
    {
        $this->assertEquals('id', $this->controller->getIdentifierName());
    }

    public function testCanSetIdentifierName(): void
    {
        $this->controller->setIdentifierName('name');
        $this->assertEquals('name', $this->controller->getIdentifierName());
    }

    public function testUsesConfiguredIdentifierNameToGetIdentifier(): void
    {
        $r             = new ReflectionObject($this->controller);
        $getIdentifier = $r->getMethod('getIdentifier');
        $getIdentifier->setAccessible(true);

        $this->controller->setIdentifierName('name');

        $this->routeMatch->setParam('name', 'foo');
        $result = $getIdentifier->invoke($this->controller, $this->routeMatch, $this->request);
        $this->assertEquals('foo', $result);

        $this->routeMatch->setParam('name', false);
        $this->request->getQuery()->set('name', 'bar');
        $result = $getIdentifier->invoke($this->controller, $this->routeMatch, $this->request);
        $this->assertEquals('bar', $result);
    }

    /**
     * @dataProvider providerNotImplementedMethodSets504HttpCodeProvider
     * @param mixed $content
     */
    public function testNotImplementedMethodSets504HttpCode(string $method, $content, array $routeParams): void
    {
        $this->request->setMethod($method);

        if ($content) {
            $this->request->setContent($content);
        }

        foreach ($routeParams as $name => $value) {
            $this->routeMatch->setParam($name, $value);
        }

        $result   = $this->emptyController->dispatch($this->request, $this->response);
        $response = $this->emptyController->getResponse();

        $this->assertEquals(405, $response->getStatusCode());
        $this->assertEquals('Method Not Allowed', $this->response->getReasonPhrase());
    }

    public function providerNotImplementedMethodSets504HttpCodeProvider(): array
    {
        return [
            ['DELETE', [], ['id' => 1]], // AbstractRestfulController::delete()
            ['DELETE', [], []], // AbstractRestfulController::deleteList()
            ['GET', [], ['id' => 1]], // AbstractRestfulController::get()
            ['GET', [], []], // AbstractRestfulController::getList()
            ['HEAD', [], ['id' => 1]], // AbstractRestfulController::head()
            ['HEAD', [], []], // AbstractRestfulController::head()
            ['OPTIONS', [], []], // AbstractRestfulController::options()
            ['PATCH', http_build_query(['foo' => 1]), ['id' => 1]], // AbstractRestfulController::patch()
            ['PATCH', json_encode(['foo' => 1]), ['id' => 1]], // AbstractRestfulController::patch()
            ['PATCH', http_build_query(['foo' => 1]), []], // AbstractRestfulController::patchList()
            ['PATCH', json_encode(['foo' => 1]), []], // AbstractRestfulController::patchList()
            ['POST', http_build_query(['foo' => 1]), ['id' => 1]], // AbstractRestfulController::update()
            ['POST', json_encode(['foo' => 1]), ['id' => 1]], // AbstractRestfulController::update()
            ['POST', http_build_query(['foo' => 1]), []], // AbstractRestfulController::create()
            ['POST', json_encode(['foo' => 1]), []], // AbstractRestfulController::create()
            ['PUT', http_build_query(['foo' => 1]), ['id' => 1]], // AbstractRestfulController::update()
            ['PUT', json_encode(['foo' => 1]), ['id' => 1]], // AbstractRestfulController::update()
            ['PUT', http_build_query(['foo' => 1]), []], // AbstractRestfulController::replaceList()
            ['PUT', json_encode(['foo' => 1]), []], // AbstractRestfulController::replaceList()
        ];
    }
}
