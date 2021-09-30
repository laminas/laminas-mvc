<?php

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
