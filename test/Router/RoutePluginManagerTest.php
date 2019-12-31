<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Router;

use Laminas\Di\Di;
use Laminas\Mvc\Router\RoutePluginManager;
use Laminas\ServiceManager\Di\DiAbstractServiceFactory;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * @category   Laminas
 * @package    Laminas_Mvc_Router
 * @subpackage UnitTests
 * @group      Laminas_Router
 */
class RoutePluginManagerTest extends TestCase
{
    public function testLoadNonExistentRoute()
    {
        $routes = new RoutePluginManager();
        $this->setExpectedException('Laminas\ServiceManager\Exception\ServiceNotFoundException');
        $routes->get('foo');
    }

    public function testCanLoadAnyRoute()
    {
        $routes = new RoutePluginManager();
        $routes->setInvokableClass('DummyRoute', 'LaminasTest\Mvc\Router\TestAsset\DummyRoute');
        $route = $routes->get('DummyRoute');

        $this->assertInstanceOf('LaminasTest\Mvc\Router\TestAsset\DummyRoute', $route);
    }

    public function shippedRoutes()
    {
        return array(
            'hostname' => array('Laminas\Mvc\Router\Http\Hostname', array('route' => 'example.com')),
            'literal'  => array('Laminas\Mvc\Router\Http\Literal', array('route' => '/example')),
            'regex'    => array('Laminas\Mvc\Router\Http\Regex', array('regex' => '[a-z]+', 'spec' => '%s')),
            'scheme'   => array('Laminas\Mvc\Router\Http\Scheme', array('scheme' => 'http')),
            'segment'  => array('Laminas\Mvc\Router\Http\Segment', array('route' => '/:segment')),
            'wildcard' => array('Laminas\Mvc\Router\Http\Wildcard', array()),
            'query'    => array('Laminas\Mvc\Router\Http\Query', array()),
            'method'   => array('Laminas\Mvc\Router\Http\Method', array('verb' => 'GET')),
        );
    }

    /**
     * @dataProvider shippedRoutes
     */
    public function testDoesNotInvokeDiForShippedRoutes($routeName, $options)
    {
        // Setup route plugin manager
        $routes = new RoutePluginManager();
        foreach ($this->shippedRoutes() as $name => $info) {
            $routes->setInvokableClass($name, $info[0]);
        }

        // Add DI abstract factory
        $di                = new Di;
        $diAbstractFactory = new DiAbstractServiceFactory($di, DiAbstractServiceFactory::USE_SL_BEFORE_DI);
        $routes->addAbstractFactory($diAbstractFactory);

        $this->assertTrue($routes->has($routeName));

        try {
            $route = $routes->get($routeName, $options);
            $this->assertInstanceOf($routeName, $route);
        } catch (\Exception $e) {
            $messages = array();
            do {
                $messages[] = $e->getMessage() . "\n" . $e->getTraceAsString();
            } while ($e = $e->getPrevious());
            $this->fail(implode("\n\n", $messages));
        }
    }

    /**
     * @dataProvider shippedRoutes
     */
    public function testDoesNotInvokeDiForShippedRoutesUsingShortName($routeName, $options)
    {
        // Setup route plugin manager
        $routes = new RoutePluginManager();
        foreach ($this->shippedRoutes() as $name => $info) {
            $routes->setInvokableClass($name, $info[0]);
        }

        // Add DI abstract factory
        $di                = new Di;
        $diAbstractFactory = new DiAbstractServiceFactory($di, DiAbstractServiceFactory::USE_SL_BEFORE_DI);
        $routes->addAbstractFactory($diAbstractFactory);

        $shortName = substr($routeName, strrpos($routeName, '\\') + 1);

        $this->assertTrue($routes->has($shortName));

        try {
            $route = $routes->get($shortName, $options);
            $this->assertInstanceOf($routeName, $route);
        } catch (\Exception $e) {
            $messages = array();
            do {
                $messages[] = $e->getMessage() . "\n" . $e->getTraceAsString();
            } while ($e = $e->getPrevious());
            $this->fail(implode("\n\n", $messages));
        }
    }
}
