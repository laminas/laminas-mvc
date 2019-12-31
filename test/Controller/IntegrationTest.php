<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Controller;

use Laminas\EventManager\SharedEventManager;
use Laminas\Mvc\Controller\ControllerManager;
use Laminas\Mvc\Controller\PluginManager;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit_Framework_TestCase as TestCase;

class IntegrationTest extends TestCase
{
    public function setUp()
    {
        $this->plugins      = new PluginManager();
        $this->sharedEvents = new SharedEventManager();
        $this->services     = new ServiceManager();
        $this->services->setService('ControllerPluginManager', $this->plugins);
        $this->services->setService('SharedEventManager', $this->sharedEvents);
        $this->services->setService('Laminas\ServiceManager\ServiceLocatorInterface', $this->services);

        $this->controllers = new ControllerManager();
        $this->controllers->setServiceLocator($this->services);
    }

    public function testPluginReceivesCurrentController()
    {
        $this->controllers->setInvokableClass('first', 'LaminasTest\Mvc\Controller\TestAsset\SampleController');
        $this->controllers->setInvokableClass('second', 'LaminasTest\Mvc\Controller\TestAsset\SampleController');

        $first  = $this->controllers->get('first');
        $second = $this->controllers->get('second');
        $this->assertNotSame($first, $second);

        $plugin1 = $first->plugin('url');
        $this->assertSame($first, $plugin1->getController());

        $plugin2 = $second->plugin('url');
        $this->assertSame($second, $plugin2->getController());

        $this->assertSame($plugin1, $plugin2);
    }
}
