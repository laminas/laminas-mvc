<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Mvc\Service;

use Laminas\Console\Console;
use Laminas\Mvc\Exception;
use Laminas\Mvc\Router\RouteMatch;
use Laminas\ServiceManager\ConfigInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\Helper as ViewHelper;
use Laminas\View\Helper\HelperInterface as ViewHelperInterface;

/**
 * @category   Laminas
 * @package    Laminas_Mvc
 * @subpackage Service
 */
class ViewHelperManagerFactory extends AbstractPluginManagerFactory
{
    const PLUGIN_MANAGER_CLASS = 'Laminas\View\HelperPluginManager';

    /**
     * An array of helper configuration classes to ensure are on the helper_map stack.
     *
     * @var array
     */
    protected $defaultHelperMapClasses = array(
        'Laminas\Form\View\HelperConfig',
        'Laminas\I18n\View\HelperConfig',
        'Laminas\Navigation\View\HelperConfig'
    );

    /**
     * Create and return the view helper manager
     *
     * @param  ServiceLocatorInterface $serviceLocator
     * @return ViewHelperInterface
     * @throws Exception\RuntimeException
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $plugins = parent::createService($serviceLocator);

        foreach ($this->defaultHelperMapClasses as $configClass) {
            if (is_string($configClass) && class_exists($configClass)) {
                $config = new $configClass;

                if (!$config instanceof ConfigInterface) {
                    throw new Exception\RuntimeException(sprintf(
                        'Invalid service manager configuration class provided; received "%s", expected class implementing %s',
                        $configClass,
                        'Laminas\ServiceManager\ConfigInterface'
                    ));
                }

                $config->configureServiceManager($plugins);
            }
        }

        // Configure URL view helper with router
        $plugins->setFactory('url', function ($sm) use($serviceLocator) {
            $helper = new ViewHelper\Url;
            $router = Console::isConsole() ? 'HttpRouter' : 'Router';
            $helper->setRouter($serviceLocator->get($router));

            $match = $serviceLocator->get('application')
                ->getMvcEvent()
                ->getRouteMatch()
            ;

            if ($match instanceof RouteMatch) {
                $helper->setRouteMatch($match);
            }

            return $helper;
        });

        $plugins->setFactory('basepath', function ($sm) use($serviceLocator) {
            $config = $serviceLocator->get('Config');
            $basePathHelper = new ViewHelper\BasePath;
            if (isset($config['view_manager']) && isset($config['view_manager']['base_path'])) {
                $basePath = $config['view_manager']['base_path'];
            } else {
                $basePath = $serviceLocator->get('Request')->getBasePath();
            }
            $basePathHelper->setBasePath($basePath);
            return $basePathHelper;
        });

        /**
         * Configure doctype view helper with doctype from configuration, if available.
         *
         * Other view helpers depend on this to decide which spec to generate their tags
         * based on. This is why it must be set early instead of later in the layout phtml.
         */
        $plugins->setFactory('doctype', function ($sm) use($serviceLocator) {
            $config = $serviceLocator->get('Config');
            $config = $config['view_manager'];
            $doctypeHelper = new ViewHelper\Doctype;
            if (isset($config['doctype'])) {
                $doctypeHelper->setDoctype($config['doctype']);
            }
            return $doctypeHelper;
        });

        return $plugins;
    }
}
