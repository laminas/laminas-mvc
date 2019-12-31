<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Service;

use Laminas\Mvc\Service\ConsoleViewManagerFactory;
use Laminas\Mvc\View\Console\ViewManager;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use PHPUnit_Framework_TestCase as TestCase;

class ConsoleViewManagerFactoryTest extends TestCase
{
    use FactoryEnvironmentTrait;

    public function tearDown()
    {
        $this->setConsoleEnvironment(true);
    }

    public function testRaisesExceptionWhenNotInConsoleEnvironment()
    {
        $this->setConsoleEnvironment(false);
        $factory = new ConsoleViewManagerFactory();
        $this->setExpectedException(ServiceNotCreatedException::class);
        $factory($this->createContainer(), 'ConsoleViewManager');
    }

    public function testReturnsConsoleViewManagerWhenInConsoleEnvironment()
    {
        $this->setConsoleEnvironment(true);
        $factory = new ConsoleViewManagerFactory();
        $result  = $factory($this->createContainer(), 'ConsoleViewManager');
        $this->assertInstanceOf(ViewManager::class, $result);
    }
}
