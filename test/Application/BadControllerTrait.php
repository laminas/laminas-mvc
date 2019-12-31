<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Application;

use Laminas\Http\PhpEnvironment\Request;
use Laminas\Http\PhpEnvironment\Response;
use Laminas\Mvc\Controller\ControllerManager;
use Laminas\Mvc\Service\ServiceListenerFactory;
use Laminas\Mvc\Service\ServiceManagerConfig;
use Laminas\Router;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Stdlib\ArrayUtils;
use LaminasTest\Mvc\Controller\TestAsset\BadController;
use LaminasTest\Mvc\TestAsset;
use ReflectionProperty;

trait BadControllerTrait
{
    public function prepareApplication()
    {
        $config = [
            'router' => [
                'routes' => [
                    'path' => [
                        'type' => Router\Http\Literal::class,
                        'options' => [
                            'route' => '/bad',
                            'defaults' => [
                                'controller' => 'bad',
                                'action'     => 'test',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $serviceListener = new ServiceListenerFactory();
        $r = new ReflectionProperty($serviceListener, 'defaultServiceConfig');
        $r->setAccessible(true);
        $serviceConfig = $r->getValue($serviceListener);

        $serviceConfig = ArrayUtils::merge(
            $serviceConfig,
            (new Router\ConfigProvider())->getDependencyConfig()
        );

        $serviceConfig = ArrayUtils::merge(
            $serviceConfig,
            [
                'aliases' => [
                    'ControllerLoader'  => ControllerManager::class,
                    'ControllerManager' => ControllerManager::class,
                ],
                'factories' => [
                    ControllerManager::class => function ($services) {
                        return new ControllerManager($services, ['factories' => [
                            'bad' => function () {
                                return new BadController();
                            },
                        ]]);
                    },
                    'Router' => function ($services) {
                        return $services->get('HttpRouter');
                    },
                ],
                'invokables' => [
                    'Request'              => Request::class,
                    'Response'             => Response::class,
                    'ViewManager'          => TestAsset\MockViewManager::class,
                    'SendResponseListener' => TestAsset\MockSendResponseListener::class,
                    'BootstrapListener'    => TestAsset\StubBootstrapListener::class,
                ],
                'services' => [
                    'config' => $config,
                    'ApplicationConfig' => [
                        'modules'                 => [],
                        'module_listener_options' => [
                            'config_cache_enabled' => false,
                            'cache_dir'            => 'data/cache',
                            'module_paths'         => [],
                        ],
                    ],
                ],
            ]
        );
        $services = new ServiceManager();
        (new ServiceManagerConfig($serviceConfig))->configureServiceManager($services);
        $application = $services->get('Application');

        $request = $services->get('Request');
        $request->setUri('http://example.local/bad');

        $application->bootstrap();
        return $application;
    }
}
