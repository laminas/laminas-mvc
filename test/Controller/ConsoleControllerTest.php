<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Controller;

use Laminas\Console\Request as ConsoleRequest;
use Laminas\Http\Request;
use Laminas\Mvc\MvcEvent;
use Laminas\Mvc\Router\RouteMatch;
use PHPUnit_Framework_TestCase as TestCase;

class ConsoleControllerTest extends TestCase
{
    /**
     * @var TestAsset\ConsoleController
     */
    public $controller;

    public function setUp()
    {
        $this->controller = new TestAsset\ConsoleController();
        $routeMatch = new RouteMatch(array('controller' => 'controller-sample'));
        $event      = new MvcEvent();
        $event->setRouteMatch($routeMatch);
        $this->controller->setEvent($event);
    }

    public function testDispatchCorrectRequest()
    {
        $request = new ConsoleRequest();
        $result = $this->controller->dispatch($request);

        $this->assertNotNull($result);
    }

    public function testDispatchIncorrectRequest()
    {
        $this->setExpectedException('\Laminas\Mvc\Exception\InvalidArgumentException');

        $request = new Request();
        $this->controller->dispatch($request);
    }

    public function testGetNoInjectedConsole()
    {
        $console = $this->controller->getConsole();

        $this->assertNull($console);
    }

    public function testGetInjectedConsole()
    {
        $consoleAdapter = $this->getMock('\Laminas\Console\Adapter\AdapterInterface');

        $controller = $this->controller->setConsole($consoleAdapter);
        $console = $this->controller->getConsole();

        $this->assertInstanceOf('\Laminas\Mvc\Controller\AbstractConsoleController', $controller);
        $this->assertInstanceOf('\Laminas\Console\Adapter\AdapterInterface', $console);
    }
}
