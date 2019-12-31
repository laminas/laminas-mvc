<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Mvc\Service;

use Interop\Container\ContainerInterface;
use Laminas\Console\Console;
use Laminas\Mvc\Router\RouteStackInterface;
use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

class RouterFactory implements FactoryInterface
{
    /**
     * Create and return the router
     *
     * Delegates to either the ConsoleRouter or HttpRouter service based
     * on the environment type.
     *
     * @param  ContainerInterface $container
     * @param  string $name
     * @param  null|array $options
     * @return RouteStackInterface
     */
    public function __invoke(ContainerInterface $container, $name, array $options = null)
    {
        // Console environment?
        if ($name === 'ConsoleRouter'                                   // force console router
            || (strtolower($name) === 'router' && Console::isConsole()) // auto detect console
        ) {
            return $container->get('ConsoleRouter');
        }

        return $container->get('HttpRouter');
    }

    /**
     * Create and return RouteStackInterface instance
     *
     * For use with laminas-servicemanager v2; proxies to __invoke().
     *
     * @param ServiceLocatorInterface $container
     * @param null|string $normalizedName
     * @param null|string $requestedName
     * @return RouteStackInterface
     */
    public function createService(ServiceLocatorInterface $container, $normalizedName = null, $requestedName = null)
    {
        if ($normalizedName === 'router' && Console::isConsole()) {
            $requestedName = 'ConsoleRouter';
        }

        return $this($container, $requestedName);
    }
}
