<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Mvc\Controller\TestAsset;

use Zend\Stdlib\DispatchableInterface;
use Zend\Stdlib\RequestInterface;
use Zend\Stdlib\ResponseInterface as Response;

class UneventfulController implements DispatchableInterface
{
    public function dispatch(RequestInterface $request, Response $response = null)
    {
    }
}
