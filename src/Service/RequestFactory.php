<?php

namespace Laminas\Mvc\Service;

use Interop\Container\ContainerInterface;
use Laminas\Http\PhpEnvironment\Request as HttpRequest;
use Laminas\ServiceManager\Factory\FactoryInterface;

class RequestFactory implements FactoryInterface
{
    /**
     * Create and return a request instance.
     *
     * @param  ContainerInterface $container
     * @param  string $name
     * @param  null|array $options
     * @return HttpRequest
     */
    public function __invoke(ContainerInterface $container, $name, array $options = null)
    {
        return new HttpRequest();
    }
}
