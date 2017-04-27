<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application;

use Zend\Http\PhpEnvironment\Request as HttpRequest;
use Zend\Http\PhpEnvironment\Response as HttpResponse;
use Zend\Mvc\Service\HttpViewManagerFactory;
use Zend\Router\Http\HttpRouterFactory;

return [
    'controllers' => [
        'factories' => [
            'path' => function () {
                return new Controller\PathController();
            },
        ],
    ],
    'router' => [
        'routes' => [
            'path' => [
                'type' => 'literal',
                'options' => [
                    'route' => '/path',
                    'defaults' => [
                        'controller' => 'path',
                    ],
                ],
            ],
        ],
    ],
    'service_manager' => [
        'factories' => [
            'Request' => function () {
                return new HttpRequest();
            },
            'Response' => function () {
                return new HttpResponse();
            },
            'Router'      => HttpRouterFactory::class,
            'ViewManager' => HttpViewManagerFactory::class,
        ],
    ],
    'view_manager' => [
        'display_not_found_reason' => true,
        'display_exceptions'       => true,
        'doctype'                  => 'HTML5',
        'not_found_template'       => 'error/404',
        'exception_template'       => 'error/index',
        'template_map' => [
            'layout/layout'           => __DIR__ . '/../view/layout/layout.phtml',
            'application/index/index' => __DIR__ . '/../view/application/index/index.phtml',
            'error/404'               => __DIR__ . '/../view/error/404.phtml',
            'error/index'             => __DIR__ . '/../view/error/index.phtml',
        ],
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
];
