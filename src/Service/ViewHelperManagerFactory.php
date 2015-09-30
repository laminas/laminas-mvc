<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Mvc\Service;

use Interop\Container\ContainerInterface;
use Zend\Console\Console;
use Zend\Mvc\Router\RouteMatch;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\Stdlib\ArrayUtils;
use Zend\View\Helper as ViewHelper;
use Zend\View\HelperPluginManager;

class ViewHelperManagerFactory extends AbstractPluginManagerFactory
{
    const PLUGIN_MANAGER_CLASS = HelperPluginManager::class;

    /**
     * An array of helper configuration classes to ensure are on the helper_map stack.
     *
     * These are *not* imported; that way they can be optional dependencies.
     *
     * @todo Re-enable these once their components have been updated to zend-servicemanager v3
     * @var array
     */
    protected $defaultHelperMapClasses = [
        /*
        'Zend\Form\View\HelperConfig',
        'Zend\I18n\View\HelperConfig',
        'Zend\Navigation\View\HelperConfig',
         */
    ];

    /**
     * Create and return the view helper manager
     *
     * @param  ContainerInterface $container
     * @return HelperPluginManager
     * @throws ServiceNotCreatedException
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $options = $options ?: [];

        foreach ($this->defaultHelperMapClasses as $configClass) {
            if (! is_string($configClass) || ! class_exists($configClass)) {
                continue;
            }

            $config = new $configClass();

            if (! $config instanceof ConfigInterface) {
                throw new ServiceNotCreatedException(sprintf(
                    'Invalid service manager configuration class provided; received "%s", expected class implementing %s',
                    $configClass,
                    ConfigInterface::class
                ));
            }

            $options = ArrayUtils::merge($options, $config->toArray());
        }

        $config = $container->has('config') ? $container->get('config') : [];

        $options['factories'] = isset($options['factories']) ? $options['factories'] : [];

        // Configure URL view helper factory
        $options['factories'][ViewHelper\Url::class] = $this->createUrlHelperFactory();

        // Configure basepath view helper factory
        $options['factories'][ViewHelper\BasePath::class] = $this->createBasePathHelperFactory($config);

        // Configure doctype view helper factory
        $options['factories'][ViewHelper\Doctype::class] = $this->createDoctypeHelperFactory();

        return parent::__invoke($container, $requestedName, $options);
    }

    /**
     * Create a factory for the "url" view helper
     *
     * @return callable
     */
    private function createUrlHelperFactory()
    {
        return function ($container, $name, array $options = null) {
            $helper = new ViewHelper\Url;
            $router = Console::isConsole() ? 'HttpRouter' : 'Router';
            $helper->setRouter($container->get($router));

            $match = $container->get('application')
                ->getMvcEvent()
                ->getRouteMatch()
            ;

            if ($match instanceof RouteMatch) {
                $helper->setRouteMatch($match);
            }

            return $helper;
        };
    }

    /**
     * Create a factory for the "basepath" view helper
     *
     * @param  array $config
     * @return callable
     */
    private function createBasePathHelperFactory($config)
    {
        return function ($container, $name, array $options = null) {
            $config = $container->has('config') ? $container->get('config') : [];
            $helper = new ViewHelper\BasePath;

            if (Console::isConsole()
                && isset($config['view_manager']['base_path_console'])
            ) {
                $helper->setBasePath($config['view_manager']['base_path_console']);
                return $helper;
            }

            if (isset($config['view_manager']) && isset($config['view_manager']['base_path'])) {
                $helper->setBasePath($config['view_manager']['base_path']);
                return $helper;
            }

            $request = $container->get('Request');
            if (is_callable([$request, 'getBasePath'])) {
                $helper->setBasePath($request->getBasePath());
            }

            return $helper;
        };
    }

    /**
     * Configure doctype view helper with doctype from configuration, if available.
     *
     * Other view helpers depend on this to decide which spec to generate their tags
     * based on.
     *
     * This is why it must be set early instead of later in the layout phtml.
     *
     * @return callable
     */
    private function createDoctypeHelperFactory()
    {
        return function ($container, $name, array $options = null) {
            $config = $container->has('config') ? $container->get('config') : [];
            $config = isset($config['view_manager']) ? $config['view_manager'] : [];
            $helper = new ViewHelper\Doctype;
            if (isset($config['doctype']) && $config['doctype']) {
                $helper->setDoctype($config['doctype']);
            }
            return $helper;
        };
    }
}
