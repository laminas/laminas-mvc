<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Controller\TestAsset;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\AbstractFactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

class ControllerLoaderAbstractFactory implements AbstractFactoryInterface
{
    protected $classmap = array(
        'path' => 'LaminasTest\Mvc\TestAsset\PathController',
    );

    public function canCreate(ContainerInterface $container, $name)
    {
        if (! isset($this->classmap[$name])) {
            return false;
        }

        $classname = $this->classmap[$name];
        return class_exists($classname);
    }

    public function canCreateServiceWithName(ServiceLocatorInterface $container, $normalizedName, $name)
    {
        return $this->canCreate($container, $name);
    }

    public function __invoke(ContainerInterface $container, $name, array $options = null)
    {
        $classname = $this->classmap[$name];
        return new $classname;
    }

    /**
     * Create and return DispatchableInterface instance
     *
     * For use with laminas-servicemanager v2; proxies to __invoke().
     *
     * {@inheritDoc}
     *
     * @return DispatchableInterface
     */
    public function createServiceWithName(ServiceLocatorInterface $container, $name, $requestedName)
    {
        return $this($container, $requestedName);
    }
}
