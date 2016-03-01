<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Mvc\Controller\Plugin\TestAsset;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

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
     * For use with zend-servicemanager v2; proxies to __invoke().
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
