<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Mvc\TestAsset;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\AbstractFactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class MiddlewareAbstractFactory implements AbstractFactoryInterface
{
    public $classmap = array(
        'test' => 'ZendTest\Mvc\TestAsset\Middleware',
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
     * For use with zend-servicemanager v2; proxies to canCreate().
     */
    public function canCreateServiceWithName(ServiceLocatorInterface $container, $name, $requestedName)
    {
        return $this->canCreate($container, $requestedName);
    }

    /**
     * Create and return callable instance
     *
     * For use with zend-servicemanager v2; proxies to __invoke().
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
