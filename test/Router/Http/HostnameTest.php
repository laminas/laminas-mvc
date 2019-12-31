<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Router\Http;

use Laminas\Http\Request as Request;
use Laminas\Mvc\Router\Http\Hostname;
use Laminas\Stdlib\Request as BaseRequest;
use Laminas\Uri\Http as HttpUri;
use LaminasTest\Mvc\Router\FactoryTester;
use PHPUnit_Framework_TestCase as TestCase;

class HostnameTest extends TestCase
{
    public static function routeProvider()
    {
        return array(
            'simple-match' => array(
                new Hostname(':foo.example.com'),
                'bar.example.com',
                array('foo' => 'bar')
            ),
            'no-match-on-different-hostname' => array(
                new Hostname('foo.example.com'),
                'bar.example.com',
                null
            ),
            'no-match-with-different-number-of-parts' => array(
                new Hostname('foo.example.com'),
                'example.com',
                null
            ),
            'match-overrides-default' => array(
                new Hostname(':foo.example.com', array(), array('foo' => 'baz')),
                'bat.example.com',
                array('foo' => 'bat')
            ),
            'constraints-prevent-match' => array(
                new Hostname(':foo.example.com', array('foo' => '\d+')),
                'bar.example.com',
                null
            ),
            'constraints-allow-match' => array(
                new Hostname(':foo.example.com', array('foo' => '\d+')),
                '123.example.com',
                array('foo' => '123')
            ),
        );
    }

    /**
     * @dataProvider routeProvider
     * @param        Hostname $route
     * @param        string   $hostname
     * @param        array    $params
     */
    public function testMatching(Hostname $route, $hostname, array $params = null)
    {
        $request = new Request();
        $request->setUri('http://' . $hostname . '/');
        $match = $route->match($request);

        if ($params === null) {
            $this->assertNull($match);
        } else {
            $this->assertInstanceOf('Laminas\Mvc\Router\Http\RouteMatch', $match);

            foreach ($params as $key => $value) {
                $this->assertEquals($value, $match->getParam($key));
            }
        }
    }

    /**
     * @dataProvider routeProvider
     * @param        Hostname $route
     * @param        string   $hostname
     * @param        array    $params
     */
    public function testAssembling(Hostname $route, $hostname, array $params = null)
    {
        if ($params === null) {
            // Data which will not match are not tested for assembling.
            return;
        }

        $uri  = new HttpUri();
        $path = $route->assemble($params, array('uri' => $uri));

        $this->assertEquals('', $path);
        $this->assertEquals($hostname, $uri->getHost());
    }

    public function testNoMatchWithoutUriMethod()
    {
        $route   = new Hostname('example.com');
        $request = new BaseRequest();

        $this->assertNull($route->match($request));
    }

    public function testAssemblingWithMissingParameter()
    {
        $this->setExpectedException('Laminas\Mvc\Router\Exception\InvalidArgumentException', 'Missing parameter "foo"');

        $route = new Hostname(':foo.example.com');
        $uri   = new HttpUri();
        $route->assemble(array(), array('uri' => $uri));
    }

    public function testGetAssembledParams()
    {
        $route = new Hostname(':foo.example.com');
        $uri   = new HttpUri();
        $route->assemble(array('foo' => 'bar', 'baz' => 'bat'), array('uri' => $uri));

        $this->assertEquals(array('foo'), $route->getAssembledParams());
    }

    public function testFactory()
    {
        $tester = new FactoryTester($this);
        $tester->testFactory(
            'Laminas\Mvc\Router\Http\Hostname',
            array(
                'route' => 'Missing "route" in options array'
            ),
            array(
                'route' => 'example.com'
            )
        );
    }
}

