<?php

namespace LaminasTest\Mvc\Controller;

use LaminasTest\Mvc\Controller\TestAsset\SampleController;
use Laminas\EventManager\EventManager;
use Laminas\EventManager\SharedEventManager;
use Laminas\Mvc\Controller\ControllerManager;
use Laminas\Mvc\Controller\PluginManager;
use Laminas\ServiceManager\Config;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;

class IntegrationTest extends TestCase
{
    private SharedEventManager $sharedEvents;
    private ServiceManager $services;

    public function setUp(): void
    {
        $this->sharedEvents = new SharedEventManager();

        $this->services = new ServiceManager();
        (new Config([
            'services' => [
                'SharedEventManager' => $this->sharedEvents,
            ],
            'factories' => [
                'ControllerPluginManager' => static fn($services): PluginManager => new PluginManager($services),
                'EventManager' => fn(): EventManager => new EventManager($this->sharedEvents),
            ],
            'shared' => [
                'EventManager' => false,
            ],
        ]))->configureServiceManager($this->services);
    }

    public function testPluginReceivesCurrentController()
    {
        $controllers = new ControllerManager($this->services, ['factories' => [
            'first'  => static fn($services): SampleController => new SampleController(),
            'second' => static fn($services): SampleController => new SampleController(),
        ]]);

        $first  = $controllers->get('first');
        $second = $controllers->get('second');
        $this->assertNotSame($first, $second);

        $plugin1 = $first->plugin('url');
        $this->assertSame($first, $plugin1->getController());

        $plugin2 = $second->plugin('url');
        $this->assertSame($second, $plugin2->getController());

        $this->assertSame($plugin1, $plugin2);
    }
}
