<?php

namespace Laminas\Mvc\Service;

// phpcs:ignore
use Interop\Container\ContainerInterface;
use Laminas\Http\PhpEnvironment\Request as HttpRequest;
use Laminas\ServiceManager\Factory\FactoryInterface;

class RequestFactory implements FactoryInterface
{
    /**
     * Create and return a request instance.
     *
     * @param  string $requestedName
     * @param  null|array $options
     * @return HttpRequest
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        return new HttpRequest();
    }
}
