<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\Service;

use Exception;
use Laminas\EventManager\SharedEventManager;
use Laminas\Mvc\Controller\ControllerManager;
use Laminas\Mvc\Service\ControllerManagerFactory;
use Laminas\Mvc\Service\ControllerPluginManagerFactory;
use Laminas\Mvc\Service\EventManagerFactory;
use Laminas\ServiceManager\Config;
use Laminas\ServiceManager\Exception\ExceptionInterface;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Laminas\ServiceManager\ServiceManager;
use LaminasTest\Mvc\Controller\Plugin\TestAsset\SamplePlugin;
use LaminasTest\Mvc\Controller\TestAsset\SampleController;
use LaminasTest\Mvc\Service\TestAsset\Dispatchable;
use LaminasTest\Mvc\Service\TestAsset\InvalidDispatchableClass;
use PHPUnit\Framework\TestCase;

use function array_merge_recursive;
use function class_exists;

class ControllerManagerFactoryTest extends TestCase
{
    /** @var ServiceManager */
    protected $services;

    /** @var ControllerManager */
    protected $loader;
    private array $defaultServiceConfig;

    public function setUp(): void
    {
        $loaderFactory              = new ControllerManagerFactory();
        $this->defaultServiceConfig = [
            'aliases'   => [
                'SharedEventManager' => SharedEventManager::class,
            ],
            'factories' => [
                'ControllerManager'       => $loaderFactory,
                'ControllerPluginManager' => ControllerPluginManagerFactory::class,
                'EventManager'            => EventManagerFactory::class,
                SharedEventManager::class => InvokableFactory::class,
            ],
            'services'  => [
                'config' => [],
            ],
        ];
        $this->services             = new ServiceManager();
        (new Config($this->defaultServiceConfig))->configureServiceManager($this->services);
    }

    public function testCannotLoadInvalidDispatchable(): void
    {
        $loader = $this->services->get('ControllerManager');

        // Ensure the class exists and can be autoloaded
        $this->assertTrue(class_exists(InvalidDispatchableClass::class));

        $loader->setFactory(InvalidDispatchableClass::class, InvokableFactory::class);

        try {
            $loader->get(InvalidDispatchableClass::class);
            $this->fail('Retrieving the invalid dispatchable should fail');
        } catch (Exception $e) {
            do {
                $this->assertStringContainsString('Should not instantiate this', $e->getMessage());
            } while ($e = $e->getPrevious());
        }
    }

    public function testCannotLoadControllerFromPeer(): void
    {
        $services = new ServiceManager();
        (new Config(array_merge_recursive($this->defaultServiceConfig, [
            'services' => [
                'foo' => $this,
            ],
        ])))->configureServiceManager($services);
        $loader = $services->get('ControllerManager');

        $this->expectException(ExceptionInterface::class);
        $loader->get('foo');
    }

    public function testControllerLoadedCanBeInjectedWithValuesFromPeer(): void
    {
        $loader = $this->services->get('ControllerManager');
        $loader->setAlias('LaminasTest\Dispatchable', Dispatchable::class);
        $loader->setFactory(Dispatchable::class, InvokableFactory::class);

        $controller = $loader->get('LaminasTest\Dispatchable');
        $this->assertInstanceOf(Dispatchable::class, $controller);
        $this->assertSame($this->services->get('EventManager'), $controller->getEventManager());
        $this->assertSame($this->services->get('ControllerPluginManager'), $controller->getPluginManager());
    }

    public function testCallPluginWithControllerPluginManager(): void
    {
        $controllerPluginManager = $this->services->get('ControllerPluginManager');
        $controllerPluginManager->setAlias('samplePlugin', SamplePlugin::class);
        $controllerPluginManager->setFactory(SamplePlugin::class, InvokableFactory::class);

        $controller = new SampleController();
        $controllerPluginManager->setController($controller);

        $plugin = $controllerPluginManager->get('samplePlugin');
        $this->assertEquals($controller, $plugin->getController());
    }
}
