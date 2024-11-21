<?php

namespace Laminas\Mvc\Service;

// phpcs:ignore
use Interop\Container\ContainerInterface;
use Laminas\ModuleManager\Listener\ServiceListener;
use Laminas\ModuleManager\Listener\ServiceListenerInterface;
use Laminas\Mvc;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Laminas\View;

use function get_debug_type;
use function gettype;
use function is_array;
use function is_string;
use function sprintf;

class ServiceListenerFactory implements FactoryInterface
{
    /** @var string */
    public const MISSING_KEY_ERROR = 'Invalid service listener options detected, %s array must contain %s key.';

    /** @var string */
    public const VALUE_TYPE_ERROR = 'Invalid service listener options detected, %s must be a string, %s given.';

    /**
     * Default mvc-related service configuration -- can be overridden by modules.
     *
     * @var array
     */
    protected $defaultServiceConfig = [
        'aliases'    => [
            'application'                               => 'Application',
            'Config'                                    => 'config',
            'configuration'                             => 'config',
            'Configuration'                             => 'config',
            'HttpDefaultRenderingStrategy'              => Mvc\View\Http\DefaultRenderingStrategy::class,
            'MiddlewareListener'                        => Mvc\MiddlewareListener::class,
            'request'                                   => 'Request',
            'response'                                  => 'Response',
            'RouteListener'                             => Mvc\RouteListener::class,
            'SendResponseListener'                      => Mvc\SendResponseListener::class,
            'View'                                      => View\View::class,
            'ViewFeedRenderer'                          => View\Renderer\FeedRenderer::class,
            'ViewJsonRenderer'                          => View\Renderer\JsonRenderer::class,
            'ViewPhpRendererStrategy'                   => View\Strategy\PhpRendererStrategy::class,
            'ViewPhpRenderer'                           => View\Renderer\PhpRenderer::class,
            'ViewRenderer'                              => View\Renderer\PhpRenderer::class,
            Mvc\Controller\PluginManager::class         => 'ControllerPluginManager',
            Mvc\View\Http\InjectTemplateListener::class => 'InjectTemplateListener',
            View\Renderer\RendererInterface::class      => View\Renderer\PhpRenderer::class,
            View\Resolver\TemplateMapResolver::class    => 'ViewTemplateMapResolver',
            View\Resolver\TemplatePathStack::class      => 'ViewTemplatePathStack',
            View\Resolver\AggregateResolver::class      => 'ViewResolver',
            View\Resolver\ResolverInterface::class      => 'ViewResolver',
            Mvc\Controller\ControllerManager::class     => 'ControllerManager',
        ],
        'invokables' => [],
        'factories'  => [
            'Application'                                 => ApplicationFactory::class,
            'config'                                      => Mvc\Service\ConfigFactory::class,
            'ControllerManager'                           => Mvc\Service\ControllerManagerFactory::class,
            'ControllerPluginManager'                     => Mvc\Service\ControllerPluginManagerFactory::class,
            'DispatchListener'                            => Mvc\Service\DispatchListenerFactory::class,
            'HttpExceptionStrategy'                       => HttpExceptionStrategyFactory::class,
            'HttpMethodListener'                          => Mvc\Service\HttpMethodListenerFactory::class,
            'HttpRouteNotFoundStrategy'                   => HttpRouteNotFoundStrategyFactory::class,
            'HttpViewManager'                             => Mvc\Service\HttpViewManagerFactory::class,
            'InjectTemplateListener'                      => Mvc\Service\InjectTemplateListenerFactory::class,
            'PaginatorPluginManager'                      => Mvc\Service\PaginatorPluginManagerFactory::class,
            'Request'                                     => Mvc\Service\RequestFactory::class,
            'Response'                                    => Mvc\Service\ResponseFactory::class,
            'ViewHelperManager'                           => Mvc\Service\ViewHelperManagerFactory::class,
            Mvc\View\Http\DefaultRenderingStrategy::class => HttpDefaultRenderingStrategyFactory::class,
            'ViewFeedStrategy'                            => Mvc\Service\ViewFeedStrategyFactory::class,
            'ViewJsonStrategy'                            => Mvc\Service\ViewJsonStrategyFactory::class,
            'ViewManager'                                 => Mvc\Service\ViewManagerFactory::class,
            'ViewResolver'                                => Mvc\Service\ViewResolverFactory::class,
            'ViewTemplateMapResolver'                     => Mvc\Service\ViewTemplateMapResolverFactory::class,
            'ViewTemplatePathStack'                       => Mvc\Service\ViewTemplatePathStackFactory::class,
            'ViewPrefixPathStackResolver'                 => Mvc\Service\ViewPrefixPathStackResolverFactory::class,
            Mvc\MiddlewareListener::class                 => InvokableFactory::class,
            Mvc\RouteListener::class                      => InvokableFactory::class,
            Mvc\SendResponseListener::class               => SendResponseListenerFactory::class,
            View\Renderer\FeedRenderer::class             => InvokableFactory::class,
            View\Renderer\JsonRenderer::class             => InvokableFactory::class,
            View\Renderer\PhpRenderer::class              => ViewPhpRendererFactory::class,
            View\Strategy\PhpRendererStrategy::class      => ViewPhpRendererStrategyFactory::class,
            View\View::class                              => ViewFactory::class,
        ],
    ];

