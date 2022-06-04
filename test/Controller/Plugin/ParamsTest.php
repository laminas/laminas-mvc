<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\Controller\Plugin;

use Laminas\Http\Header\GenericHeader;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\RouteMatch;
use LaminasTest\Mvc\Controller\TestAsset\SampleController;
use PHPUnit\Framework\TestCase;

use function uniqid;

use const UPLOAD_ERR_OK;

class ParamsTest extends TestCase
{
    public function setUp(): void
    {
        $this->request = new Request();
        $event         = new MvcEvent();

        $event->setRequest($this->request);
        $event->setResponse(new Response());
        $event->setRouteMatch(new RouteMatch([
            'value' => 'rm:1234',
            'other' => '1234:rm',
        ]));

        $this->controller = new SampleController();
        $this->controller->setEvent($event);

        $this->plugin = $this->controller->plugin('params');
    }

    public function testFromRouteIsDefault(): void
    {
        $value = $this->plugin->__invoke('value');
        $this->assertEquals('rm:1234', $value);
    }

    public function testFromRouteReturnsDefaultIfSet(): void
    {
        $value = $this->plugin->fromRoute('foo', 'bar');
        $this->assertEquals('bar', $value);
    }

    public function testFromRouteReturnsExpectedValue(): void
    {
        $value = $this->plugin->fromRoute('value');
        $this->assertEquals('rm:1234', $value);
    }

    public function testFromRouteNotReturnsExpectedValueWithDefault(): void
    {
        $value = $this->plugin->fromRoute('value', 'default');
        $this->assertEquals('rm:1234', $value);
    }

    public function testFromRouteReturnsAllIfEmpty(): void
    {
        $value = $this->plugin->fromRoute();
        $this->assertEquals(['value' => 'rm:1234', 'other' => '1234:rm'], $value);
    }

    public function testFromQueryReturnsDefaultIfSet(): void
    {
        $this->setQuery();

        $value = $this->plugin->fromQuery('foo', 'bar');
        $this->assertEquals('bar', $value);
    }

    public function testFromQueryReturnsExpectedValue(): void
    {
        $this->setQuery();

        $value = $this->plugin->fromQuery('value');
        $this->assertEquals('query:1234', $value);
    }

    public function testFromQueryReturnsExpectedValueWithDefault(): void
    {
        $this->setQuery();

        $value = $this->plugin->fromQuery('value', 'default');
        $this->assertEquals('query:1234', $value);
    }

    public function testFromQueryReturnsAllIfEmpty(): void
    {
        $this->setQuery();

        $value = $this->plugin->fromQuery();
        $this->assertEquals(['value' => 'query:1234', 'other' => '1234:other'], $value);
    }

    public function testFromPostReturnsDefaultIfSet(): void
    {
        $this->setPost();

        $value = $this->plugin->fromPost('foo', 'bar');
        $this->assertEquals('bar', $value);
    }

    public function testFromPostReturnsExpectedValue(): void
    {
        $this->setPost();

        $value = $this->plugin->fromPost('value');
        $this->assertEquals('post:1234', $value);
    }

    public function testFromPostReturnsExpectedValueWithDefault(): void
    {
        $this->setPost();

        $value = $this->plugin->fromPost('value', 'default');
        $this->assertEquals('post:1234', $value);
    }

    public function testFromPostReturnsAllIfEmpty(): void
    {
        $this->setPost();

        $value = $this->plugin->fromPost();
        $this->assertEquals(['value' => 'post:1234', 'other' => '2345:other'], $value);
    }

    public function testFromFilesReturnsExpectedValue(): void
    {
        $file = [
            'name'     => 'test.txt',
            'type'     => 'text/plain',
            'size'     => 0,
            'tmp_name' => '/tmp/' . uniqid('', true),
            'error'    => UPLOAD_ERR_OK,
        ];
        $this->request->getFiles()->set('test', $file);
        $this->controller->dispatch($this->request);

        $value = $this->plugin->fromFiles('test');
        $this->assertEquals($value, $file);
    }

    public function testFromFilesReturnsAllIfEmpty(): void
    {
        $file = [
            'name'     => 'test.txt',
            'type'     => 'text/plain',
            'size'     => 0,
            'tmp_name' => '/tmp/' . uniqid('', true),
            'error'    => UPLOAD_ERR_OK,
        ];

        $file2 = [
            'name'     => 'file2.txt',
            'type'     => 'text/plain',
            'size'     => 1,
            'tmp_name' => '/tmp/' . uniqid('', true),
            'error'    => UPLOAD_ERR_OK,
        ];
        $this->request->getFiles()->set('file', $file);
        $this->request->getFiles()->set('file2', $file2);
        $this->controller->dispatch($this->request);

        $value = $this->plugin->fromFiles();
        $this->assertEquals($value, ['file' => $file, 'file2' => $file2]);
    }

    public function testFromHeaderReturnsExpectedValue(): void
    {
        $header = new GenericHeader('X-TEST', 'test');
        $this->request->getHeaders()->addHeader($header);
        $this->controller->dispatch($this->request);

        $value = $this->plugin->fromHeader('X-TEST');
        $this->assertSame($value, $header);
    }

    public function testFromHeaderReturnsAllIfEmpty(): void
    {
        $header  = new GenericHeader('X-TEST', 'test');
        $header2 = new GenericHeader('OTHER-TEST', 'value:12345');

        $this->request->getHeaders()->addHeader($header);
        $this->request->getHeaders()->addHeader($header2);

        $this->controller->dispatch($this->request);

        $value = $this->plugin->fromHeader();
        $this->assertSame($value, ['X-TEST' => 'test', 'OTHER-TEST' => 'value:12345']);
    }

    public function testInvokeWithNoArgumentsReturnsInstance(): void
    {
        $this->assertSame($this->plugin, $this->plugin->__invoke());
    }

    protected function setQuery(): void
    {
        $this->request->setMethod(Request::METHOD_GET);
        $this->request->getQuery()->set('value', 'query:1234');
        $this->request->getQuery()->set('other', '1234:other');

        $this->controller->dispatch($this->request);
    }

    protected function setPost(): void
    {
        $this->request->setMethod(Request::METHOD_POST);
        $this->request->getPost()->set('value', 'post:1234');
        $this->request->getPost()->set('other', '2345:other');

        $this->controller->dispatch($this->request);
    }
}
