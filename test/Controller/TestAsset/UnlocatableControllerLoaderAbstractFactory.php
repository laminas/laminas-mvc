<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Controller\TestAsset;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\AbstractFactoryInterface;

class UnlocatableControllerLoaderAbstractFactory implements AbstractFactoryInterface
{
    public function canCreate(ContainerInterface $container, $name)
    {
        return false;
    }

    public function __invoke(ContainerInterface $container, $name, array $options = null)
    {
    }
}
