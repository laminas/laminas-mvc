<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\TestAsset;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\AbstractFactoryInterface;

use function class_exists;

class MiddlewareAbstractFactory implements AbstractFactoryInterface
{
    public $classmap = [
        'test' => Middleware::class,
    ];

    public function canCreate(ContainerInterface $container, $name)
    {
        if (! isset($this->classmap[$name])) {
            return false;
        }

        $classname = $this->classmap[$name];
        return class_exists($classname);
    }

    public function __invoke(ContainerInterface $container, $name, ?array $options = null)
    {
        $classname = $this->classmap[$name];
        return new $classname();
    }
}
