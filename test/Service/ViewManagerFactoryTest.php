<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Service;

use Interop\Container\ContainerInterface;
use Laminas\Mvc\Service\ViewManagerFactory;
use Laminas\Mvc\View\Http\ViewManager as HttpViewManager;
use PHPUnit_Framework_TestCase as TestCase;

class ViewManagerFactoryTest extends TestCase
{
    private function createContainer()
    {
        $http      = $this->prophesize(HttpViewManager::class);
        $container = $this->prophesize(ContainerInterface::class);
        $container->get('HttpViewManager')->will(function () use ($http) {
            return $http->reveal();
        });
        return $container->reveal();
    }

    public function testReturnsHttpViewManager()
    {
        $factory = new ViewManagerFactory();
        $result  = $factory($this->createContainer(), 'ViewManager');
        $this->assertInstanceOf(HttpViewManager::class, $result);
    }
}
