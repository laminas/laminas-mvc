<?php

namespace Laminas\Mvc\Controller;

use Laminas\Mvc\MvcEvent;
use Laminas\Psr7Bridge\Psr7Response;
use Laminas\Router\Http\RouteMatch;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

abstract class AbstractRestfulControllerRequestHandler extends AbstractRestfulController implements
    RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $laminasRequest = Psr7Response::toLaminas($request);
        $routerMatch = $request->getAttribute(RouteMatch::class);
        if ($routerMatch !== null) {
            $this->setEvent((new MvcEvent())->setRouteMatch($routerMatch));
        }

        return $this->dispatch($laminasRequest);
    }
}
