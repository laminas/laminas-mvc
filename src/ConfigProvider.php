<?php

declare(strict_types=1);

namespace Laminas\Mvc;

use Laminas\EventManager\EventManager;
use Laminas\EventManager\EventManagerAwareInterface;
use Laminas\EventManager\EventManagerInterface;
use Laminas\EventManager\SharedEventManager;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\Mvc\Controller\ControllerManager;
use Laminas\Mvc\Controller\PluginManager;
use Laminas\Mvc\RouteListener;
use Laminas\Mvc\SendResponseListener;
use Laminas\Mvc\Service\ApplicationFactory;
use Laminas\Mvc\Service\ApplicationListenerProviderFactory;
use Laminas\Mvc\Service\ControllerManagerFactory;
use Laminas\Mvc\Service\ControllerPluginManagerFactory;
use Laminas\Mvc\Service\DispatchListenerFactory;
use Laminas\Mvc\Service\EventManagerFactory;
use Laminas\Mvc\Service\HttpDefaultRenderingStrategyFactory;
use Laminas\Mvc\Service\HttpExceptionStrategyFactory;
use Laminas\Mvc\Service\HttpMethodListenerFactory;
use Laminas\Mvc\Service\HttpRouteNotFoundStrategyFactory;
use Laminas\Mvc\Service\HttpViewManagerFactory;
use Laminas\Mvc\Service\InjectTemplateListenerFactory;
use Laminas\Mvc\Service\PaginatorPluginManagerFactory;
use Laminas\Mvc\Service\RequestFactory;
use Laminas\Mvc\Service\ResponseFactory;
use Laminas\Mvc\Service\SendResponseListenerFactory;
use Laminas\Mvc\Service\ViewFactory;
use Laminas\Mvc\Service\ViewFeedStrategyFactory;
use Laminas\Mvc\Service\ViewHelperManagerFactory;
use Laminas\Mvc\Service\ViewJsonStrategyFactory;
use Laminas\Mvc\Service\ViewManagerFactory;
use Laminas\Mvc\Service\ViewPhpRendererFactory;
use Laminas\Mvc\Service\ViewPhpRendererStrategyFactory;
use Laminas\Mvc\Service\ViewPrefixPathStackResolverFactory;
use Laminas\Mvc\Service\ViewResolverFactory;
use Laminas\Mvc\Service\ViewTemplateMapResolverFactory;
use Laminas\Mvc\Service\ViewTemplatePathStackFactory;
use Laminas\Mvc\View\Http\DefaultRenderingStrategy;
use Laminas\Mvc\View\Http\InjectTemplateListener;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Laminas\View\Renderer\FeedRenderer;
use Laminas\View\Renderer\JsonRenderer;
use Laminas\View\Renderer\PhpRenderer;
use Laminas\View\Renderer\RendererInterface;
use Laminas\View\Resolver\AggregateResolver;
use Laminas\View\Resolver\ResolverInterface;
use Laminas\View\Resolver\TemplateMapResolver;
use Laminas\View\Resolver\TemplatePathStack;
use Laminas\View\Strategy\PhpRendererStrategy;
use Laminas\View\View;
use Psr\Container\ContainerInterface;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies'     => $this->getDependencies(),
            Application::class => [
                'listeners' => [],
            ],
        ];
    }

    public function getDependencies(): array
    {
        return [
            'aliases'      => [
                'EventManagerInterface'            => EventManager::class,
                EventManagerInterface::class       => 'EventManager',
                SharedEventManager::class          => 'SharedEventManager',
                'SharedEventManagerInterface'      => 'SharedEventManager',
                SharedEventManagerInterface::class => 'SharedEventManager',
                'application'                      => 'Application',
                'Config'                           => 'config',
                'configuration'                    => 'config',
                'Configuration'                    => 'config',
                'HttpDefaultRenderingStrategy'     => DefaultRenderingStrategy::class,
                'request'                          => 'Request',
                'response'                         => 'Response',
                'RouteListener'                    => RouteListener::class,
                'SendResponseListener'             => SendResponseListener::class,
                'View'                             => View::class,
                'ViewFeedRenderer'                 => FeedRenderer::class,
                'ViewJsonRenderer'                 => JsonRenderer::class,
                'ViewPhpRendererStrategy'          => PhpRendererStrategy::class,
                'ViewPhpRenderer'                  => PhpRenderer::class,
                'ViewRenderer'                     => PhpRenderer::class,
                PluginManager::class               => 'ControllerPluginManager',
                InjectTemplateListener::class      => 'InjectTemplateListener',
                RendererInterface::class           => PhpRenderer::class,
                TemplateMapResolver::class         => 'ViewTemplateMapResolver',
                TemplatePathStack::class           => 'ViewTemplatePathStack',
                AggregateResolver::class           => 'ViewResolver',
                ResolverInterface::class           => 'ViewResolver',
                ControllerManager::class           => 'ControllerManager',
            ],
            'factories'    => [
                'EventManager'                     => EventManagerFactory::class,
                'SharedEventManager'               => static fn() => new SharedEventManager(),
                'Application'                      => ApplicationFactory::class,
                'ControllerManager'                => ControllerManagerFactory::class,
                'ControllerPluginManager'          => ControllerPluginManagerFactory::class,
                'DispatchListener'                 => DispatchListenerFactory::class,
                'HttpExceptionStrategy'            => HttpExceptionStrategyFactory::class,
                'HttpMethodListener'               => HttpMethodListenerFactory::class,
                'HttpRouteNotFoundStrategy'        => HttpRouteNotFoundStrategyFactory::class,
                'HttpViewManager'                  => HttpViewManagerFactory::class,
                'InjectTemplateListener'           => InjectTemplateListenerFactory::class,
                'PaginatorPluginManager'           => PaginatorPluginManagerFactory::class,
                'Request'                          => RequestFactory::class,
                'Response'                         => ResponseFactory::class,
                'ViewHelperManager'                => ViewHelperManagerFactory::class,
                DefaultRenderingStrategy::class    => HttpDefaultRenderingStrategyFactory::class,
                'ViewFeedStrategy'                 => ViewFeedStrategyFactory::class,
                'ViewJsonStrategy'                 => ViewJsonStrategyFactory::class,
                'ViewManager'                      => ViewManagerFactory::class,
                'ViewResolver'                     => ViewResolverFactory::class,
                'ViewTemplateMapResolver'          => ViewTemplateMapResolverFactory::class,
                'ViewTemplatePathStack'            => ViewTemplatePathStackFactory::class,
                'ViewPrefixPathStackResolver'      => ViewPrefixPathStackResolverFactory::class,
                ApplicationListenerProvider::class => ApplicationListenerProviderFactory::class,
                RouteListener::class               => InvokableFactory::class,
                SendResponseListener::class        => SendResponseListenerFactory::class,
                FeedRenderer::class                => InvokableFactory::class,
                JsonRenderer::class                => InvokableFactory::class,
                PhpRenderer::class                 => ViewPhpRendererFactory::class,
                PhpRendererStrategy::class         => ViewPhpRendererStrategyFactory::class,
                View::class                        => ViewFactory::class,
            ],
            'shared'       => [
                'EventManager' => false,
            ],
            'initializers' => [
                'EventManagerAwareInitializer' => static function ($first, $second): void {
                    if ($first instanceof ContainerInterface) {
                        $container = $first;
                        $instance  = $second;
                    } else {
                        $container = $second;
                        $instance  = $first;
                    }

                    if (! $instance instanceof EventManagerAwareInterface) {
                        return;
                    }

                    $eventManager = $instance->getEventManager();

                    // If the instance has an EM WITH an SEM composed, do nothing.
                    if (
                        $eventManager instanceof EventManagerInterface
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
