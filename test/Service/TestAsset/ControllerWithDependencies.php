<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\Service\TestAsset;

use Laminas\Stdlib\DispatchableInterface;
use Laminas\Stdlib\RequestInterface;
use Laminas\Stdlib\ResponseInterface;
use stdClass;

class ControllerWithDependencies implements DispatchableInterface
{
    /** @var stdClass */
    public $injectedValue;

    public function setInjectedValue(stdClass $injected)
    {
        $this->injectedValue = $injected;
    }

    public function dispatch(RequestInterface $request, ?ResponseInterface $response = null)
    {
    }
}
