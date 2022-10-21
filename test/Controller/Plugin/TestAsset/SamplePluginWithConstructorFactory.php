<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\Controller\Plugin\TestAsset;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class SamplePluginWithConstructorFactory implements FactoryInterface
{
    /**
     * @param string $name
     */
    public function __invoke(ContainerInterface $container, $name, ?array $options = null): SamplePluginWithConstructor
    {
        return new SamplePluginWithConstructor($options);
    }
}
