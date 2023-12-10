<?php

declare(strict_types=1);

namespace Laminas\Mvc\Service;

use Laminas\Mvc\Application;
use Laminas\Mvc\ApplicationListenersProvider;
use Psr\Container\ContainerInterface;

use function assert;
use function is_array;

final class ApplicationListenersProviderFactory
{
    /**
     * For default listeners @see ApplicationListenersProvider::DEFAULT_LISTENERS
     *
     * Extra listeners could be specified via configuration at `$config[Application::class]['listeners']`
     * or overridden via delegator factory.
     */
    public function __invoke(ContainerInterface $container): ApplicationListenersProvider
    {
        $config = $container->get('config');
        assert(is_array($config));

        /** @psalm-var list<string> $listeners */
        $listeners = $config[Application::class]['listeners'] ?? [];
        return ApplicationListenersProvider::withDefaultListeners($container, $listeners);
    }
}
