<?php

declare(strict_types=1);

namespace Laminas\Mvc\Service;

use Interop\Container\ContainerInterface;
use Laminas\Router\RouteMatch;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\View\Helper as ViewHelper;
use Laminas\View\HelperPluginManager;

use function is_callable;

class ViewHelperManagerFactory implements FactoryInterface
{
    /**
     * An array of helper configuration classes to ensure are on the helper_map stack.
     *
     * These are *not* imported; that way they can be optional dependencies.
     *
     * @todo Remove these once their components have Modules defined.
     * @var array
     */
    protected $defaultHelperMapClasses = [];

    /**
     * Create and return the view helper manager
     *
     * @param  string             $requestedName
     * @param  null|array         $options
     * @return HelperPluginManager
     * @throws ServiceNotCreatedException
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $options              = $options ?: [];
        $options['factories'] = $options['factories'] ?? [];

        $managerConfig = [];
        if ($options) {
            $managerConfig = $options;
        } elseif ($container->has('config')) {
            $managerConfig = $container->get('config')['view_helpers'] ?? [];
        }

        // Override plugin factories
        $managerConfig = $this->injectOverrideFactories($managerConfig, $container);

        return new HelperPluginManager($container, $managerConfig);
    }

    /**
     * Inject override factories into the plugin manager config.
     */
    private function injectOverrideFactories(array $managerConfig, ContainerInterface $services): array
    {
        // Configure URL view helper
        $urlFactory                                        = $this->createUrlHelperFactory($services);
        $managerConfig['aliases']['laminasviewhelperurl']  = ViewHelper\Url::class;
        $managerConfig['factories'][ViewHelper\Url::class] = $urlFactory;

        // Configure base path helper
        $basePathFactory                                        = $this->createBasePathHelperFactory($services);
        $managerConfig['aliases']['laminasviewhelperbasepath']  = ViewHelper\BasePath::class;
        $managerConfig['factories'][ViewHelper\BasePath::class] = $basePathFactory;

        // Configure doctype view helper
        $doctypeFactory                                        = $this->createDoctypeHelperFactory($services);
        $managerConfig['aliases']['laminasviewhelperdoctype']  = ViewHelper\Doctype::class;
        $managerConfig['factories'][ViewHelper\Doctype::class] = $doctypeFactory;

        return $managerConfig;
    }

    /**
     * Create and return a factory for creating a URL helper.
     *
     * Retrieves the application and router from the servicemanager,
     * and the route match from the MvcEvent composed by the application,
     * using them to configure the helper.
     *
     * @return callable
     */
    private function createUrlHelperFactory(ContainerInterface $services)
    {
        return function () use ($services) {
            $helper = new ViewHelper\Url();
            $helper->setRouter($services->get('HttpRouter'));

            $match = $services->get('Application')
                ->getMvcEvent()
                ->getRouteMatch();

            if ($match instanceof RouteMatch) {
                $helper->setRouteMatch($match);
            }

            return $helper;
        };
    }

    /**
     * Create and return a factory for creating a BasePath helper.
     *
     * Uses configuration and request services to configure the helper.
     *
     * @return callable
     */
    private function createBasePathHelperFactory(ContainerInterface $services)
    {
        return function () use ($services) {
            $config = $services->has('config') ? $services->get('config') : [];
            $helper = new ViewHelper\BasePath();

            if (isset($config['view_manager']) && isset($config['view_manager']['base_path'])) {
                $helper->setBasePath($config['view_manager']['base_path']);
                return $helper;
            }

            $request = $services->get('Request');

            if (is_callable([$request, 'getBasePath'])) {
                $helper->setBasePath($request->getBasePath());
            }

            return $helper;
        };
    }

    /**
     * Create and return a Doctype helper factory.
     *
     * Other view helpers depend on this to decide which spec to generate their tags
     * based on. This is why it must be set early instead of later in the layout phtml.
     *
     * @return callable
     */
    private function createDoctypeHelperFactory(ContainerInterface $services)
    {
        return function () use ($services) {
            $config = $services->has('config') ? $services->get('config') : [];
            $config = $config['view_manager'] ?? [];
            $helper = new ViewHelper\Doctype();
            if (isset($config['doctype']) && $config['doctype']) {
                $helper->setDoctype($config['doctype']);
            }
            return $helper;
        };
    }
}
