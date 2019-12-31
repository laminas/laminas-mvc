<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Mvc\Service;

use Laminas\Console\Console;
use Laminas\Mvc\Router\Console\SimpleRouteStack as ConsoleRouter;
use Laminas\Mvc\Router\Http\TreeRouteStack as HttpRouter;
use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

/**
 * @category   Laminas
 * @package    Laminas_Mvc
 * @subpackage Service
 */
class RouterFactory implements FactoryInterface
{
    /**
     * Create and return the router
     *
     * Retrieves the "router" key of the Config service, and uses it
     * to instantiate the router. Uses the TreeRouteStack implementation by
     * default.
     *
     * @param  ServiceLocatorInterface        $serviceLocator
     * @param string|null                     $cName
     * @param string|null                     $rName
     * @return \Laminas\Mvc\Router\RouteStackInterface
     */
    public function createService(ServiceLocatorInterface $serviceLocator, $cName = null, $rName = null)
    {
        $config = $serviceLocator->get('Config');

        if (
            $rName === 'ConsoleRouter' ||                   // force console router
            ($cName === 'router' && Console::isConsole())       // auto detect console
        ) {
            // We are in a console, use console router.
            if (isset($config['console']) && isset($config['console']['router'])) {
                $routerConfig = $config['console']['router'];
            } else {
                $routerConfig = array();
            }

            $router = ConsoleRouter::factory($routerConfig);
        } else {
            // This is an HTTP request, so use HTTP router
            $routerConfig = isset($config['router']) ? $config['router'] : array();
            $router = HttpRouter::factory($routerConfig);
        }
        return $router;
    }
}
