<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Service;

use Interop\Container\ContainerInterface;
use Laminas\Mvc\Service\ViewManagerFactory;
use Laminas\Mvc\View\Console\ViewManager as ConsoleViewManager;
use Laminas\Mvc\View\Http\ViewManager as HttpViewManager;
use PHPUnit_Framework_TestCase as TestCase;

class ViewManagerFactoryTest extends TestCase
{
    use FactoryEnvironmentTrait;

    public function tearDown()
    {
        $this->setConsoleEnvironment(true);
    }

    private function createContainer()
    {
        $console   = $this->prophesize(ConsoleViewManager::class);
        $http      = $this->prophesize(HttpViewManager::class);
        $container = $this->prophesize(ContainerInterface::class);
        $container->get('ConsoleViewManager')->will(function () use ($console) {
            return $console->reveal();
        });
        $container->get('HttpViewManager')->will(function () use ($http) {
            return $http->reveal();
        });
        return $container->reveal();
    }

    public function testReturnsConsoleViewManagerInConsoleEnvironment()
    {
        $this->setConsoleEnvironment(true);
        $factory = new ViewManagerFactory();
        $result  = $factory($this->createContainer(), 'ViewManager');
        $this->assertInstanceOf(ConsoleViewManager::class, $result);
    }

    public function testReturnsHttpViewManagerInNonConsoleEnvironment()
    {
        $this->setConsoleEnvironment(false);
        $factory = new ViewManagerFactory();
        $result  = $factory($this->createContainer(), 'ViewManager');
        $this->assertInstanceOf(HttpViewManager::class, $result);
    }
}
