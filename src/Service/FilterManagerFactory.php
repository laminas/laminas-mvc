<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Mvc\Service;

use Laminas\ServiceManager\ConfigInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

class FilterManagerFactory extends AbstractPluginManagerFactory
{
    const PLUGIN_MANAGER_CLASS = 'Laminas\Filter\FilterPluginManager';

    /**
     * Create and return the filter plugin manager
     *
     * @param  ServiceLocatorInterface $serviceLocator
     * @return FilterPluginManager
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $plugins = parent::createService($serviceLocator);
        return $plugins;
    }
}
