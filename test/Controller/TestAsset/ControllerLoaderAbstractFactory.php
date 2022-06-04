<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\Controller\TestAsset;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\AbstractFactoryInterface;
use LaminasTest\Mvc\TestAsset\PathController;

use function class_exists;

class ControllerLoaderAbstractFactory implements AbstractFactoryInterface
{
    protected array $classmap = [
        'path' => PathController::class,
    ];

    /**
     * @param string $name
     */
    public function canCreate(ContainerInterface $container, $name): bool
    {
        if (! isset($this->classmap[$name])) {
            return false;
        }

        $classname = $this->classmap[$name];
        return class_exists($classname);
    }

    /**
     * @param string $name
     */
    public function __invoke(ContainerInterface $container, $name, ?array $options = null): object
    {
        $classname = $this->classmap[$name];
        return new $classname();
    }
}
