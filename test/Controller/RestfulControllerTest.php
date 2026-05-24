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
use function http_build_query;
use function json_encode;
use function method_exists;
use function sort;
use function uniqid;

class RestfulControllerTest extends TestCase
{
    public RestfulTestController $controller;
    public RestfulMethodNotAllowedTestController $emptyController;
    public Request $request;
    public Response $response;
    public RouteMatch $routeMatch;
    public MvcEvent $event;
    private SharedEventManager $sharedEvents;
    private EventManager $events;

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
        self::assertArrayHasKey('entities', $result);
        self::assertEquals($entities, $result['entities']);
        self::assertEquals('getList', $this->routeMatch->getParam('action'));
    }

    public function testDispatchInvokesGetMethodWhenNoActionPresentAndIdentifierPresentOnGet(): void
    {
        $entity                   = new stdClass();
        $this->controller->entity = $entity;
        $this->routeMatch->setParam('id', 1);
        $result = $this->controller->dispatch($this->request, $this->response);
        self::assertArrayHasKey('entity', $result);
        self::assertEquals($entity, $result['entity']);
        self::assertEquals('get', $this->routeMatch->getParam('action'));
    }

    public function testDispatchInvokesCreateMethodWhenNoActionPresentAndPostInvoked(): void
    {
        $entity = ['id' => 1, 'name' => __FUNCTION__];
        $this->request->setMethod('POST');
        $post = $this->request->getPost();
        $post->fromArray($entity);
        $result = $this->controller->dispatch($this->request, $this->response);
        self::assertArrayHasKey('entity', $result);
        self::assertEquals($entity, $result['entity']);
        self::assertEquals('create', $this->routeMatch->getParam('action'));
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

        self::assertEquals($id, $result['id']);
        self::assertEquals($string, $result['data']);
        self::assertEquals('update', $this->routeMatch->getParam('action'));
    }

    public function testDispatchInvokesUpdateMethodWhenNoActionPresentAndPutInvokedWithIdentifier(): void
    {
        $entity = ['name' => __FUNCTION__];
        $string = http_build_query($entity);
        $this->request->setMethod('PUT')
                      ->setContent($string);
        $this->routeMatch->setParam('id', 1);
        $result = $this->controller->dispatch($this->request, $this->response);
        self::assertArrayHasKey('entity', $result);
        $test = $result['entity'];
        self::assertArrayHasKey('id', $test);
        self::assertEquals(1, $test['id']);
        self::assertArrayHasKey('name', $test);
        self::assertEquals(__FUNCTION__, $test['name']);
        self::assertEquals('update', $this->routeMatch->getParam('action'));
    }

    public function testDispatchInvokesReplaceListMethodWhenNoActionPresentAndPutInvokedWithoutIdentifier(): void
    {
        $entities = [
            ['id' => uniqid(), 'name' => __FUNCTION__],
            ['id' => uniqid(), 'name' => __FUNCTION__],
            ['id' => uniqid(), 'name' => __FUNCTION__],
        ];
        $string   = http_build_query($entities);
        $this->request->setMethod('PUT')
                      ->setContent($string);
        $result = $this->controller->dispatch($this->request, $this->response);
        self::assertEquals($entities, $result);
        self::assertEquals('replaceList', $this->routeMatch->getParam('action'));
    }

    public function testDispatchInvokesPatchListMethodWhenNoActionPresentAndPatchInvokedWithoutIdentifier(): void
    {
        $entities = [
            ['id' => uniqid(), 'name' => __FUNCTION__],
            ['id' => uniqid(), 'name' => __FUNCTION__],
            ['id' => uniqid(), 'name' => __FUNCTION__],
        ];
        $string   = http_build_query($entities);
        $this->request->setMethod('PATCH')
                      ->setContent($string);
        $result = $this->controller->dispatch($this->request, $this->response);
        self::assertEquals($entities, $result);
        self::assertEquals('patchList', $this->routeMatch->getParam('action'));
    }

    public function testDispatchInvokesDeleteMethodWhenNoActionPresentAndDeleteInvokedWithIdentifier(): void
    {
        $entity                   = ['id' => 1, 'name' => __FUNCTION__];
        $this->controller->entity = $entity;
        $this->request->setMethod('DELETE');
        $this->routeMatch->setParam('id', 1);
        $result = $this->controller->dispatch($this->request, $this->response);
        self::assertEquals([], $result);
        self::assertEquals([], $this->controller->entity);
        self::assertEquals('delete', $this->routeMatch->getParam('action'));
    }

    public function testDispatchInvokesDeleteListMethodWhenNoActionPresentAndDeleteInvokedWithoutIdentifier(): void
    {
        $entities = [
            ['id' => uniqid(), 'name' => __FUNCTION__],
            ['id' => uniqid(), 'name' => __FUNCTION__],
            ['id' => uniqid(), 'name' => __FUNCTION__],
        ];

        $this->controller->entity = $entities;

        $string = http_build_query($entities);
        $this->request->setMethod('DELETE')
                      ->setContent($string);
        $result = $this->controller->dispatch($this->request, $this->response);
        self::assertEmpty($this->controller->entity);
        self::assertEquals(204, $result->getStatusCode());
        self::assertTrue($result->getHeaders()->has('X-Deleted'));
        self::assertEquals('deleteList', $this->routeMatch->getParam('action'));
    }

    public function testDispatchInvokesOptionsMethodWhenNoActionPresentAndOptionsInvoked(): void
    {
        $this->request->setMethod('OPTIONS');
        $result = $this->controller->dispatch($this->request, $this->response);
        self::assertSame($this->response, $result);
        self::assertEquals('options', $this->routeMatch->getParam('action'));
        $headers = $result->getHeaders();
        self::assertTrue($headers->has('Allow'));
        $allow    = $headers->get('Allow');
        $expected = explode(', ', 'GET, POST, PUT, DELETE, PATCH, HEAD, TRACE');
        sort($expected);
        $test = explode(', ', $allow->getFieldValue());
        sort($test);
        self::assertEquals($expected, $test);
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
        self::assertArrayHasKey('entity', $result);
        $test = $result['entity'];
        self::assertArrayHasKey('id', $test);
        self::assertEquals(1, $test['id']);
        self::assertArrayHasKey('name', $test);
        self::assertEquals(__FUNCTION__, $test['name']);
        self::assertArrayHasKey('type', $test);
        self::assertEquals('standard', $test['type']);
        self::assertEquals('patch', $this->routeMatch->getParam('action'));
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

        self::assertEquals(418, $result->getStatusCode());
        self::assertEquals('', $result->getContent());
        self::assertEquals('head', $this->routeMatch->getParam('action'));
        self::assertEquals('Header Value', $result->getHeaders()->get('Custom-Header')->getFieldValue());
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
        self::assertSame($this->response, $result);
        $content = $result->getContent();
        self::assertEquals('', $content);
        self::assertEquals('head', $this->routeMatch->getParam('action'));
    }

    public function testDispatchInvokesHeadMethodWhenNoActionPresentAndHeadInvokedWithIdentifier(): void
    {
        $entity                   = new stdClass();
        $this->controller->entity = $entity;
        $this->routeMatch->setParam('id', 1);
        $this->request->setMethod('HEAD');
        $result = $this->controller->dispatch($this->request, $this->response);
        self::assertSame($this->response, $result);
        $content = $result->getContent();
        self::assertEquals('', $content);
        self::assertEquals('head', $this->routeMatch->getParam('action'));

        $headers = $this->controller->getResponse()->getHeaders();
        self::assertTrue($headers->has('X-Laminas-Id'));
        $header = $headers->get('X-Laminas-Id');
        self::assertEquals(1, $header->getFieldValue());
    }

    public function testAllowsRegisteringCustomHttpMethodsWithHandlers(): void
    {
        $this->controller->addHttpMethodHandler('DESCRIBE', [$this->controller, 'describe']);
        $this->request->setMethod('DESCRIBE');
        $result = $this->controller->dispatch($this->request, $this->response);
        self::assertArrayHasKey('description', $result);
        self::assertStringContainsString('::describe', $result['description']);
    }

    public function testDispatchCallsActionMethodBasedOnNormalizingAction(): void
    {
        $this->routeMatch->setParam('action', 'test.some-strangely_separated.words');
        $result = $this->controller->dispatch($this->request, $this->response);
        self::assertArrayHasKey('content', $result);
        self::assertStringContainsString('Test Some Strangely Separated Words', $result['content']);
    }

    public function testDispatchCallsNotFoundActionWhenActionPassedThatCannotBeMatched(): void
    {
        $this->routeMatch->setParam('action', 'test-some-made-up-action');
        $result   = $this->controller->dispatch($this->request, $this->response);
        $response = $this->controller->getResponse();
        self::assertEquals(404, $response->getStatusCode());
        self::assertArrayHasKey('content', $result);
        self::assertStringContainsString('Page not found', $result['content']);
    }

    public function testShortCircuitsBeforeActionIfPreDispatchReturnsAResponse(): void
    {
        $response = new Response();
        $response->setContent('short circuited!');
        $this->controller->getEventManager()->attach(
            MvcEvent::EVENT_DISPATCH,
            static fn($e): Response => $response,
            10
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
        $this->sharedEvents->attach(
            DispatchableInterface::class,
            MvcEvent::EVENT_DISPATCH,
            static fn($e): Response => $response,
            10
        );
        $result = $this->controller->dispatch($this->request, $this->response);
        self::assertSame($response, $result);
    }

    public function testEventManagerListensOnRestfulControllerClassByDefault(): void
    {
        $response = new Response();
        $response->setContent('short circuited!');
        $this->sharedEvents->attach(
            AbstractRestfulController::class,
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
        $this->sharedEvents->attach(
            $this->controller::class,
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

    public function testParsingDataAsJsonWillReturnAsArray(): void
    {
        $this->request->setMethod('POST');
        $this->request->getHeaders()->addHeaderLine('Content-type', 'application/json');
        $this->request->setContent('{"foo":"bar"}');

        $result = $this->controller->dispatch($this->request, $this->response);
        self::assertIsArray($result);
        self::assertEquals(['entity' => ['foo' => 'bar']], $result);
    }

    public static function matchingContentTypes(): array
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
        self::assertTrue($this->controller->requestHasContentType(
            $this->request,
            RestfulTestController::CONTENT_TYPE_JSON
        ));
    }

    public static function nonMatchingContentTypes(): array
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
        self::assertFalse($this->controller->requestHasContentType(
            $this->request,
            RestfulTestController::CONTENT_TYPE_JSON
        ));
    }

    public function testDispatchWithUnrecognizedMethodReturns405Response(): void
    {
        $this->request->setMethod('PROPFIND');
        $result = $this->controller->dispatch($this->request, $this->response);
        self::assertInstanceOf(Response::class, $result);
        self::assertEquals(405, $result->getStatusCode());
    }

    public function testDispatchInvokesGetMethodWhenNoActionPresentAndZeroIdentifierPresentOnGet(): void
    {
        $entity                   = new stdClass();
        $this->controller->entity = $entity;
        $this->routeMatch->setParam('id', 0);
        $result = $this->controller->dispatch($this->request, $this->response);
        self::assertArrayHasKey('entity', $result);
        self::assertEquals($entity, $result['entity']);
        self::assertEquals('get', $this->routeMatch->getParam('action'));
    }

    public function testIdentifierNameDefaultsToId(): void
    {
        self::assertEquals('id', $this->controller->getIdentifierName());
    }

    public function testCanSetIdentifierName(): void
    {
        $this->controller->setIdentifierName('name');
        self::assertEquals('name', $this->controller->getIdentifierName());
    }

    public function testUsesConfiguredIdentifierNameToGetIdentifier(): void
    {
        $r             = new ReflectionObject($this->controller);
        $getIdentifier = $r->getMethod('getIdentifier');
        $getIdentifier->setAccessible(true);

        $this->controller->setIdentifierName('name');

        $this->routeMatch->setParam('name', 'foo');
        $result = $getIdentifier->invoke($this->controller, $this->routeMatch, $this->request);
        self::assertEquals('foo', $result);

        $this->routeMatch->setParam('name', false);
        $this->request->getQuery()->set('name', 'bar');
        $result = $getIdentifier->invoke($this->controller, $this->routeMatch, $this->request);
        self::assertEquals('bar', $result);
    }

    /**
     * @dataProvider providerNotImplementedMethodSets504HttpCodeProvider
     */
    public function testNotImplementedMethodSets504HttpCode(
        string $method,
        array|string $content,
        array $routeParams
    ): void {
        $this->request->setMethod($method);

        if ($content) {
            $this->request->setContent($content);
        }

        foreach ($routeParams as $name => $value) {
            $this->routeMatch->setParam($name, $value);
        }

        $result   = $this->emptyController->dispatch($this->request, $this->response);
        $response = $this->emptyController->getResponse();

        self::assertEquals(405, $response->getStatusCode());
        self::assertEquals('Method Not Allowed', $this->response->getReasonPhrase());
    }

    public static function providerNotImplementedMethodSets504HttpCodeProvider(): array
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
