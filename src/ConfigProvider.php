<?php

declare(strict_types=1);

namespace Laminas\Mvc;

use Psr\Container\ContainerInterface;
use Laminas\EventManager\EventManager;
use Laminas\EventManager\EventManagerAwareInterface;
use Laminas\EventManager\EventManagerInterface;
use Laminas\EventManager\SharedEventManager;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\Mvc\Controller\ControllerManager;
use Laminas\Mvc\Service\ApplicationFactory;
use Laminas\Mvc\Service\EventManagerFactory;
use Laminas\Mvc\Service\HttpDefaultRenderingStrategyFactory;
use Laminas\Mvc\Service\HttpExceptionStrategyFactory;
use Laminas\Mvc\Service\HttpRouteNotFoundStrategyFactory;
use Laminas\Mvc\Service\SendResponseListenerFactory;
use Laminas\Mvc\Service\ViewFactory;
use Laminas\Mvc\Service\ViewPhpRendererFactory;
use Laminas\Mvc\Service\ViewPhpRendererStrategyFactory;
use Laminas\ServiceManager\Factory\InvokableFactory;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    public function getDependencies(): array
    {
        return [
            'aliases' => [
                'EventManagerInterface' => EventManager::class,
                EventManagerInterface::class => 'EventManager',
                SharedEventManager::class => 'SharedEventManager',
                'SharedEventManagerInterface' => 'SharedEventManager',
                SharedEventManagerInterface::class => 'SharedEventManager',
                'application' => 'Application',
                'Config' => 'config',
                'configuration' => 'config',
                'Configuration' => 'config',
                'HttpDefaultRenderingStrategy' => View\Http\DefaultRenderingStrategy::class,
                'request' => 'Request',
                'response' => 'Response',
                'RouteListener' => 'Laminas\Mvc\RouteListener',
                'SendResponseListener' => 'Laminas\Mvc\SendResponseListener',
                'View' => 'Laminas\View\View',
                'ViewFeedRenderer' => 'Laminas\View\Renderer\FeedRenderer',
                'ViewJsonRenderer' => 'Laminas\View\Renderer\JsonRenderer',
                'ViewPhpRendererStrategy' => 'Laminas\View\Strategy\PhpRendererStrategy',
                'ViewPhpRenderer' => 'Laminas\View\Renderer\PhpRenderer',
                'ViewRenderer' => 'Laminas\View\Renderer\PhpRenderer',
                'Laminas\Mvc\Controller\PluginManager' => 'ControllerPluginManager',
                'Laminas\Mvc\View\Http\InjectTemplateListener' => 'InjectTemplateListener',
                'Laminas\View\Renderer\RendererInterface' => 'Laminas\View\Renderer\PhpRenderer',
                'Laminas\View\Resolver\TemplateMapResolver' => 'ViewTemplateMapResolver',
                'Laminas\View\Resolver\TemplatePathStack' => 'ViewTemplatePathStack',
                'Laminas\View\Resolver\AggregateResolver' => 'ViewResolver',
                'Laminas\View\Resolver\ResolverInterface' => 'ViewResolver',
                ControllerManager::class => 'ControllerManager',
            ],
            'factories' => [
                'EventManager' => EventManagerFactory::class,
                'SharedEventManager' => static function () {
                    return new SharedEventManager();
                },
                'Application' => ApplicationFactory::class,
                'config' => 'Laminas\Mvc\Service\ConfigFactory',
                'ControllerManager' => 'Laminas\Mvc\Service\ControllerManagerFactory',
                'ControllerPluginManager' => 'Laminas\Mvc\Service\ControllerPluginManagerFactory',
                'DispatchListener' => 'Laminas\Mvc\Service\DispatchListenerFactory',
                'HttpExceptionStrategy' => HttpExceptionStrategyFactory::class,
                'HttpMethodListener' => 'Laminas\Mvc\Service\HttpMethodListenerFactory',
                'HttpRouteNotFoundStrategy' => HttpRouteNotFoundStrategyFactory::class,
                'HttpViewManager' => 'Laminas\Mvc\Service\HttpViewManagerFactory',
                'InjectTemplateListener' => 'Laminas\Mvc\Service\InjectTemplateListenerFactory',
                'PaginatorPluginManager' => 'Laminas\Mvc\Service\PaginatorPluginManagerFactory',
                'Request' => 'Laminas\Mvc\Service\RequestFactory',
                'Response' => 'Laminas\Mvc\Service\ResponseFactory',
                'ViewHelperManager' => 'Laminas\Mvc\Service\ViewHelperManagerFactory',
                View\Http\DefaultRenderingStrategy::class => HttpDefaultRenderingStrategyFactory::class,
                'ViewFeedStrategy' => 'Laminas\Mvc\Service\ViewFeedStrategyFactory',
                'ViewJsonStrategy' => 'Laminas\Mvc\Service\ViewJsonStrategyFactory',
                'ViewManager' => 'Laminas\Mvc\Service\ViewManagerFactory',
                'ViewResolver' => 'Laminas\Mvc\Service\ViewResolverFactory',
                'ViewTemplateMapResolver' => 'Laminas\Mvc\Service\ViewTemplateMapResolverFactory',
                'ViewTemplatePathStack' => 'Laminas\Mvc\Service\ViewTemplatePathStackFactory',
                'ViewPrefixPathStackResolver' => 'Laminas\Mvc\Service\ViewPrefixPathStackResolverFactory',
                'Laminas\Mvc\RouteListener' => InvokableFactory::class,
                'Laminas\Mvc\SendResponseListener' => SendResponseListenerFactory::class,
                'Laminas\View\Renderer\FeedRenderer' => InvokableFactory::class,
                'Laminas\View\Renderer\JsonRenderer' => InvokableFactory::class,
                'Laminas\View\Renderer\PhpRenderer' => ViewPhpRendererFactory::class,
                'Laminas\View\Strategy\PhpRendererStrategy' => ViewPhpRendererStrategyFactory::class,
                'Laminas\View\View' => ViewFactory::class,
            ],
            'shared' => [
                'EventManager' => false,
            ],
            'initializers' => [
                'EventManagerAwareInitializer' => static function ($first, $second) {
                    if ($first instanceof ContainerInterface) {
                        $container = $first;
                        $instance = $second;
                    } else {
                        $container = $second;
                        $instance = $first;
                    }

                    if (! $instance instanceof EventManagerAwareInterface) {
                        return;
                    }

                    $eventManager = $instance->getEventManager();

                    // If the instance has an EM WITH an SEM composed, do nothing.
                    if ($eventManager instanceof EventManagerInterface
                        && $eventManager->getSharedManager() instanceof SharedEventManagerInterface
                    ) {
                        return;
                    }

                    $instance->setEventManager($container->get('EventManager'));
                },
            ],
        ];
    }
}
