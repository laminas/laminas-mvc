<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Mvc\Controller;

use Psr\Http\Message\ResponseInterface;
use Zend\EventManager\EventManager;
use Zend\Http\Request;
use Zend\Http\Response;
use Zend\Mvc\Exception\ReachedFinalHandlerException;
use Zend\Mvc\Exception\RuntimeException;
use Zend\Mvc\MvcEvent;
use Zend\Psr7Bridge\Psr7ServerRequest;
use Zend\Router\RouteMatch;
use Zend\Stratigility\Delegate\CallableDelegateDecorator;
use Zend\Stratigility\MiddlewarePipe;

/**
 * Note: I'm a terrible person
 *
 * @internal don't use this in your codebase, or else @ocramius will hunt you down. This is just an internal
 * @internal hack to make middleware trigger 'dispatch' events attached to the DispatchableInterface identifier.
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
        EventManager $eventManager,
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
     * @throws \Zend\Mvc\Exception\RuntimeException
     */
    public function onDispatch(MvcEvent $e)
    {
        $request  = $this->request;
        $response = $this->response;

        if (! $request instanceof Request) {
            throw new RuntimeException(sprintf(
                'Expected request to be a %s, %s given',
                Request::class,
                get_class($request)
            ));
        }

        if (! $response instanceof Response) {
            throw new RuntimeException(sprintf(
                'Expected response to be a %s, %s given',
                Response::class,
                get_class($response)
            ));
        }

        $routeMatch  = $e->getRouteMatch();
        $psr7Request = Psr7ServerRequest::fromZend($request)->withAttribute(RouteMatch::class, $routeMatch);

        if ($routeMatch) {
            foreach ($routeMatch->getParams() as $key => $value) {
                $psr7Request = $psr7Request->withAttribute($key, $value);
            }
        }

        $result = $this->pipe->process($psr7Request, new CallableDelegateDecorator(
            function () {
                throw ReachedFinalHandlerException::create();
            },
            $this->responsePrototype
        ));

        $e->setResult($result);

        return $result;
    }
}
