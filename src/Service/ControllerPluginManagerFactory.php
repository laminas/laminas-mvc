<?php

declare(strict_types=1);

namespace Laminas\Mvc\Service;

use Interop\Container\ContainerInterface;
use Laminas\Mvc\Controller\PluginManager as ControllerPluginManager;
use Laminas\ServiceManager\Factory\FactoryInterface;

class ControllerPluginManagerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $name, ?array $options = null)
    {
        if ($options) {
            return new ControllerPluginManager($container, $options);
        }
        $managerConfig = [];
        if ($container->has('config')) {
            $managerConfig = $container->get('config')['controller_plugins'] ?? [];
        }
        return new ControllerPluginManager($container, $managerConfig);
    }
}
