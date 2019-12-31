<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\TestAsset;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\AbstractFactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

class MiddlewareAbstractFactory implements AbstractFactoryInterface
{
    public $classmap = array(
        'test' => 'LaminasTest\Mvc\TestAsset\Middleware',
    );

    public function canCreate(ContainerInterface $container, $name)
    {
        if (! isset($this->classmap[$name])) {
            return false;
        }

        $classname = $this->classmap[$name];
        return class_exists($classname);
    }

    public function __invoke(ContainerInterface $container, $name, array $options = null)
    {
        $classname = $this->classmap[$name];
        return new $classname;
    }

    /**
     * {@inheritDoc}
     *
     * For use with laminas-servicemanager v2; proxies to canCreate().
     */
    public function canCreateServiceWithName(ServiceLocatorInterface $container, $name, $requestedName)
    {
        return $this->canCreate($container, $requestedName);
    }

    /**
     * Create and return callable instance
     *
     * For use with laminas-servicemanager v2; proxies to __invoke().
     *
     * {@inheritDoc}
     *
     * @return callable
     */
    public function createServiceWithName(ServiceLocatorInterface $container, $name, $requestedName)
    {
        return $this($container, $requestedName);
    }
}
