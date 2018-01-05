<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Mvc\Controller\TestAsset;

use Zend\Http\Request as HttpRequest;

/**
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */
class Request extends HttpRequest
{
    /**
     * Override the method setter, to allow arbitrary HTTP methods
     *
     * @param  string $method
     * @return Request
     */
    public function setMethod($method)
    {
        $method = strtoupper($method);
        $this->method = $method;
        return $this;
    }
}
