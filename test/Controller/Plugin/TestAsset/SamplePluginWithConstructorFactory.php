<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Controller\Plugin\TestAsset;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

class SamplePluginWithConstructorFactory implements FactoryInterface
{
    protected $options;

    public function __invoke(ContainerInterface $container, $name, array $options = null)
    {
        return new SamplePluginWithConstructor($options);
    }

    /**
     * Create and return SamplePluginWithConstructor instance
     *
     * For use with laminas-servicemanager v2; proxies to __invoke().
     *
     * @param ServiceLocatorInterface $container
     * @return SamplePluginWithConstructor
     */
    public function createService(ServiceLocatorInterface $container)
    {
        $container = $container->getServiceLocator() ?: $container;
        return $this($container, SamplePluginWithConstructor::class, $this->options);
    }

    public function setCreationOptions(array $options)
    {
        $this->options = $options;
    }
}
