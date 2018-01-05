<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Mvc\Service\TestAsset;

use Zend\Stdlib\DispatchableInterface;
use Zend\Stdlib\RequestInterface;
use Zend\Stdlib\ResponseInterface;

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
