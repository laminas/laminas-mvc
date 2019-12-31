<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Mvc\Service;

use ArrayAccess;
use Interop\Container\ContainerInterface;

trait ConsoleViewManagerConfigTrait
{
    /**
     * Retrieve view_manager configuration, if present.
     *
     * @param ContainerInterface $container
     * @return array
     */
    private function getConfig(ContainerInterface $container)
    {
        $config = $container->has('config') ? $container->get('config') : [];

        if (isset($config['console']['view_manager'])) {
            $config = $config['console']['view_manager'];
        } elseif (isset($config['view_manager'])) {
            $config = $config['view_manager'];
        } else {
            $config = [];
        }

        return (is_array($config) || $config instanceof ArrayAccess)
            ? $config
            : [];
    }
}
