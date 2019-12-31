<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Controller;

use Laminas\Console\Adapter\Virtual as ConsoleAdapter;
use Laminas\EventManager\EventManager;
use Laminas\EventManager\SharedEventManager;
use Laminas\Mvc\Controller\ControllerManager;
use Laminas\Mvc\Controller\PluginManager as ControllerPluginManager;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit_Framework_TestCase as TestCase;

class ControllerManagerTest extends TestCase
{
    public function setUp()
    {
        $this->events       = new EventManager();
        $this->consoleAdapter = new ConsoleAdapter();
        $this->sharedEvents = new SharedEventManager;
        $this->events->setSharedManager($this->sharedEvents);

        $this->plugins  = new ControllerPluginManager();
        $this->services = new ServiceManager();
        $this->services->setService('Console', $this->consoleAdapter);
        $this->services->setService('Laminas\ServiceManager\ServiceLocatorInterface', $this->services);
        $this->services->setService('EventManager', $this->events);
        $this->services->setService('SharedEventManager', $this->sharedEvents);
        $this->services->setService('ControllerPluginManager', $this->plugins);

        $this->controllers = new ControllerManager();
        $this->controllers->setServiceLocator($this->services);
        $this->controllers->addPeeringServiceManager($this->services);
    }

    public function testInjectControllerDependenciesInjectsExpectedDependencies()
    {
        $controller = new TestAsset\SampleController();
        $this->controllers->injectControllerDependencies($controller, $this->controllers);
        $this->assertSame($this->services, $controller->getServiceLocator());
        $this->assertSame($this->plugins, $controller->getPluginManager());

        // The default AbstractController implementation lazy instantiates an EM
        // instance, which means we need to check that that instance gets injected
        // with the shared EM instance.
        $events = $controller->getEventManager();
        $this->assertInstanceOf('Laminas\EventManager\EventManagerInterface', $events);
        $this->assertSame($this->sharedEvents, $events->getSharedManager());
    }

    public function testInjectControllerDependenciesToConsoleController()
    {
        $controller = new TestAsset\ConsoleController();
        $this->controllers->injectControllerDependencies($controller, $this->controllers);
        $this->assertInstanceOf('Laminas\Console\Adapter\AdapterInterface', $controller->getConsole());
    }

    public function testInjectControllerDependenciesWillNotOverwriteExistingEventManager()
    {
        $events     = new EventManager();
        $controller = new TestAsset\SampleController();
        $controller->setEventManager($events);
        $this->controllers->injectControllerDependencies($controller, $this->controllers);
        $this->assertSame($events, $controller->getEventManager());
        $this->assertSame($this->sharedEvents, $events->getSharedManager());
    }

    /**
     * @covers Laminas\ServiceManager\ServiceManager::has
     * @covers Laminas\ServiceManager\AbstractPluginManager::get
     */
    public function testDoNotUsePeeringServiceManagers()
    {
        $this->assertFalse($this->controllers->has('EventManager'));
        $this->setExpectedException('Laminas\ServiceManager\Exception\ServiceNotFoundException');
        $this->controllers->get('EventManager');
    }
}
