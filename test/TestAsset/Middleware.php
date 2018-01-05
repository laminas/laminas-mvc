<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Mvc\TestAsset;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class Middleware
{
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $next = null)
    {
        $response->getBody()->write(__CLASS__);
        return $response;
    }
}
