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

class SamplePluginFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $name, array $options = null)
    {
        return new SamplePlugin();
    }

    /**
     * Create and return SamplePlugin instance
     *
     * For use with zend-servicemanager v2; proxies to __invoke().
     *
     * @param ServiceLocatorInterface $container
     * @return SamplePlugin
     */
    public function createService(ServiceLocatorInterface $container)
    {
        return $this($container, SamplePlugin::class);
    }
}
