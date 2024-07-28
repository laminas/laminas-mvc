<?php

declare(strict_types=1);

namespace Laminas\Mvc\Service;

use Laminas\EventManager\EventManagerInterface;
use Laminas\Mvc\Application;
use Laminas\Mvc\ApplicationListenerProvider;
use Psr\Container\ContainerInterface;

final class ApplicationFactory
{
    public function __invoke(ContainerInterface $container): Application
    {
        return new Application(
            $container,
            $container->get(EventManagerInterface::class),
            $container->get(ApplicationListenerProvider::class),
            $container->get('Request'),
            $container->get('Response')
        );
    }
}
