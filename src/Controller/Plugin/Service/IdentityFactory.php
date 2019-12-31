<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Mvc\Controller\Plugin\Service;

use Interop\Container\ContainerInterface;
use Laminas\Authentication\AuthenticationService;
use Laminas\Mvc\Controller\Plugin\Identity;
use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

class IdentityFactory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     *
     * @return Identity
     */
    public function __invoke(ContainerInterface $container, $name, array $options = null)
    {
        $helper = new Identity();
        if ($container->has(AuthenticationService::class)) {
            $helper->setAuthenticationService($container->get(AuthenticationService::class));
        }
        return $helper;
    }

    /**
     * Create and return Identity instance
     *
     * For use with laminas-servicemanager v2; proxies to __invoke().
     *
     * @param ServiceLocatorInterface $container
     * @return Identity
     */
    public function createService(ServiceLocatorInterface $container)
    {
        // Retrieve the parent container when under laminas-servicemanager v2
        if (! method_exists($container, 'configure')) {
            $container = $container->getServiceLocator() ?: $container;
        }

        return $this($container, Identity::class);
    }
}
