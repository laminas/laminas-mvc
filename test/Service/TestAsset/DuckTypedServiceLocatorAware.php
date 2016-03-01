<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Mvc\Service\TestAsset;

use Zend\ServiceManager\ServiceLocatorInterface;

class DuckTypedServiceLocatorAware
{
    private $container;

    public function setServiceLocator(ServiceLocatorInterface $container)
    {
        $this->container = $container;
    }

    public function getServiceLocator()
    {
        return $this->container;
    }
}
