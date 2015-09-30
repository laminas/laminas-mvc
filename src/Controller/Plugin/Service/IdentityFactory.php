<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Mvc\Controller\Plugin\Service;

use Interop\Container\ContainerInterface;
use Zend\Authentication\AuthenticationService;
use Zend\Mvc\Controller\Plugin\Identity;
use Zend\ServiceManager\Factory\FactoryInterface;

class IdentityFactory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     *
     * @return \Zend\Mvc\Controller\Plugin\Identity
     */
    public function __invoke(ContainerInterface $container, $name, array $options = null)
    {
        $helper = new Identity();
        if ($container->has(AuthenticationService::class)) {
            $helper->setAuthenticationService($container->get(AuthenticationService::class));
        }
        return $helper;
    }
}
