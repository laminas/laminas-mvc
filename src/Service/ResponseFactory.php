<?php

namespace Laminas\Mvc\Service;

// phpcs:ignore
use Interop\Container\ContainerInterface;
use Laminas\Http\PhpEnvironment\Response as HttpResponse;
use Laminas\ServiceManager\Factory\FactoryInterface;

class ResponseFactory implements FactoryInterface
{
    /**
     * Create and return a response instance.
     *
     * @param  string $requestedName
     * @param  null|array $options
     * @return HttpResponse
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        return new HttpResponse();
    }
}
