<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Mvc\Service;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\Mvc\Service\ConsoleViewManagerFactory;
use Zend\Mvc\View\Console\ViewManager;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;

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
