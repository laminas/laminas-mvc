<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Service\TestAsset;

use Laminas\Stdlib\DispatchableInterface;
use Laminas\Stdlib\RequestInterface;
use Laminas\Stdlib\ResponseInterface;

class DuckTypedServiceLocatorAwareController extends DuckTypedServiceLocatorAware implements
    DispatchableInterface
{
    public function dispatch(RequestInterface $request, ResponseInterface $response = null)
    {
    }
}
