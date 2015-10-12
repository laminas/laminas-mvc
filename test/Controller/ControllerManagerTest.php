<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Mvc\Controller;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\EventManager\EventManager;
use Zend\EventManager\SharedEventManager;
use Zend\Mvc\Controller\ControllerManager;
use Zend\Mvc\Controller\PluginManager as ControllerPluginManager;
use Zend\ServiceManager\ServiceManager;
use Zend\Console\Adapter\Virtual as ConsoleAdapter;

class ControllerManagerTest extends TestCase
{
    public function setUp()
    {
        $this->sharedEvents   = new SharedEventManager;
        $this->events         = new EventManager($this->sharedEvents);
        $this->consoleAdapter = new ConsoleAdapter();

        $this->services = new ServiceManager([
            'factories' => [
                'ControllerPluginManager' => function ($services) {
                    return new ControllerPluginManager($services);
                },
            ],
            'services' => [
                'Console'            => $this->consoleAdapter,
                'EventManager'       => $this->events,
                'SharedEventManager' => $this->sharedEvents,
            ],
        ]);

        $this->controllers = new ControllerManager($this->services);
    }

    public function testCanInjectEventManager()
    {
        $controller = new TestAsset\SampleController();
        $this->controllers->injectEventManager($this->services, $controller);

        // The default AbstractController implementation lazy instantiates an EM
        // instance, which means we need to check that that instance gets injected
        // with the shared EM instance.
        $events = $controller->getEventManager();
        $this->assertInstanceOf('Zend\EventManager\EventManagerInterface', $events);
        $this->assertSame($this->sharedEvents, $events->getSharedManager());
    }

    public function testCanInjectConsoleAdapter()
    {
        $controller = new TestAsset\ConsoleController();
        $this->controllers->injectConsole($this->services, $controller);
        $this->assertInstanceOf('Zend\Console\Adapter\AdapterInterface', $controller->getConsole());
    }

    public function testCanInjectPluginManager()
    {
        $controller = new TestAsset\SampleController();
        $this->controllers->injectPluginManager($this->services, $controller);
        $this->assertSame($this->services->get('ControllerPluginManager'), $controller->getPluginManager());
    }

    public function testInjectEventManagerWillNotOverwriteExistingEventManagerIfItAlreadyHasASharedManager()
    {
        $events     = new EventManager($this->sharedEvents);
        $controller = new TestAsset\SampleController();
        $controller->setEventManager($events);
        $this->controllers->injectEventManager($this->services, $controller);
        $this->assertSame($events, $controller->getEventManager());
        $this->assertSame($this->sharedEvents, $events->getSharedManager());
    }

    /**
     * @covers Zend\ServiceManager\ServiceManager::has
     * @covers Zend\ServiceManager\AbstractPluginManager::get
     */
    public function testDoNotUsePeeringServiceManagers()
    {
        $this->assertFalse($this->controllers->has('EventManager'));
        $this->setExpectedException('Zend\ServiceManager\Exception\ServiceNotFoundException');
        $this->controllers->get('EventManager');
    }
}
