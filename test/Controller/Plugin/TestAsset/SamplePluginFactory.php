<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\Controller\Plugin\TestAsset;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class SamplePluginFactory implements FactoryInterface
{
    /**
     * @param string $name
     */
    public function __invoke(ContainerInterface $container, $name, ?array $options = null): SamplePlugin
    {
        return new SamplePlugin();
    }
}
