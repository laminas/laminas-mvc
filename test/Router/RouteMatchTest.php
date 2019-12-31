<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Router;

use Laminas\Mvc\Router\RouteMatch;
use PHPUnit_Framework_TestCase as TestCase;

class RouteMatchTest extends TestCase
{
    public function testParamsAreStored()
    {
        $match = new RouteMatch(array('foo' => 'bar'));

        $this->assertEquals(array('foo' => 'bar'), $match->getParams());
    }

    public function testMatchedRouteNameIsSet()
    {
        $match = new RouteMatch(array());
        $match->setMatchedRouteName('foo');

        $this->assertEquals('foo', $match->getMatchedRouteName());
    }

    public function testSetParam()
    {
        $match = new RouteMatch(array());
        $match->setParam('foo', 'bar');

        $this->assertEquals(array('foo' => 'bar'), $match->getParams());
    }

    public function testGetParam()
    {
        $match = new RouteMatch(array('foo' => 'bar'));

        $this->assertEquals('bar', $match->getParam('foo'));
    }

    public function testGetNonExistentParamWithoutDefault()
    {
        $match = new RouteMatch(array());

        $this->assertNull($match->getParam('foo'));
    }

    public function testGetNonExistentParamWithDefault()
    {
        $match = new RouteMatch(array());

        $this->assertEquals('bar', $match->getParam('foo', 'bar'));
    }
}
