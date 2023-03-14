<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\Application;

use Laminas\Http\PhpEnvironment\Request;
use Laminas\Http\PhpEnvironment\Response;
use Laminas\Mvc\Application;
use Laminas\Mvc\Controller\ControllerManager;
use Laminas\Router\ConfigProvider;
use Laminas\Router\Http\Literal;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Stdlib\ArrayUtils;
use LaminasTest\Mvc\Controller\TestAsset\BadController;
use LaminasTest\Mvc\TestAsset\MockSendResponseListener;
use LaminasTest\Mvc\TestAsset\MockViewManager;
use LaminasTest\Mvc\TestAsset\StubBootstrapListener;

trait BadControllerTrait
{
    public function prepareApplication(): Application
    {
        $config = [
            'router' => [
                'routes' => [
                    'path' => [
                        'type'    => Literal::class,
                        'options' => [
                            'route'    => '/bad',
                            'defaults' => [
                                'controller' => 'bad',
                                'action'     => 'test',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $serviceConfig = ArrayUtils::merge(
            ArrayUtils::merge(
                (new \Laminas\Mvc\ConfigProvider())->getDependencies(),
                (new ConfigProvider())->getDependencyConfig(),
            ),
            [
                'aliases'    => [
                    'ControllerLoader' => ControllerManager::class,
                ],
                'factories'  => [
                    'ControllerManager' => static fn($services): ControllerManager => new ControllerManager($services, [
                        'factories' => [
                            'bad' => static fn(): BadController => new BadController(),
                        ],
                    ]),
                    'Router'            => static fn($services) => $services->get('HttpRouter'),
                ],
                'invokables' => [
                    'Request'              => Request::class,
                    'Response'             => Response::class,
                    'ViewManager'          => MockViewManager::class,
                    'SendResponseListener' => MockSendResponseListener::class,
                    'BootstrapListener'    => StubBootstrapListener::class,
                ],
                'services'   => [
                    'config' => $config,
                ],
            ]
        );
        $services      = new ServiceManager($serviceConfig);
        $application   = $services->get('Application');

        $request = $services->get('Request');
        $request->setUri('http://example.local/bad');

        $application->bootstrap();
        return $application;
    }
}
