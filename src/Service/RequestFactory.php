<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Mvc\Service;

use Interop\Container\ContainerInterface;
use Laminas\Console\Console;
use Laminas\Console\Request as ConsoleRequest;
use Laminas\Http\PhpEnvironment\Request as HttpRequest;
use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

class RequestFactory implements FactoryInterface
{
    /**
     * Create and return a request instance, according to current environment.
     *
     * @param  ContainerInterface $container
     * @param  string $name
     * @param  null|array $options
     * @return ConsoleRequest|HttpRequest
     */
    public function __invoke(ContainerInterface $container, $name, array $options = null)
    {
        if (Console::isConsole()) {
            return new ConsoleRequest();
        }

        return new HttpRequest();
    }

    /**
     * Create and return HttpRequest or ConsoleRequest instance
     *
     * For use with laminas-servicemanager v2; proxies to __invoke().
     *
     * @param ServiceLocatorInterface $container
     * @return HttpRequest|ConsoleRequest
     */
    public function createService(ServiceLocatorInterface $container)
    {
        $type = Console::isConsole() ? ConsoleRequest::class : HttpRequest::class;
        return $this($container, $type);
    }
}
