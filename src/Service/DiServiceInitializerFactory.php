<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Mvc\Service;

use Container\Interop\ContainerInterface;
use Laminas\ServiceManager\Di\DiServiceInitializer;
use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

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
    public function __invoke(ContainerInterface $container, $name, array $options = null)
    {
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
