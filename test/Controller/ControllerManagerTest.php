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
use ReflectionClass;
use Zend\EventManager\EventManager;
use Zend\EventManager\SharedEventManager;
use Zend\Mvc\Controller\ControllerManager;
use Zend\Mvc\Controller\PluginManager as ControllerPluginManager;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\ServiceManager;

class ControllerManagerTest extends TestCase
{
    public function setUp()
    {
        $this->sharedEvents   = new SharedEventManager;
        $this->events         = $this->createEventManager($this->sharedEvents);

        $this->services = new ServiceManager();
        (new Config([
            'factories' => [
                'ControllerPluginManager' => function ($services) {
                    return new ControllerPluginManager($services);
                },
            ],
            'services' => [
                'EventManager'       => $this->events,
                'SharedEventManager' => $this->sharedEvents,
            ],
        ]))->configureServiceManager($this->services);

        $this->controllers = new ControllerManager($this->services);
    }

    /**
     * Create an event manager instance based on zend-eventmanager version
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

        // Vary injection based on zend-servicemanager version
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
        $this->assertInstanceOf('Zend\EventManager\EventManagerInterface', $events);
        $this->assertSame($this->sharedEvents, $events->getSharedManager());
    }

    public function testCanInjectPluginManager()
    {
        $controller = new TestAsset\SampleController();

        // Vary injection based on zend-servicemanager version
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

        // Vary injection based on zend-servicemanager version
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
