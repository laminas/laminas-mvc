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
use Zend\Mvc\Controller\PluginManager;
use Zend\ServiceManager\Factory\InvokableFactory;
use Zend\ServiceManager\ServiceManager;
use ZendTest\Mvc\Controller\TestAsset\SampleController;
use ZendTest\Mvc\Controller\Plugin\TestAsset\SamplePlugin;

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
