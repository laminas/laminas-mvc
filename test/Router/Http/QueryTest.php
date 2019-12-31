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
    public static function routeProvider()
    {
        return array(
            'simple-match' => array(
                new Query(),
                'foo=bar&baz=bat',
                null,
                array('foo' => 'bar', 'baz' => 'bat')
            ),
            'empty-match' => array(
                new Query(),
                '',
                null,
                array()
            ),
            'url-encoded-parameters-are-decoded' => array(
                new Query(),
                'foo=foo%20bar',
                null,
                array('foo' => 'foo bar')
            ),
            'nested-params' => array(
                new Query(),
                'foo%5Bbar%5D=baz&foo%5Bbat%5D=foo%20bar',
                null,
                array('foo' => array('bar' => 'baz', 'bat' => 'foo bar'))
            ),
        );
    }

    /**
     * @dataProvider routeProvider
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
     * @dataProvider routeProvider
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
        $result = $route->assemble($params, array('uri' => $uri));

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
        $this->assertNull($match);
    }

    public function testGetAssembledParams()
    {
        $route = new Query();
        $uri = new Http();
        $route->assemble(array('foo' => 'bar'), array('uri' => $uri));


        $this->assertEquals(array('foo'), $route->getAssembledParams());
    }

    public function testFactory()
    {
        $tester = new FactoryTester($this);
        $tester->testFactory(
            'Laminas\Mvc\Router\Http\Query',
            array(),
            array()
        );
    }
}

