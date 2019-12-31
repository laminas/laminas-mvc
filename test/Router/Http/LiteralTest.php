<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Router\Http;

use Laminas\Http\Request as Request;
use Laminas\Mvc\Router\Http\Literal;
use Laminas\Stdlib\Request as BaseRequest;
use LaminasTest\Mvc\Router\FactoryTester;
use PHPUnit_Framework_TestCase as TestCase;

class LiteralTest extends TestCase
{
    public static function routeProvider()
    {
        return [
            'simple-match' => [
                new Literal('/foo'),
                '/foo',
                null,
                true
            ],
            'no-match-without-leading-slash' => [
                new Literal('foo'),
                '/foo',
                null,
                false
            ],
            'no-match-with-trailing-slash' => [
                new Literal('/foo'),
                '/foo/',
                null,
                false
            ],
            'offset-skips-beginning' => [
                new Literal('foo'),
                '/foo',
                1,
                true
            ],
            'offset-enables-partial-matching' => [
                new Literal('/foo'),
                '/foo/bar',
                0,
                true
            ],
        ];
    }

    /**
     * @dataProvider routeProvider
     * @param        Literal $route
     * @param        string  $path
     * @param        integer $offset
     * @param        bool $shouldMatch
     */
    public function testMatching(Literal $route, $path, $offset, $shouldMatch)
    {
        $request = new Request();
        $request->setUri('http://example.com' . $path);
        $match = $route->match($request, $offset);

        if (!$shouldMatch) {
            $this->assertNull($match);
        } else {
            $this->assertInstanceOf('Laminas\Mvc\Router\Http\RouteMatch', $match);

            if ($offset === null) {
                $this->assertEquals(strlen($path), $match->getLength());
            }
        }
    }

    /**
     * @dataProvider routeProvider
     * @param        Literal $route
     * @param        string  $path
     * @param        integer $offset
     * @param        bool $shouldMatch
     */
    public function testAssembling(Literal $route, $path, $offset, $shouldMatch)
    {
        if (!$shouldMatch) {
            // Data which will not match are not tested for assembling.
            return;
        }

        $result = $route->assemble();

        if ($offset !== null) {
            $this->assertEquals($offset, strpos($path, $result, $offset));
        } else {
            $this->assertEquals($path, $result);
        }
    }

    public function testNoMatchWithoutUriMethod()
    {
        $route   = new Literal('/foo');
        $request = new BaseRequest();

        $this->assertNull($route->match($request));
    }

    public function testGetAssembledParams()
    {
        $route = new Literal('/foo');
        $route->assemble(['foo' => 'bar']);

        $this->assertEquals([], $route->getAssembledParams());
    }

    public function testFactory()
    {
        $tester = new FactoryTester($this);
        $tester->testFactory(
            'Laminas\Mvc\Router\Http\Literal',
            [
                'route' => 'Missing "route" in options array'
            ],
            [
                'route' => '/foo'
            ]
        );
    }

    /**
     * @group Laminas-436
     */
    public function testEmptyLiteral()
    {
        $request = new Request();
        $route = new Literal('');
        $this->assertNull($route->match($request, 0));
    }
}
