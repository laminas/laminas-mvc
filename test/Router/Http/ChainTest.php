<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Router\Http;

use Laminas\Http\Request as Request;
use Laminas\Mvc\Router\Http\Chain;
use Laminas\Mvc\Router\RoutePluginManager;
use LaminasTest\Mvc\Router\FactoryTester;
use PHPUnit_Framework_TestCase as TestCase;

class ChainTest extends TestCase
{
    public static function getRoute()
    {
        $routePlugins = new RoutePluginManager();

        return new Chain(
            array(
                array(
                    'type'    => 'Laminas\Mvc\Router\Http\Segment',
                    'options' => array(
                        'route'    => '/:controller',
                        'defaults' => array(
                            'controller' => 'foo',
                        ),
                    ),
                ),
                array(
                    'type'    => 'Laminas\Mvc\Router\Http\Segment',
                    'options' => array(
                        'route'    => '/:bar',
                        'defaults' => array(
                            'bar' => 'bar',
                        ),
                    ),
                ),
                array(
                    'type' => 'Laminas\Mvc\Router\Http\Wildcard',
                ),
            ),
            $routePlugins
        );
    }

    public static function getRouteWithOptionalParam()
    {
        $routePlugins = new RoutePluginManager();

        return new Chain(
            array(
                array(
                    'type'    => 'Laminas\Mvc\Router\Http\Segment',
                    'options' => array(
                        'route'    => '/:controller',
                        'defaults' => array(
                            'controller' => 'foo',
                        ),
                    ),
                ),
                array(
                    'type'    => 'Laminas\Mvc\Router\Http\Segment',
                    'options' => array(
                        'route'    => '[/:bar]',
                        'defaults' => array(
                            'bar' => 'bar',
                        ),
                    ),
                ),
            ),
            $routePlugins
        );
    }

    public static function routeProvider()
    {
        return array(
            'simple-match' => array(
                self::getRoute(),
                '/foo/bar',
                null,
                array(
                    'controller' => 'foo',
                    'bar'        => 'bar',
                ),
            ),
            'offset-skips-beginning' => array(
                self::getRoute(),
                '/baz/foo/bar',
                4,
                array(
                    'controller' => 'foo',
                    'bar'        => 'bar',
                ),
            ),
            'parameters-are-used-only-once' => array(
                self::getRoute(),
                '/foo/baz',
                null,
                array(
                    'controller' => 'foo',
                    'bar' => 'baz',
                ),
            ),
            'optional-parameter' => array(
                self::getRouteWithOptionalParam(),
                '/foo/baz',
                null,
                array(
                    'controller' => 'foo',
                    'bar' => 'baz',
                ),
            ),
            'optional-parameter-empty' => array(
                self::getRouteWithOptionalParam(),
                '/foo',
                null,
                array(
                    'controller' => 'foo',
                    'bar' => 'bar',
                ),
            ),
        );
    }

    /**
     * @dataProvider routeProvider
     * @param        Chain   $route
     * @param        string  $path
     * @param        integer $offset
     * @param        array   $params
     */
    public function testMatching(Chain $route, $path, $offset, array $params = null)
    {
        $request = new Request();
        $request->setUri('http://example.com' . $path);
        $match = $route->match($request, $offset);

        if ($params === null) {
            $this->assertNull($match);
        } else {
            $this->assertInstanceOf('Laminas\Mvc\Router\Http\RouteMatch', $match);

            if ($offset === null) {
                $this->assertEquals(strlen($path), $match->getLength());
            }

            foreach ($params as $key => $value) {
                $this->assertEquals($value, $match->getParam($key));
            }
        }
    }

    /**
     * @dataProvider routeProvider
     * @param        Chain   $route
     * @param        string  $path
     * @param        integer $offset
     * @param        string  $routeName
     * @param        array   $params
     */
    public function testAssembling(Chain $route, $path, $offset, array $params = null)
    {
        if ($params === null) {
            // Data which will not match are not tested for assembling.
            return;
        }

        $result = $route->assemble($params);

        if ($offset !== null) {
            $this->assertEquals($offset, strpos($path, $result, $offset));
        } else {
            $this->assertEquals($path, $result);
        }
    }

    public function testFactory()
    {
        $tester = new FactoryTester($this);
        $tester->testFactory(
            'Laminas\Mvc\Router\Http\Chain',
            array(
                'routes'        => 'Missing "routes" in options array',
                'route_plugins' => 'Missing "route_plugins" in options array',
            ),
            array(
                'routes'        => array(),
                'route_plugins' => new RoutePluginManager(),
            )
        );
    }
}
