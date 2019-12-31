<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Router\Http;

use Laminas\Http\Request as Request;
use Laminas\Mvc\Router\Http\Query;
use Laminas\Stdlib\Request as BaseRequest;
use Laminas\Uri\Http;
use LaminasTest\Mvc\Router\FactoryTester;
use PHPUnit_Framework_TestCase as TestCase;

class QueryTest extends TestCase
{
    public function setUp()
    {
        $this->markTestSkipped('Query route part has been deprecated in Laminas as of 2.1.4');
    }

    public function routeProvider()
    {
        // Have to setup error handler here as well, as PHPUnit calls on
        // provider methods outside the scope of setUp().
        return [
            'simple-match' => [
                new Query(),
                'foo=bar&baz=bat',
                null,
                ['foo' => 'bar', 'baz' => 'bat']
            ],
            'empty-match' => [
                new Query(),
                '',
                null,
                []
            ],
            'url-encoded-parameters-are-decoded' => [
                new Query(),
                'foo=foo%20bar',
                null,
                ['foo' => 'foo bar']
            ],
            'nested-params' => [
                new Query(),
                'foo%5Bbar%5D=baz&foo%5Bbat%5D=foo%20bar',
                null,
                ['foo' => ['bar' => 'baz', 'bat' => 'foo bar']]
            ],
        ];
    }

    /**
     * @param        Query $route
     * @param        string   $path
     * @param        integer  $offset
     * @param        array    $params
     */
    public function testMatching(Query $route, $path, $offset, array $params = null)
    {
        $request = new Request();
        $request->setUri('http://example.com?' . $path);
        $match = $route->match($request, $offset);
        $this->assertInstanceOf('Laminas\Mvc\Router\RouteMatch', $match);
    }

    /**
     * @param        Query $route
     * @param        string   $path
     * @param        integer  $offset
     * @param        array    $params
     * @param        boolean  $skipAssembling
     */
    public function testAssembling(Query $route, $path, $offset, array $params = null, $skipAssembling = false)
    {
        if ($params === null || $skipAssembling) {
            // Data which will not match are not tested for assembling.
            return;
        }

        $uri = new Http();
        $result = $route->assemble($params, ['uri' => $uri]);

        if ($offset !== null) {
            $this->assertEquals($offset, strpos($path, $uri->getQuery(), $offset));
        } else {
            $this->assertEquals($path, $uri->getQuery());
        }
    }

    public function testNoMatchWithoutUriMethod()
    {
        $route   = new Query();
        $request = new BaseRequest();
        $match   = $route->match($request);
        $this->assertInstanceOf('Laminas\Mvc\Router\RouteMatch', $match);
        $this->assertEquals([], $match->getParams());
    }

    public function testGetAssembledParams()
    {
        $route = new Query();
        $uri = new Http();
        $route->assemble(['foo' => 'bar'], ['uri' => $uri]);


        $this->assertEquals(['foo'], $route->getAssembledParams());
    }

    public function testFactory()
    {
        $tester = new FactoryTester($this);
        $tester->testFactory(
            'Laminas\Mvc\Router\Http\Query',
            [],
            []
        );
    }
}
