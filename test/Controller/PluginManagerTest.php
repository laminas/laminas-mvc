<?php

namespace LaminasTest\Mvc\Controller;

use LaminasTest\Mvc\Controller\Plugin\TestAsset\SamplePluginWithConstructor;
use LaminasTest\Mvc\Controller\Plugin\TestAsset\SamplePluginFactory;
use LaminasTest\Mvc\Controller\Plugin\TestAsset\SamplePluginWithConstructorFactory;
use Laminas\Mvc\Controller\PluginManager;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Laminas\ServiceManager\ServiceManager;
use LaminasTest\Mvc\Controller\Plugin\TestAsset\SamplePlugin;
use LaminasTest\Mvc\Controller\TestAsset\SampleController;
use PHPUnit\Framework\TestCase;

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
            'aliases'   => ['samplePlugin' => SamplePluginWithConstructor::class],
            'factories' => [SamplePluginWithConstructor::class => InvokableFactory::class],
        ]);
        $plugin = $pluginManager->get('samplePlugin');
        $this->assertEquals($plugin->getBar(), 'baz');
    }

    public function testGetWithConstructorAndOptions()
    {
        $pluginManager = new PluginManager(new ServiceManager(), [
            'aliases'   => ['samplePlugin' => SamplePluginWithConstructor::class],
            'factories' => [SamplePluginWithConstructor::class => InvokableFactory::class],
        ]);
        $plugin = $pluginManager->get('samplePlugin', ['foo']);
        $this->assertEquals($plugin->getBar(), ['foo']);
    }

    public function testCanCreateByFactory()
    {
        $pluginManager = new PluginManager(new ServiceManager(), [
            'factories' => [
                'samplePlugin' => SamplePluginFactory::class,
            ]
        ]);
        $plugin = $pluginManager->get('samplePlugin');
        $this->assertInstanceOf(SamplePlugin::class, $plugin);
    }

    public function testCanCreateByFactoryWithConstrutor()
    {
        $pluginManager = new PluginManager(new ServiceManager(), [
            'factories' => [
                'samplePlugin' => SamplePluginWithConstructorFactory::class,
            ],
        ]);
        $plugin = $pluginManager->get('samplePlugin', ['foo']);
        $this->assertInstanceOf(SamplePluginWithConstructor::class, $plugin);
        $this->assertEquals($plugin->getBar(), ['foo']);
    }
}
