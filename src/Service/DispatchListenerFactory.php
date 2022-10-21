<?php

declare(strict_types=1);

namespace Laminas\Mvc\Service;

use Laminas\Mvc\DispatchListener;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class DispatchListenerFactory implements FactoryInterface
{
    /**
     * Create the default dispatch listener.
     *
     * @param  string $name
     * @param  null|array $options
     * @return DispatchListener
     */
    public function __invoke(ContainerInterface $container, $name, ?array $options = null)
    {
        return new DispatchListener($container->get('ControllerManager'));
    }
}
