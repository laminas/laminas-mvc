<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Mvc\Service;

use Interop\Container\ContainerInterface;
use Laminas\Mvc\Application;
use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

class ApplicationFactory implements FactoryInterface
{
    /**
     * Create the Application service (v3)
     *
     * Creates a Laminas\Mvc\Application service, passing it the configuration
     * service and the service manager instance.
     *
     * @param  ContainerInterface $container
     * @param  string $name
     * @param  null|array $options
     * @return Application
     */
    public function __invoke(ContainerInterface $container, $name, array $options = null)
    {
        return new Application(
            $container->get('config'),
            $container,
            $container->get('EventManager'),
            $container->get('Request'),
            $container->get('Response')
        );
    }

    /**
     * Create the Application service (v2)
     *
     * Proxies to __invoke().
     *
     * @param ServiceLocatorInterface $container
     * @return Application
     */
    public function createService(ServiceLocatorInterface $container)
    {
        return $this($container, Application::class);
    }
}
