<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Mvc\Service;

use Container\Interop\ContainerInterface;
use Zend\ServiceManager\Di\DiServiceInitializer;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class DiServiceInitializerFactory implements FactoryInterface
{
    /**
     * Class responsible for instantiating a DiServiceInitializer
     *
     * @param ContainerInterface $container
     * @param string $name
     * @param null|array $options
     * @return DiServiceInitializer
     */
    public function __invoke(ContainerInterface $container, $name, array $options = null)
    {
        return new DiServiceInitializer($container->get('Di'), $container);
    }

    /**
     * Create and return DiServiceInitializer instance
     *
     * For use with zend-servicemanager v2; proxies to __invoke().
     *
     * @param ServiceLocatorInterface $container
     * @return DiServiceInitializer
     */
    public function createService(ServiceLocatorInterface $container)
    {
        return $this($container, DiServiceInitializer::class);
    }
}
