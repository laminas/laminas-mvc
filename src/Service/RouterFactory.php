<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Mvc\Service;

use Interop\Container\ContainerInterface;
use Zend\Console\Console;
use Zend\ServiceManager\Factory\FactoryInterface;

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
     * @return \Zend\Mvc\Router\RouteStackInterface
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
}
