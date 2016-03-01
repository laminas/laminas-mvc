<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Mvc\Router;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\Mvc\Router\RoutePluginManager;
use Zend\ServiceManager\ServiceManager;

class RoutePluginManagerTest extends TestCase
{
    public function testLoadNonExistentRoute()
    {
        $routes = new RoutePluginManager(new ServiceManager());
        $this->setExpectedException('Zend\ServiceManager\Exception\ServiceNotFoundException');
        $routes->get('foo');
    }

    public function testCanLoadAnyRoute()
    {
        $routes = new RoutePluginManager(new ServiceManager(), ['invokables' => [
            'DummyRoute' => 'ZendTest\Mvc\Router\TestAsset\DummyRoute',
        ]]);
        $route = $routes->get('DummyRoute');

        $this->assertInstanceOf('ZendTest\Mvc\Router\TestAsset\DummyRoute', $route);
    }
}
