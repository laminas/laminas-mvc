<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Mvc\Service;

use Laminas\ServiceManager\Di\DiAbstractServiceFactory;
use Laminas\ServiceManager\Di\DiServiceInitializer;
use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

/**
 * @category   Laminas
 * @package    Laminas_Mvc
 * @subpackage Service
 */
abstract class AbstractPluginManagerFactory implements FactoryInterface
{
    const PLUGIN_MANAGER_CLASS = 'AbstractPluginManager';

    /**
     * Create and return a plugin manager.
     * Classes that extend this should provide a valid class for
     * the PLUGIN_MANGER_CLASS constant.
     *
     * @param  ServiceLocatorInterface $serviceLocator
     * @return AbstractPluginManager
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $pluginManagerClass = static::PLUGIN_MANAGER_CLASS;
        $plugins = new $pluginManagerClass;
        $plugins->setServiceLocator($serviceLocator);
        $configuration    = $serviceLocator->get('Config');
        if (isset($configuration['di']) && $serviceLocator->has('Di')) {
            $di = $serviceLocator->get('Di');
            $plugins->addAbstractFactory(
                new DiAbstractServiceFactory($di, DiAbstractServiceFactory::USE_SL_BEFORE_DI)
            );
            $plugins->addInitializer(
                new DiServiceInitializer($di, $serviceLocator)
            );
        }
        return $plugins;
    }
}
