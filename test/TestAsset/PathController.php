<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\TestAsset;

use Laminas\Http\Response as HttpResponse;
use Laminas\Stdlib\DispatchableInterface;
use Laminas\Stdlib\RequestInterface as Request;
use Laminas\Stdlib\ResponseInterface as Response;

class PathController implements DispatchableInterface
{
    /**
     * @return Response|null
     */
    public function dispatch(Request $request, ?Response $response = null)
    {
        if (! $response) {
            $response = new HttpResponse();
        }
        $response->setContent(__METHOD__);
        return $response;
    }
}
