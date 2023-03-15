<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\Controller;

use Laminas\EventManager\EventManager;
use Laminas\EventManager\EventManagerInterface;
use Laminas\EventManager\SharedEventManager;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\Mvc\Controller\ControllerManager;
use Laminas\Mvc\Controller\PluginManager as ControllerPluginManager;
use Laminas\ServiceManager\Config;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\ServiceManager;
use LaminasTest\Mvc\Controller\TestAsset\SampleController;
use PHPUnit\Framework\TestCase;

class ControllerManagerTest extends TestCase
{
    private SharedEventManager $sharedEvents;
    private EventManager $events;
    private ServiceManager $services;
    private ControllerManager $controllers;

    public function setUp(): void
    {
        $this->sharedEvents = new SharedEventManager();
        $this->events       = $this->createEventManager($this->sharedEvents);

        $this->services = new ServiceManager();
        (new Config([
            'factories' => [
                'ControllerPluginManager' => static fn($services): ControllerPluginManager
                    => new ControllerPluginManager($services),
            ],
            'services'  => [
                'EventManager'       => $this->events,
                'SharedEventManager' => $this->sharedEvents,
            ],
        ]))->configureServiceManager($this->services);

        $this->controllers = new ControllerManager($this->services);
    }

    protected function createEventManager(SharedEventManagerInterface $sharedManager): EventManager
    {
        return new EventManager($sharedManager);
    }

    public function testCanInjectEventManager(): void
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

    public function testCanInjectPluginManager(): void
    {
        $controller = new SampleController();

        $this->controllers->injectPluginManager($this->services, $controller);

        $this->assertSame($this->services->get('ControllerPluginManager'), $controller->getPluginManager());
    }

    public function testInjectEventManagerWillNotOverwriteExistingEventManagerIfItAlreadyHasASharedManager(): void
    {
        $events     = $this->createEventManager($this->sharedEvents);
        $controller = new SampleController();
        $controller->setEventManager($events);

        $this->controllers->injectEventManager($this->services, $controller);

        $this->assertSame($events, $controller->getEventManager());
        $this->assertSame($this->sharedEvents, $events->getSharedManager());
    }

    /**
     * @covers \Laminas\ServiceManager\ServiceManager::has
     * @covers \Laminas\ServiceManager\AbstractPluginManager::get
     */
    public function testDoNotUsePeeringServiceManagers(): void
    {
        $this->assertFalse($this->controllers->has('EventManager'));
        $this->expectException(ServiceNotFoundException::class);
        $this->controllers->get('EventManager');
    }
}
