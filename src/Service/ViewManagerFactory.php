<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Mvc\Service;

use Interop\Container\ContainerInterface;
use Laminas\Console\Console;
use Laminas\Mvc\View\Console\ViewManager as ConsoleViewManager;
use Laminas\Mvc\View\Http\ViewManager as HttpViewManager;
use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

class ViewManagerFactory implements FactoryInterface
{
    /**
     * Create and return a view manager based on detected environment
     *
     * @param  ContainerInterface $container
     * @param  string $name
     * @param  null|array $options
     * @return ConsoleViewManager|HttpViewManager
     */
    public function __invoke(ContainerInterface $container, $name, array $options = null)
    {
        if (Console::isConsole()) {
            return $container->get('ConsoleViewManager');
        }

        return $container->get('HttpViewManager');
    }

    /**
     * Create and return HttpViewManager or ConsoleViewManager instance
     *
     * For use with laminas-servicemanager v2; proxies to __invoke().
     *
     * @param ServiceLocatorInterface $container
     * @return HttpViewManager|ConsoleViewManager
     */
    public function createService(ServiceLocatorInterface $container)
    {
        $type = Console::isConsole() ? ConsoleViewManager::class : HttpViewManager::class;
        return $this($container, $type);
    }
}
