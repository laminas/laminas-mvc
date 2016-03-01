<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Mvc\Service;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Di\DiAbstractServiceFactory;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\ServiceManager;

class DiAbstractServiceFactoryFactory implements FactoryInterface
{
    /**
     * Class responsible for instantiating a DiAbstractServiceFactory
     *
     * @param ContainerInterface $container
     * @param string $name
     * @param null|array $options
     * @return DiAbstractServiceFactory
     */
    public function __invoke(ContainerInterface $container, $name, array $options = null)
    {
        $factory = new DiAbstractServiceFactory($container->get('Di'), DiAbstractServiceFactory::USE_SL_BEFORE_DI);

        if ($serviceLocator instanceof ServiceManager) {
            $serviceLocator->addAbstractFactory($factory, false);
        }

        return $factory;
    }

    /**
     * Create and return DiAbstractServiceFactory instance
     *
     * For use with zend-servicemanager v2; proxies to __invoke().
     *
     * @param ServiceLocatorInterface $container
     * @return DiAbstractServiceFactory
     */
    public function createService(ServiceLocatorInterface $container)
    {
        return $this($container, DiAbstractServiceFactory::class);
    }
}
