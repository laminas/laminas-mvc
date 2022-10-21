<?php

namespace LaminasTest\Mvc\Application;

use Laminas\Router\Http\Literal;
use Laminas\Router\ConfigProvider;
use LaminasTest\Mvc\TestAsset\MockViewManager;
use LaminasTest\Mvc\TestAsset\MockSendResponseListener;
use LaminasTest\Mvc\TestAsset\StubBootstrapListener;
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
                        'type' => Literal::class,
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
            (new ConfigProvider())->getDependencyConfig()
        );

        $serviceConfig = ArrayUtils::merge(
            $serviceConfig,
            [
                'aliases' => [
                    'ControllerLoader'  => ControllerManager::class,
                ],
                'factories' => [
                    'ControllerManager' => static fn($services): ControllerManager =>
                        new ControllerManager($services, ['factories' => [
                        'bad' => static fn(): BadController => new BadController(),
                    ]]),
                    'Router' => static fn($services) => $services->get('HttpRouter'),
                ],
                'invokables' => [
                    'Request'              => Request::class,
                    'Response'             => Response::class,
                    'ViewManager'          => MockViewManager::class,
                    'SendResponseListener' => MockSendResponseListener::class,
                    'BootstrapListener'    => StubBootstrapListener::class,
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
