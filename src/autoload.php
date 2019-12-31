<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Mvc;

use Laminas\ServiceManager\PluginManagerInterface;

if (class_exists(PluginManagerInterface::class)) {
    class_alias(Controller\PluginManagerSM3::class, Controller\PluginManager::class, true);
} else {
    class_alias(Controller\PluginManagerSM2::class, Controller\PluginManager::class, true);
}
