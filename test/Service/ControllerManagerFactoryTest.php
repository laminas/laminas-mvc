<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Mvc\Service;

use PHPUnit\Framework\TestCase;
use Zend\EventManager\SharedEventManager;
use Zend\Mvc\Service\ControllerManagerFactory;
use Zend\Mvc\Service\ControllerPluginManagerFactory;
use Zend\Mvc\Service\EventManagerFactory;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\Exception;
use Zend\ServiceManager\Factory\InvokableFactory;
use Zend\ServiceManager\ServiceManager;
use ZendTest\Mvc\Controller\Plugin\TestAsset\SamplePlugin;
use ZendTest\Mvc\Controller\TestAsset\SampleController;
use ZendTest\Mvc\Service\TestAsset\InvalidDispatchableClass;

class ControllerManagerFactoryTest extends TestCase
{
    /**
     * @var ServiceManager
     */
    protected $services;

    /**
     * @var \Zend\Mvc\Controller\ControllerManager
     */
    protected $loader;

    public function setUp()
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
                $this->assertNotContains('Should not instantiate this', $e->getMessage());
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
        $loader->setAlias('ZendTest\Dispatchable', TestAsset\Dispatchable::class);
        $loader->setFactory(TestAsset\Dispatchable::class, InvokableFactory::class);

        $controller = $loader->get('ZendTest\Dispatchable');
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
