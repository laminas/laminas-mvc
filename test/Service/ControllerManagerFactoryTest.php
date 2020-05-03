<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Service;

use Laminas\EventManager\SharedEventManager;
use Laminas\Mvc\Service\ControllerManagerFactory;
use Laminas\Mvc\Service\ControllerPluginManagerFactory;
use Laminas\Mvc\Service\EventManagerFactory;
use Laminas\ServiceManager\Config;
use Laminas\ServiceManager\Exception;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Laminas\ServiceManager\ServiceManager;
use LaminasTest\Mvc\Controller\Plugin\TestAsset\SamplePlugin;
use LaminasTest\Mvc\Controller\TestAsset\SampleController;
use LaminasTest\Mvc\Service\TestAsset\InvalidDispatchableClass;
use PHPUnit\Framework\TestCase;

class ControllerManagerFactoryTest extends TestCase
{
    /**
     * @var ServiceManager
     */
    protected $services;

    /**
     * @var \Laminas\Mvc\Controller\ControllerManager
     */
    protected $loader;

    protected function setUp() : void
    {
        $loaderFactory  = new ControllerManagerFactory();
        $this->defaultServiceConfig = [
            'aliases' => [
                'SharedEventManager' => SharedEventManager::class,
            ],
            'factories' => [
                'ControllerManager'       => $loaderFactory,
                'ControllerPluginManager' => ControllerPluginManagerFactory::class,
                'EventManager'            => EventManagerFactory::class,
                SharedEventManager::class => InvokableFactory::class,
            ],
            'services' => [
                'config' => [],
            ],
        ];
        $this->services = new ServiceManager();
        (new Config($this->defaultServiceConfig))->configureServiceManager($this->services);
    }

    public function testCannotLoadInvalidDispatchable()
    {
        $loader = $this->services->get('ControllerManager');

        // Ensure the class exists and can be autoloaded
        $this->assertTrue(class_exists(InvalidDispatchableClass::class));

        try {
            $loader->get(InvalidDispatchableClass::class);
            $this->fail('Retrieving the invalid dispatchable should fail');
        } catch (\Exception $e) {
            do {
                $this->assertStringNotContainsString('Should not instantiate this', $e->getMessage());
            } while ($e = $e->getPrevious());
        }
    }

    public function testCannotLoadControllerFromPeer()
    {
        $services = new ServiceManager();
        (new Config(array_merge_recursive($this->defaultServiceConfig, ['services' => [
            'foo' => $this,
        ]])))->configureServiceManager($services);
        $loader = $services->get('ControllerManager');

        $this->expectException(Exception\ExceptionInterface::class);
        $loader->get('foo');
    }

    public function testControllerLoadedCanBeInjectedWithValuesFromPeer()
    {
        $loader = $this->services->get('ControllerManager');
        $loader->setAlias('LaminasTest\Dispatchable', TestAsset\Dispatchable::class);
        $loader->setFactory(TestAsset\Dispatchable::class, InvokableFactory::class);

        $controller = $loader->get('LaminasTest\Dispatchable');
        $this->assertInstanceOf(TestAsset\Dispatchable::class, $controller);
        $this->assertSame($this->services->get('EventManager'), $controller->getEventManager());
        $this->assertSame($this->services->get('ControllerPluginManager'), $controller->getPluginManager());
    }

    public function testCallPluginWithControllerPluginManager()
    {
        $controllerPluginManager = $this->services->get('ControllerPluginManager');
        $controllerPluginManager->setAlias('samplePlugin', SamplePlugin::class);
        $controllerPluginManager->setFactory(SamplePlugin::class, InvokableFactory::class);

        $controller = new SampleController;
        $controllerPluginManager->setController($controller);

        $plugin = $controllerPluginManager->get('samplePlugin');
        $this->assertEquals($controller, $plugin->getController());
    }
}
