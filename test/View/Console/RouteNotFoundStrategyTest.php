<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */
namespace LaminasTest\Mvc\View\Console;

use Laminas\Mvc\View\Console\RouteNotFoundStrategy;
use PHPUnit_Framework_TestCase as TestCase;
use ReflectionClass;

class RouteNotFoundStrategyTest extends TestCase
{
    /**
     * @var RouteNotFoundStrategy
     */
    protected $strategy;

    public function setUp()
    {
        $this->strategy = new RouteNotFoundStrategy();
    }

    public function testRenderTableConcatenateAndInvalidInputDoesNotThrowException()
    {
        $reflection = new ReflectionClass('Laminas\Mvc\View\Console\RouteNotFoundStrategy');
        $method = $reflection->getMethod('renderTable');
        $method->setAccessible(true);
        $result = $method->invokeArgs($this->strategy, array(array(array()), 1, 0));
        $this->assertSame('', $result);
    }
}
