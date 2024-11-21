<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\Controller\TestAsset;

// phpcs:ignore
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\AbstractFactoryInterface;
use LaminasTest\Mvc\TestAsset\PathController;

use function class_exists;

class ControllerLoaderAbstractFactory implements AbstractFactoryInterface
{
    protected array $classmap = [
        'path' => PathController::class,
    ];

    /** @inheritDoc */
    public function canCreate(ContainerInterface $container, $requestedName): bool
    {
        if (! isset($this->classmap[$requestedName])) {
            return false;
        }

        $classname = $this->classmap[$requestedName];
        return class_exists($classname);
    }

    /** @inheritDoc */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $classname = $this->classmap[$requestedName];
        return new $classname();
    }
}