    /**
     * Create the service listener service
     *
     * Tries to get a service named ServiceListenerInterface from the service
     * locator, otherwise creates a ServiceListener instance, passing it the
     * container instance and the default service configuration, which can be
     * overridden by modules.
     *
     * It looks for the 'service_listener_options' key in the application
     * config and tries to add service/plugin managers as configured. The value
     * of 'service_listener_options' must be a list (array) which contains the
     * following keys:
     *
     * - service_manager: the name of the service manage to create as string
     * - config_key: the name of the configuration key to search for as string
     * - interface: the name of the interface that modules can implement as string
     * - method: the name of the method that modules have to implement as string
     *
     * @param  string              $requestedName
     * @param  null|array          $options
     * @return ServiceListenerInterface
     * @throws ServiceNotCreatedException For invalid ServiceListener service.
     * @throws ServiceNotCreatedException For invalid configurations.
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $configuration = $container->get('ApplicationConfig');

        $serviceListener = $container->has('ServiceListenerInterface')
            ? $container->get('ServiceListenerInterface')
            : new ServiceListener($container);

        if (! $serviceListener instanceof ServiceListenerInterface) {
            throw new ServiceNotCreatedException(
                'The service named ServiceListenerInterface must implement '
                . ServiceListenerInterface::class
            );
        }

        $serviceListener->setDefaultServiceConfig($this->defaultServiceConfig);

        if (isset($configuration['service_listener_options'])) {
            $this->injectServiceListenerOptions($configuration['service_listener_options'], $serviceListener);
        }

        return $serviceListener;
    }

    /**
     * Validate and inject plugin manager options into the service listener.
     *
     * @param array $options
     * @throws ServiceListenerInterface For invalid $options types.
     */
    private function injectServiceListenerOptions($options, ServiceListenerInterface $serviceListener)
    {
        if (! is_array($options)) {
            throw new ServiceNotCreatedException(sprintf(
                'The value of service_listener_options must be an array, %s given.',
                get_debug_type($options)
            ));
        }

        foreach ($options as $key => $newServiceManager) {
            $this->validatePluginManagerOptions($newServiceManager, $key);

            $serviceListener->addServiceManager(
                $newServiceManager['service_manager'],
                $newServiceManager['config_key'],
                $newServiceManager['interface'],
                $newServiceManager['method']
            );
        }
    }

    /**
     * Validate the structure and types for plugin manager configuration options.
     *
     * Ensures all required keys are present in the expected types.
     *
     * @param array $options
     * @param string $name Plugin manager service name; used for exception messages
     * @throws ServiceNotCreatedException For any missing configuration options.
     * @throws ServiceNotCreatedException For configuration options of invalid types.
     */
    private function validatePluginManagerOptions($options, $name)
    {
        if (! is_array($options)) {
            throw new ServiceNotCreatedException(sprintf(
                'Plugin manager configuration for "%s" is invalid; must be an array, received "%s"',
                $name,
                get_debug_type($options)
            ));
        }

        if (! isset($options['service_manager'])) {
            throw new ServiceNotCreatedException(sprintf(self::MISSING_KEY_ERROR, $name, 'service_manager'));
        }

        if (! is_string($options['service_manager'])) {
            throw new ServiceNotCreatedException(sprintf(
                self::VALUE_TYPE_ERROR,
                'service_manager',
                gettype($options['service_manager'])
            ));
        }

        if (! isset($options['config_key'])) {
            throw new ServiceNotCreatedException(sprintf(self::MISSING_KEY_ERROR, $name, 'config_key'));
        }

        if (! is_string($options['config_key'])) {
            throw new ServiceNotCreatedException(sprintf(
                self::VALUE_TYPE_ERROR,
                'config_key',
                gettype($options['config_key'])
            ));
        }

        if (! isset($options['interface'])) {
            throw new ServiceNotCreatedException(sprintf(self::MISSING_KEY_ERROR, $name, 'interface'));
        }

        if (! is_string($options['interface'])) {
            throw new ServiceNotCreatedException(sprintf(
                self::VALUE_TYPE_ERROR,
                'interface',
                gettype($options['interface'])
            ));
        }

        if (! isset($options['method'])) {
            throw new ServiceNotCreatedException(sprintf(self::MISSING_KEY_ERROR, $name, 'method'));
        }

        if (! is_string($options['method'])) {
            throw new ServiceNotCreatedException(sprintf(
                self::VALUE_TYPE_ERROR,
                'method',
                gettype($options['method'])
            ));
        }
    }
}
