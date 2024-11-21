<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\Controller\TestAsset;

// phpcs:ignore
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\AbstractFactoryInterface;

class UnlocatableControllerLoaderAbstractFactory implements AbstractFactoryInterface
{
    /** @inheritDoc */
    public function canCreate(ContainerInterface $container, $requestedName)
    {
        return false;
    }

    /** @inheritDoc */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
    }
}
