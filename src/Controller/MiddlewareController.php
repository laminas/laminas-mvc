<?php
/**
 * @see       https://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Mvc\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\EventManager\EventManagerInterface;
use Zend\Http\Request;
use Zend\Mvc\Exception\ReachedFinalHandlerException;
use Zend\Mvc\Exception\RuntimeException;
use Zend\Mvc\MvcEvent;
use Zend\Psr7Bridge\Psr7ServerRequest;
use Zend\Router\RouteMatch;
use Zend\Stratigility\Delegate\CallableDelegateDecorator;
use Zend\Stratigility\MiddlewarePipe;

/**
 * @internal don't use this in your codebase, or else @ocramius will hunt you
 *     down. This is just an internal hack to make middleware trigger
 *     'dispatch' events attached to the DispatchableInterface identifier.
 *
 *     Specifically, it will receive a @see MiddlewarePipe and a
 *     @see ResponseInterface prototype, and then dispatch the pipe whilst still
 *     behaving like a normal controller. That is needed for any events
 *     attached to the @see \Zend\Stdlib\DispatchableInterface identifier to
 *     reach their listeners on any attached
 *     @see \Zend\EventManager\SharedEventManagerInterface
 */
final class MiddlewareController extends AbstractController
{
    /**
     * @var MiddlewarePipe
     */
    private $pipe;

    /**
     * @var ResponseInterface
     */
    private $responsePrototype;

    public function __construct(
        MiddlewarePipe $pipe,
        ResponseInterface $responsePrototype,
        EventManagerInterface $eventManager,
        MvcEvent $event
    ) {
        $this->eventIdentifier   = __CLASS__;
        $this->pipe              = $pipe;
        $this->responsePrototype = $responsePrototype;

        $this->setEventManager($eventManager);
        $this->setEvent($event);
    }

    /**
     * {@inheritDoc}
     *
     * @throws RuntimeException
     */
    public function onDispatch(MvcEvent $e)
    {
        $routeMatch  = $e->getRouteMatch();
        $psr7Request = $this->populateRequestParametersFromRoute(
            $this->loadRequest()->withAttribute(RouteMatch::class, $routeMatch),
            $routeMatch
        );

        $result = $this->pipe->process($psr7Request, new CallableDelegateDecorator(
            function () {
                throw ReachedFinalHandlerException::create();
            },
            $this->responsePrototype
        ));

        $e->setResult($result);

        return $result;
    }

    /**
     * @return \Zend\Diactoros\ServerRequest
     *
     * @throws RuntimeException
     */
    private function loadRequest()
    {
        $request = $this->request;

        if (! $request instanceof Request) {
            throw new RuntimeException(sprintf(
                'Expected request to be a %s, %s given',
                Request::class,
                get_class($request)
            ));
        }

        return Psr7ServerRequest::fromZend($request);
    }

    /**
     * @param ServerRequestInterface $request
     * @param RouteMatch|null $routeMatch
     *
     * @return ServerRequestInterface
     */
    private function populateRequestParametersFromRoute(ServerRequestInterface $request, RouteMatch $routeMatch = null)
    {
        if (! $routeMatch) {
            return $request;
        }

        foreach ($routeMatch->getParams() as $key => $value) {
            $request = $request->withAttribute($key, $value);
        }

        return $request;
    }
}
