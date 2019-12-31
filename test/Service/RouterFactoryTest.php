<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Service;

use Laminas\Mvc\Router\RoutePluginManager;
use Laminas\Mvc\Service\ConsoleRouterFactory;
use Laminas\Mvc\Service\HttpRouterFactory;
use Laminas\Mvc\Service\RouterFactory;
use Laminas\ServiceManager\Config;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit_Framework_TestCase as TestCase;

class RouterFactoryTest extends TestCase
{
    use FactoryEnvironmentTrait;

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
                    'router_class' => 'LaminasTest\Mvc\Service\TestAsset\Router',
                ],
                'console' => [
                    'router' => [
                        'router_class' => 'LaminasTest\Mvc\Service\TestAsset\Router',
                    ],
                ],
            ]],
        ]));
        $services = new ServiceManager();
        $config->configureServiceManager($services);

        $router = $this->factory->__invoke($services, 'router');
        $this->assertInstanceOf('LaminasTest\Mvc\Service\TestAsset\Router', $router);
    }

    public function testFactoryCanCreateRouterWhenOnlyHttpRouterConfigPresent()
    {
        $config = new Config(array_merge_recursive($this->defaultServiceConfig, [
            'services' => [ 'config' => [
                'router' => [
                    'router_class' => 'LaminasTest\Mvc\Service\TestAsset\Router',
                ],
            ]],
        ]));
        $services = new ServiceManager();
        $config->configureServiceManager($services);

        $router = $this->factory->__invoke($services, 'router');
        $this->assertInstanceOf('Laminas\Mvc\Router\Console\SimpleRouteStack', $router);
    }

    public function testFactoryWillCreateConsoleRouterBasedOnConsoleUsageUnderServiceManagerV2()
    {
        $this->setConsoleEnvironment(true);

        $services = new ServiceManager();
        (new Config($this->defaultServiceConfig))->configureServiceManager($services);

        $router = $this->factory->createService($services, 'router');
        $this->assertInstanceOf('Laminas\Mvc\Router\Console\SimpleRouteStack', $router);
    }
}
