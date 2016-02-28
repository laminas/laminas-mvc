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
use Zend\Mvc\Router\RoutePluginManager;
use Zend\Mvc\Service\ConsoleRouterFactory;
use Zend\Mvc\Service\HttpRouterFactory;
use Zend\Mvc\Service\RouterFactory;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\ServiceManager;

class RouterFactoryTest extends TestCase
{
    public function setUp()
    {
        $this->defaultServiceConfig = [
            'factories' => [
                'ConsoleRouter'      => ConsoleRouterFactory::class,
                'HttpRouter'         => HttpRouterFactory::class,
                'RoutePluginManager' => function ($services) {
                    return new RoutePluginManager($services);
                },
            ],
        ];

        $this->factory  = new RouterFactory();
    }

    public function testFactoryCanCreateRouterBasedOnConfiguredName()
    {
        $config = new Config(array_merge_recursive($this->defaultServiceConfig, [
            'services' => [ 'config' => [
                'router' => [
                    'router_class' => 'ZendTest\Mvc\Service\TestAsset\Router',
                ],
                'console' => [
                    'router' => [
                        'router_class' => 'ZendTest\Mvc\Service\TestAsset\Router',
                    ],
                ],
            ]],
        ]));
        $services = new ServiceManager();
        $config->configureServiceManager($services);

        $router = $this->factory->__invoke($services, 'router');
        $this->assertInstanceOf('ZendTest\Mvc\Service\TestAsset\Router', $router);
    }

    public function testFactoryCanCreateRouterWhenOnlyHttpRouterConfigPresent()
    {
        $config = new Config(array_merge_recursive($this->defaultServiceConfig, [
            'services' => [ 'config' => [
                'router' => [
                    'router_class' => 'ZendTest\Mvc\Service\TestAsset\Router',
                ],
            ]],
        ]));
        $services = new ServiceManager();
        $config->configureServiceManager($services);

        $router = $this->factory->__invoke($services, 'router');
        $this->assertInstanceOf('Zend\Mvc\Router\Console\SimpleRouteStack', $router);
    }
}
