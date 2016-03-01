<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Mvc\Controller\TestAsset;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\AbstractFactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class UnlocatableControllerLoaderAbstractFactory implements AbstractFactoryInterface
{
    public function canCreate(ContainerInterface $container, $name)
    {
        return false;
    }

    /**
     * {@inheritDoc}
     *
     * For use with zend-servicemanager v2; proxies to canCreate().
     */
    public function canCreateServiceWithName(ServiceLocatorInterface $container, $name, $requestedName)
    {
        return $this->canCreate($container, $requestedName);
    }

    public function __invoke(ContainerInterface $container, $name, array $options = null)
    {
    }

    public function createServiceWithName(ServiceLocatorInterface $container, $name, $requestedName)
    {
    }
}
