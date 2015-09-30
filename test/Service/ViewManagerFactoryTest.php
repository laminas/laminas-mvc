<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Mvc\Service;

use Interop\Container\ContainerInterface;
use PHPUnit_Framework_TestCase as TestCase;
use Zend\Mvc\Service\ViewManagerFactory;
use Zend\Mvc\View\Console\ViewManager as ConsoleViewManager;
use Zend\Mvc\View\Http\ViewManager as HttpViewManager;

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
