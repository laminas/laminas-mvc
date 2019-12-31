<?php
namespace LaminasTest\Mvc\Service\TestAsset;

use Laminas\Stdlib\DispatchableInterface;
use Laminas\Stdlib\RequestInterface;
use Laminas\Stdlib\ResponseInterface;

class ControllerWithDependencies implements DispatchableInterface
{
    /**
     * @var \stdClass
     */
    public $injectedValue;

    /**
     * @param \stdClass $injected
     */
    public function setInjectedValue(\stdClass $injected)
    {
        $this->injectedValue = $injected;
    }

    public function dispatch(RequestInterface $request, ResponseInterface $response = null)
    {
    }
}
