<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Mvc\Service;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\EventManager\SharedEventManager;
use Zend\Mvc\Service\ControllerManagerFactory;
use Zend\Mvc\Service\ControllerPluginManagerFactory;
use Zend\Mvc\Service\EventManagerFactory;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\ServiceManager;

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
            'invokables' => [
                'SharedEventManager' => SharedEventManager::class,
            ],
            'factories' => [
                'ControllerManager'       => $loaderFactory,
                'ControllerPluginManager' => ControllerPluginManagerFactory::class,
                'EventManager'            => EventManagerFactory::class,
            ],
            'services' => [
                'config' => [],
            ],
        ];
        $this->services = new ServiceManager($this->defaultServiceConfig);
    }

    public function testCannotLoadInvalidDispatchable()
    {
        $loader = $this->services->get('ControllerManager');

        // Ensure the class exists and can be autoloaded
        $this->assertTrue(class_exists('ZendTest\Mvc\Service\TestAsset\InvalidDispatchableClass'));

        try {
            $loader->get('ZendTest\Mvc\Service\TestAsset\InvalidDispatchableClass');
            $this->fail('Retrieving the invalid dispatchable should fail');
        } catch (\Exception $e) {
            do {
                $this->assertNotContains('Should not instantiate this', $e->getMessage());
            } while ($e = $e->getPrevious());
        }
    }

    public function testCannotLoadControllerFromPeer()
    {
        $services = new ServiceManager(array_merge_recursive($this->defaultServiceConfig, ['services' => [
            'foo' => $this,
        ]]));
        $loader = $services->get('ControllerManager');

        $this->setExpectedException('Zend\ServiceManager\Exception\ExceptionInterface');
        $loader->get('foo');
    }

    public function testControllerLoadedCanBeInjectedWithValuesFromPeer()
    {
        $loader = $this->services->get('ControllerManager');
        $loader = $loader->withConfig(['invokables' => [
            'ZendTest\Dispatchable' => TestAsset\Dispatchable::class,
        ]]);

        $controller = $loader->get('ZendTest\Dispatchable');
        $this->assertInstanceOf(TestAsset\Dispatchable::class, $controller);
        $this->assertSame($this->services->get('EventManager'), $controller->getEventManager());
        $this->assertSame($this->services->get('ControllerPluginManager'), $controller->getPluginManager());
    }

    public function testCallPluginWithControllerPluginManager()
    {
        $controllerPluginManager = $this->services->get('ControllerPluginManager');
        $controllerPluginManager = $controllerPluginManager->withConfig([
            'invokables' => [
                'samplePlugin' => 'ZendTest\Mvc\Controller\Plugin\TestAsset\SamplePlugin',
            ],
        ]);

        $controller    = new \ZendTest\Mvc\Controller\TestAsset\SampleController;
        $controllerPluginManager->setController($controller);

        $services = $this->services->withConfig(['services' => [
            'ControllerPluginManager' => $controllerPluginManager,
        ]]);

        $plugin = $controllerPluginManager->get('samplePlugin');
        $this->assertEquals($controller, $plugin->getController());
    }
}
