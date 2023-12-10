<?php

declare(strict_types=1);

namespace Laminas\Mvc\Service;

use Laminas\EventManager\EventManagerInterface;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\Application;
use Laminas\Mvc\ApplicationListenersProvider;
use Psr\Container\ContainerInterface;

final class ApplicationFactory
{
    public function __invoke(ContainerInterface $container): Application
    {
        return new Application(
            $container,
            $container->get(EventManagerInterface::class),
            $container->get(ApplicationListenersProvider::class),
            static fn (): Request => $container->get('Request'),
            static fn (): Response => $container->get('Response')
        );
    }
}
