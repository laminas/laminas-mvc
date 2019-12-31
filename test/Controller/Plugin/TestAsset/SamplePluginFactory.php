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

class SamplePluginFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $name, array $options = null)
    {
        return new SamplePlugin();
    }

    /**
     * Create and return SamplePlugin instance
     *
     * For use with laminas-servicemanager v2; proxies to __invoke().
     *
     * @param ServiceLocatorInterface $container
     * @return SamplePlugin
     */
    public function createService(ServiceLocatorInterface $container)
    {
        return $this($container, SamplePlugin::class);
    }
}
