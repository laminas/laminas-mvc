<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\Controller\TestAsset;

use Laminas\ServiceManager\Factory\AbstractFactoryInterface;
use Psr\Container\ContainerInterface;

class UnlocatableControllerLoaderAbstractFactory implements AbstractFactoryInterface
{
    /**
     * @param string $name
     */
    public function canCreate(ContainerInterface $container, $name): bool
    {
        return false;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function __invoke(ContainerInterface $container, $name, ?array $options = null)
    {
        return null;
    }
}
