<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Service\TestAsset;

use Laminas\ServiceManager\ServiceLocatorInterface;

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
