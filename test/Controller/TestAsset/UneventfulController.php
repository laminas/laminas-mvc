<?php

namespace LaminasTest\Mvc\Controller\TestAsset;

use Laminas\Stdlib\DispatchableInterface;
use Laminas\Stdlib\RequestInterface;
use Laminas\Stdlib\ResponseInterface as Response;

class UneventfulController implements DispatchableInterface
{
    public function dispatch(RequestInterface $request, Response $response = null)
    {
    }
}
