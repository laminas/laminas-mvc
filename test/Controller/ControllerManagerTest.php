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
use Laminas\ServiceManager\Config;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit_Framework_TestCase as TestCase;
use ReflectionClass;

class ControllerManagerTest extends TestCase
{
    public function setUp()
    {
        $this->sharedEvents   = new SharedEventManager;
        $this->events         = $this->createEventManager($this->sharedEvents);
        $this->consoleAdapter = new ConsoleAdapter();

        $this->services = new ServiceManager();
        (new Config([
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
        ]))->configureServiceManager($this->services);

        $this->controllers = new ControllerManager($this->services);
    }

    /**
     * Create an event manager instance based on laminas-eventmanager version
     *
     * @param SharedEventManager
     * @return EventManager
     */
    protected function createEventManager($sharedManager)
    {
        $r = new ReflectionClass(EventManager::class);

        if ($r->hasMethod('setSharedManager')) {
            $events = new EventManager();
            $events->setSharedManager($sharedManager);
            return $events;
        }

        return new EventManager($sharedManager);
    }

    public function testCanInjectEventManager()
    {
        $controller = new TestAsset\SampleController();

        // Vary injection based on laminas-servicemanager version
        if (method_exists($this->controllers, 'configure')) {
            // v3
            $this->controllers->injectEventManager($this->services, $controller);
        } else {
            // v2
            $this->controllers->injectEventManager($controller, $this->controllers);
        }

        // The default AbstractController implementation lazy instantiates an EM
        // instance, which means we need to check that that instance gets injected
        // with the shared EM instance.
        $events = $controller->getEventManager();
        $this->assertInstanceOf('Laminas\EventManager\EventManagerInterface', $events);
        $this->assertSame($this->sharedEvents, $events->getSharedManager());
    }

    public function testCanInjectConsoleAdapter()
    {
        $controller = new TestAsset\ConsoleController();

        // Vary injection based on laminas-servicemanager version
        if (method_exists($this->controllers, 'configure')) {
            // v3
            $this->controllers->injectConsole($this->services, $controller);
        } else {
            // v2
            $this->controllers->injectConsole($controller, $this->controllers);
        }

        $this->assertInstanceOf('Laminas\Console\Adapter\AdapterInterface', $controller->getConsole());
    }

    public function testCanInjectPluginManager()
    {
        $controller = new TestAsset\SampleController();

        // Vary injection based on laminas-servicemanager version
        if (method_exists($this->controllers, 'configure')) {
            // v3
            $this->controllers->injectPluginManager($this->services, $controller);
        } else {
            // v2
            $this->controllers->injectPluginManager($controller, $this->controllers);
        }

        $this->assertSame($this->services->get('ControllerPluginManager'), $controller->getPluginManager());
    }

    public function testInjectEventManagerWillNotOverwriteExistingEventManagerIfItAlreadyHasASharedManager()
    {
        $events     = $this->createEventManager($this->sharedEvents);
        $controller = new TestAsset\SampleController();
        $controller->setEventManager($events);

        // Vary injection based on laminas-servicemanager version
        if (method_exists($this->controllers, 'configure')) {
            // v3
            $this->controllers->injectEventManager($this->services, $controller);
        } else {
            // v2
            $this->controllers->injectEventManager($controller, $this->controllers);
        }

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
