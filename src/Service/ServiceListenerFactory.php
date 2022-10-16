<?php

namespace Laminas\Mvc\Service;

use Interop\Container\ContainerInterface;
use Laminas\ModuleManager\Listener\ServiceListener;
use Laminas\ModuleManager\Listener\ServiceListenerInterface;
use Laminas\Mvc\Controller\ControllerManager;
use Laminas\Mvc\Controller\PluginManager;
use Laminas\Mvc\MiddlewareListener;
use Laminas\Mvc\RouteListener;
use Laminas\Mvc\SendResponseListener;
use Laminas\Mvc\Service\ConfigFactory;
use Laminas\Mvc\Service\ControllerManagerFactory;
use Laminas\Mvc\Service\ControllerPluginManagerFactory;
use Laminas\Mvc\Service\DispatchListenerFactory;
use Laminas\Mvc\Service\HttpMethodListenerFactory;
use Laminas\Mvc\Service\HttpViewManagerFactory;
use Laminas\Mvc\Service\InjectTemplateListenerFactory;
use Laminas\Mvc\Service\PaginatorPluginManagerFactory;
use Laminas\Mvc\Service\RequestFactory;
use Laminas\Mvc\Service\ResponseFactory;
use Laminas\Mvc\Service\ViewFeedStrategyFactory;
use Laminas\Mvc\Service\ViewHelperManagerFactory;
use Laminas\Mvc\Service\ViewJsonStrategyFactory;
use Laminas\Mvc\Service\ViewManagerFactory;
use Laminas\Mvc\Service\ViewPrefixPathStackResolverFactory;
use Laminas\Mvc\Service\ViewResolverFactory;
use Laminas\Mvc\Service\ViewTemplateMapResolverFactory;
use Laminas\Mvc\Service\ViewTemplatePathStackFactory;
use Laminas\Mvc\View;
use Laminas\Mvc\View\Http\InjectTemplateListener;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Factory\FactoryInterface;
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

use function gettype;
use function is_array;
use function is_object;
use function is_string;
use function sprintf;

class ServiceListenerFactory implements FactoryInterface
{
    /**
     * @var string
     */
    public const MISSING_KEY_ERROR = 'Invalid service listener options detected, %s array must contain %s key.';

    /**
     * @var string
     */
    public const VALUE_TYPE_ERROR = 'Invalid service listener options detected, %s must be a string, %s given.';

    /**
     * Default mvc-related service configuration -- can be overridden by modules.
     *
     * @var array
     */
    protected $defaultServiceConfig = [
        'aliases'    => [
            'application'                  => 'Application',
            'Config'                       => 'config',
            'configuration'                => 'config',
            'Configuration'                => 'config',
            'HttpDefaultRenderingStrategy' => View\Http\DefaultRenderingStrategy::class,
            'MiddlewareListener'           => MiddlewareListener::class,
            'request'                      => 'Request',
            'response'                     => 'Response',
            'RouteListener'                => RouteListener::class,
            'SendResponseListener'         => SendResponseListener::class,
            'View'                         => \Laminas\View\View::class,
            'ViewFeedRenderer'             => FeedRenderer::class,
            'ViewJsonRenderer'             => JsonRenderer::class,
            'ViewPhpRendererStrategy'      => PhpRendererStrategy::class,
            'ViewPhpRenderer'              => PhpRenderer::class,
            'ViewRenderer'                 => PhpRenderer::class,
            PluginManager::class           => 'ControllerPluginManager',
            InjectTemplateListener::class  => 'InjectTemplateListener',
            RendererInterface::class       => PhpRenderer::class,
            TemplateMapResolver::class     => 'ViewTemplateMapResolver',
            TemplatePathStack::class       => 'ViewTemplatePathStack',
            AggregateResolver::class       => 'ViewResolver',
            ResolverInterface::class       => 'ViewResolver',
            ControllerManager::class       => 'ControllerManager',
        ],
        'invokables' => [],
        'factories'  => [
            'Application'                             => ApplicationFactory::class,
            'config'                                  => ConfigFactory::class,
            'ControllerManager'                       => ControllerManagerFactory::class,
            'ControllerPluginManager'                 => ControllerPluginManagerFactory::class,
            'DispatchListener'                        => DispatchListenerFactory::class,
            'HttpExceptionStrategy'                   => HttpExceptionStrategyFactory::class,
            'HttpMethodListener'                      => HttpMethodListenerFactory::class,
            'HttpRouteNotFoundStrategy'               => HttpRouteNotFoundStrategyFactory::class,
            'HttpViewManager'                         => HttpViewManagerFactory::class,
            'InjectTemplateListener'                  => InjectTemplateListenerFactory::class,
            'PaginatorPluginManager'                  => PaginatorPluginManagerFactory::class,
            'Request'                                 => RequestFactory::class,
            'Response'                                => ResponseFactory::class,
            'ViewHelperManager'                       => ViewHelperManagerFactory::class,
            View\Http\DefaultRenderingStrategy::class => HttpDefaultRenderingStrategyFactory::class,
            'ViewFeedStrategy'                        => ViewFeedStrategyFactory::class,
            'ViewJsonStrategy'                        => ViewJsonStrategyFactory::class,
            'ViewManager'                             => ViewManagerFactory::class,
            'ViewResolver'                            => ViewResolverFactory::class,
            'ViewTemplateMapResolver'                 => ViewTemplateMapResolverFactory::class,
            'ViewTemplatePathStack'                   => ViewTemplatePathStackFactory::class,
            'ViewPrefixPathStackResolver'             => ViewPrefixPathStackResolverFactory::class,
            MiddlewareListener::class                 => InvokableFactory::class,
            RouteListener::class                      => InvokableFactory::class,
            SendResponseListener::class               => SendResponseListenerFactory::class,
            FeedRenderer::class                       => InvokableFactory::class,
            JsonRenderer::class                       => InvokableFactory::class,
            PhpRenderer::class                        => ViewPhpRendererFactory::class,
            PhpRendererStrategy::class                => ViewPhpRendererStrategyFactory::class,
            \Laminas\View\View::class                 => ViewFactory::class,
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
     * @throws ServiceNotCreatedException for invalid ServiceListener service
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
     * @throws ServiceListenerInterface for invalid $options types
     */
    private function injectServiceListenerOptions($options, ServiceListenerInterface $serviceListener)
    {
        if (! is_array($options)) {
            throw new ServiceNotCreatedException(sprintf(
                'The value of service_listener_options must be an array, %s given.',
                is_object($options) ? $options::class : gettype($options)
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
     * @throws ServiceNotCreatedException for any missing configuration options.
     * @throws ServiceNotCreatedException for configuration options of invalid types.
     */
    private function validatePluginManagerOptions($options, $name)
    {
        if (! is_array($options)) {
            throw new ServiceNotCreatedException(sprintf(
                'Plugin manager configuration for "%s" is invalid; must be an array, received "%s"',
                $name,
                is_object($options) ? $options::class : gettype($options)
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
