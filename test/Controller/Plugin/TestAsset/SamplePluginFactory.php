<?php

namespace LaminasTest\Mvc\Controller\Plugin\TestAsset;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class SamplePluginFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $name, array $options = null)
    {
        return new SamplePlugin();
    }
}
