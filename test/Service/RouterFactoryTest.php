<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Service;

use Laminas\Mvc\Router\RoutePluginManager;
use Laminas\Mvc\Service\RouterFactory;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit_Framework_TestCase as TestCase;

class RouterFactoryTest extends TestCase
{
    public function setUp()
    {
        $this->services = new ServiceManager();
        $this->services->setService('RoutePluginManager', new RoutePluginManager());
        $this->factory  = new RouterFactory();
    }

    public function testFactoryCanCreateRouterBasedOnConfiguredName()
    {
        $this->services->setService('Config', array(
            'router' => array(
                'router_class' => 'LaminasTest\Mvc\Service\TestAsset\Router',
            ),
            'console' => array(
                'router' => array(
                    'router_class' => 'LaminasTest\Mvc\Service\TestAsset\Router',
                ),
            ),
        ));

        $router = $this->factory->createService($this->services, 'router', 'Router');
        $this->assertInstanceOf('LaminasTest\Mvc\Service\TestAsset\Router', $router);
    }

    public function testFactoryCanCreateRouterWhenOnlyHttpRouterConfigPresent()
    {
        $this->services->setService('Config', array(
            'router' => array(
                'router_class' => 'LaminasTest\Mvc\Service\TestAsset\Router',
            ),
        ));

        $router = $this->factory->createService($this->services, 'router', 'Router');
        $this->assertInstanceOf('Laminas\Mvc\Router\Console\SimpleRouteStack', $router);
    }
}
