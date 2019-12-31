<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Mvc\Service;

use Interop\Container\ContainerInterface;
use Laminas\Mvc\Exception;
use Laminas\ServiceManager\Di\DiServiceInitializer;
use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\ServiceManager\ServiceManager;

/**
 * @deprecated Since 2.7.9. The factory is now defined in laminas-servicemanager-di,
 *     and removed in 3.0.0. Use Laminas\ServiceManager\Di\DiServiceInitializerFactory
 *     from laminas-servicemanager-di if you are using laminas-servicemanager v3, and/or when
 *     ready to migrate to laminas-mvc 3.0.
 */
class DiServiceInitializerFactory implements FactoryInterface
{
    /**
     * Class responsible for instantiating a DiServiceInitializer
     *
     * @param ContainerInterface $container
     * @param string $name
     * @param null|array $options
     * @return DiServiceInitializer
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        if (! class_exists(DiServiceInitializer::class)) {
            throw new Exception\RuntimeException(sprintf(
                "%s is not compatible with laminas-servicemanager v3, which you are currently using. \n"
                . "Please run 'composer require laminas/laminas-servicemanager-di', and then update\n"
                . "your configuration to use Laminas\ServiceManager\Di\DiServiceInitializerFactory instead.",
                __CLASS__
            ));
        }

        return new DiServiceInitializer($container->get('Di'), $container);
    }

    /**
     * Create and return DiServiceInitializer instance
     *
     * For use with laminas-servicemanager v2; proxies to __invoke().
     *
     * @param ServiceLocatorInterface $container
     * @return DiServiceInitializer
     */
    public function createService(ServiceLocatorInterface $container)
    {
        return $this($container, DiServiceInitializer::class);
    }
}
