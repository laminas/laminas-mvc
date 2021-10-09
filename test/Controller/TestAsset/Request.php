<?php

namespace LaminasTest\Mvc\Controller\TestAsset;

use Laminas\Http\Request as HttpRequest;

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
