<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Mvc\Controller;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\Authentication\AuthenticationService;
use Zend\Mvc\Controller\PluginManager;
use Zend\ServiceManager\ServiceManager;
use ZendTest\Mvc\Controller\TestAsset\SampleController;
use ZendTest\Mvc\Controller\Plugin\TestAsset\SamplePlugin;

class PluginManagerTest extends TestCase
{
    public function testPluginManagerInjectsControllerInPlugin()
    {
        $controller    = new SampleController;
        $pluginManager = new PluginManager(new ServiceManager(), [
            'invokables' => ['samplePlugin' =>  SamplePlugin::class],
        ]);
        $pluginManager->setController($controller);

        $plugin = $pluginManager->get('samplePlugin');
        $this->assertEquals($controller, $plugin->getController());
    }

    public function testPluginManagerInjectsControllerForExistingPlugin()
    {
        $controller1   = new SampleController;
        $pluginManager = new PluginManager(new ServiceManager(), [
            'invokables' => ['samplePlugin' =>  SamplePlugin::class],
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
            'invokables' => ['samplePlugin' => Plugin\TestAsset\SamplePluginWithConstructor::class],
        ]);
        $plugin = $pluginManager->get('samplePlugin');
        $this->assertEquals($plugin->getBar(), 'baz');
    }

    public function testGetWithConstructorAndOptions()
    {
        $pluginManager = new PluginManager(new ServiceManager(), [
            'invokables' => ['samplePlugin' => Plugin\TestAsset\SamplePluginWithConstructor::class],
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
        $services = new ServiceManager([
            'invokables' => [
                AuthenticationService::class => AuthenticationService::class,
            ],
        ]);
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
