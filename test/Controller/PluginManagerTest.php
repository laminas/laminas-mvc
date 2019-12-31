<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Controller;

use Laminas\Authentication\AuthenticationService;
use Laminas\Mvc\Controller\PluginManager;
use Laminas\ServiceManager\Config;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Laminas\ServiceManager\ServiceManager;
use LaminasTest\Mvc\Controller\Plugin\TestAsset\SamplePlugin;
use LaminasTest\Mvc\Controller\TestAsset\SampleController;
use PHPUnit_Framework_TestCase as TestCase;

class PluginManagerTest extends TestCase
{
    public function testPluginManagerInjectsControllerInPlugin()
    {
        $controller    = new SampleController;
        $pluginManager = new PluginManager(new ServiceManager(), [
            'aliases'   => ['samplePlugin' => SamplePlugin::class],
            'factories' => [SamplePlugin::class => InvokableFactory::class],
        ]);
        $pluginManager->setController($controller);

        $plugin = $pluginManager->get('samplePlugin');
        $this->assertEquals($controller, $plugin->getController());
    }

    public function testPluginManagerInjectsControllerForExistingPlugin()
    {
        $controller1   = new SampleController;
        $pluginManager = new PluginManager(new ServiceManager(), [
            'aliases'   => ['samplePlugin' => SamplePlugin::class],
            'factories' => [SamplePlugin::class => InvokableFactory::class],
        ]);
        $pluginManager->setController($controller1);

        // Plugin manager registers now instance of SamplePlugin
        $pluginManager->get('samplePlugin');

        $controller2   = new SampleController;
        $pluginManager->setController($controller2);

        $plugin = $pluginManager->get('samplePlugin');
        $this->assertEquals($controller2, $plugin->getController());
    }

    public function testGetWithConstructor()
    {
        $pluginManager = new PluginManager(new ServiceManager(), [
            'aliases'   => ['samplePlugin' => Plugin\TestAsset\SamplePluginWithConstructor::class],
            'factories' => [Plugin\TestAsset\SamplePluginWithConstructor::class => InvokableFactory::class],
        ]);
        $plugin = $pluginManager->get('samplePlugin');
        $this->assertEquals($plugin->getBar(), 'baz');
    }

    public function testGetWithConstructorAndOptions()
    {
        $pluginManager = new PluginManager(new ServiceManager(), [
            'aliases'   => ['samplePlugin' => Plugin\TestAsset\SamplePluginWithConstructor::class],
            'factories' => [Plugin\TestAsset\SamplePluginWithConstructor::class => InvokableFactory::class],
        ]);
        $plugin = $pluginManager->get('samplePlugin', ['foo']);
        $this->assertEquals($plugin->getBar(), ['foo']);
    }

    public function testDefinesFactoryForIdentityPlugin()
    {
        $pluginManager = new PluginManager(new ServiceManager());
        $this->assertTrue($pluginManager->has('identity'));
    }

    public function testIdentityFactoryCanInjectAuthenticationServiceIfInParentServiceManager()
    {
        $services = new ServiceManager();
        (new Config([
            'factories' => [
                AuthenticationService::class => InvokableFactory::class,
            ],
        ]))->configureServiceManager($services);
        $pluginManager = new PluginManager($services);
        $identity = $pluginManager->get('identity');
        $expected = $services->get(AuthenticationService::class);
        $this->assertSame($expected, $identity->getAuthenticationService());
    }

    public function testCanCreateByFactory()
    {
        $pluginManager = new PluginManager(new ServiceManager(), [
            'factories' => [
                'samplePlugin' => Plugin\TestAsset\SamplePluginFactory::class,
            ]
        ]);
        $plugin = $pluginManager->get('samplePlugin');
        $this->assertInstanceOf(SamplePlugin::class, $plugin);
    }

    public function testCanCreateByFactoryWithConstrutor()
    {
        $pluginManager = new PluginManager(new ServiceManager(), [
            'factories' => [
                'samplePlugin' => Plugin\TestAsset\SamplePluginWithConstructorFactory::class,
            ],
        ]);
        $plugin = $pluginManager->get('samplePlugin', ['foo']);
        $this->assertInstanceOf(Plugin\TestAsset\SamplePluginWithConstructor::class, $plugin);
        $this->assertEquals($plugin->getBar(), ['foo']);
    }
}
