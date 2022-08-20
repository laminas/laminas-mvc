<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\Controller;

use Laminas\Mvc\Controller\PluginManager;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Laminas\ServiceManager\ServiceManager;
use LaminasTest\Mvc\Controller\Plugin\TestAsset\SamplePlugin;
use LaminasTest\Mvc\Controller\TestAsset\SampleController;
use PHPUnit\Framework\TestCase;

class PluginManagerTest extends TestCase
{
    public function testPluginManagerInjectsControllerInPlugin(): void
    {
        $controller    = new SampleController();
        $pluginManager = new PluginManager(new ServiceManager(), [
            'aliases'   => ['samplePlugin' => SamplePlugin::class],
            'factories' => [SamplePlugin::class => InvokableFactory::class],
        ]);
        $pluginManager->setController($controller);

        $plugin = $pluginManager->get('samplePlugin');
        $this->assertEquals($controller, $plugin->getController());
    }

    public function testPluginManagerInjectsControllerForExistingPlugin(): void
    {
        $controller1   = new SampleController();
        $pluginManager = new PluginManager(new ServiceManager(), [
            'aliases'   => ['samplePlugin' => SamplePlugin::class],
            'factories' => [SamplePlugin::class => InvokableFactory::class],
        ]);
        $pluginManager->setController($controller1);

        // Plugin manager registers now instance of SamplePlugin
        $pluginManager->get('samplePlugin');

        $controller2 = new SampleController();
        $pluginManager->setController($controller2);

        $plugin = $pluginManager->get('samplePlugin');
        $this->assertEquals($controller2, $plugin->getController());
    }

    public function testGetWithConstructor(): void
    {
        $pluginManager = new PluginManager(new ServiceManager(), [
            'aliases'   => ['samplePlugin' => Plugin\TestAsset\SamplePluginWithConstructor::class],
            'factories' => [Plugin\TestAsset\SamplePluginWithConstructor::class => InvokableFactory::class],
        ]);
        $plugin        = $pluginManager->get('samplePlugin');
        $this->assertEquals('baz', $plugin->getBar());
    }

    public function testGetWithConstructorAndOptions(): void
    {
        $pluginManager = new PluginManager(new ServiceManager(), [
            'aliases'   => ['samplePlugin' => Plugin\TestAsset\SamplePluginWithConstructor::class],
            'factories' => [Plugin\TestAsset\SamplePluginWithConstructor::class => InvokableFactory::class],
        ]);
        $plugin        = $pluginManager->get('samplePlugin', ['foo']);
        $this->assertEquals(['foo'], $plugin->getBar());
    }

    public function testCanCreateByFactory(): void
    {
        $pluginManager = new PluginManager(new ServiceManager(), [
            'factories' => [
                'samplePlugin' => Plugin\TestAsset\SamplePluginFactory::class,
            ],
        ]);
        $plugin        = $pluginManager->get('samplePlugin');
        $this->assertInstanceOf(SamplePlugin::class, $plugin);
    }

    public function testCanCreateByFactoryWithConstructor(): void
    {
        $pluginManager = new PluginManager(new ServiceManager(), [
            'factories' => [
                'samplePlugin' => Plugin\TestAsset\SamplePluginWithConstructorFactory::class,
            ],
        ]);
        $plugin        = $pluginManager->get('samplePlugin', ['foo']);
        $this->assertInstanceOf(Plugin\TestAsset\SamplePluginWithConstructor::class, $plugin);
        $this->assertEquals(['foo'], $plugin->getBar());
    }
}
