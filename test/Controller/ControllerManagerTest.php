<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Mvc\Controller;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Zend\EventManager\EventManager;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\SharedEventManager;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\Mvc\Controller\ControllerManager;
use Zend\Mvc\Controller\PluginManager as ControllerPluginManager;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\ServiceManager;
use ZendTest\Mvc\Controller\TestAsset\SampleController;

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
     * @param SharedEventManager
     * @return EventManager
     */
    protected function createEventManager(SharedEventManagerInterface $sharedManager)
    {
        return new EventManager($sharedManager);
    }

    public function testCanInjectEventManager()
    {
        $controller = new SampleController();

        $this->controllers->injectEventManager($this->services, $controller);

        // The default AbstractController implementation lazy instantiates an EM
        // instance, which means we need to check that that instance gets injected
        // with the shared EM instance.
        $events = $controller->getEventManager();
        $this->assertInstanceOf(EventManagerInterface::class, $events);
        $this->assertSame($this->sharedEvents, $events->getSharedManager());
    }

    public function testCanInjectPluginManager()
    {
        $controller = new SampleController();

        $this->controllers->injectPluginManager($this->services, $controller);

        $this->assertSame($this->services->get('ControllerPluginManager'), $controller->getPluginManager());
    }

    public function testInjectEventManagerWillNotOverwriteExistingEventManagerIfItAlreadyHasASharedManager()
    {
        $events     = $this->createEventManager($this->sharedEvents);
        $controller = new SampleController();
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
        $this->expectException(ServiceNotFoundException::class);
        $this->controllers->get('EventManager');
    }
}
