<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\Application;

use Laminas\Http\PhpEnvironment\Request;
use Laminas\Http\PhpEnvironment\Response;
use Laminas\Mvc\Application;
use Laminas\Mvc\ConfigProvider;
use Laminas\Mvc\Controller\ControllerManager;
use Laminas\Router;
use Laminas\Router\Http\Literal;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Stdlib\ArrayUtils;
use LaminasTest\Mvc\TestAsset;
use LaminasTest\Mvc\TestAsset\MockSendResponseListener;
use LaminasTest\Mvc\TestAsset\MockViewManager;
use LaminasTest\Mvc\TestAsset\StubBootstrapListener;

trait PathControllerTrait
{
    public function prepareApplication(): Application
    {
        $testConfig = [
            'router'       => [
                'routes' => [
                    'path' => [
                        'type'    => Literal::class,
                        'options' => [
                            'route'    => '/path',
                            'defaults' => [
                                'controller' => 'path',
                            ],
                        ],
                    ],
                ],
            ],
            'dependencies' => [
                'aliases'    => [
                    'ControllerLoader' => ControllerManager::class,
                ],
                'factories'  => [
                    'ControllerManager' => static fn($services) => new ControllerManager($services, [
                        'factories' => [
                            'path' => static fn() => new TestAsset\PathController(),
                        ],
                    ]),
                ],
                'invokables' => [
                    'Request'              => Request::class,
                    'Response'             => Response::class,
                    'ViewManager'          => MockViewManager::class,
                    'SendResponseListener' => MockSendResponseListener::class,
                    'BootstrapListener'    => StubBootstrapListener::class,
                ],
            ],
        ];

        $config                                       = ArrayUtils::merge(
            ArrayUtils::merge(
                (new ConfigProvider())(),
                (new Router\ConfigProvider())(),
            ),
            $testConfig
        );
        $config['dependencies']['services']['config'] = $config;

        $services    = new ServiceManager($config['dependencies']);
        $application = $services->get('Application');

        $request = $services->get('Request');
        $request->setUri('http://example.local/path');

        $application->bootstrap();
        return $application;
    }
}
