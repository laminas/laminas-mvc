<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Mvc;

use Interop\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as PsrServerRequestInterface;
use Zend\EventManager\AbstractListenerAggregate;
use Zend\EventManager\EventManagerInterface;
use Zend\Mvc\Exception\MiddlewareNotCallableException;
use Zend\Mvc\Exception\ReachedFinalHandlerException;
use Zend\Psr7Bridge\Psr7ServerRequest as Psr7Request;
use Zend\Psr7Bridge\Psr7Response;
use Zend\Router\RouteMatch;
use Zend\Stratigility\MiddlewarePipe;

class MiddlewareListener extends AbstractListenerAggregate
{
    /**
     * Attach listeners to an event manager
     *
     * @param  EventManagerInterface $events
     * @return void
     */
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $this->listeners[] = $events->attach(MvcEvent::EVENT_DISPATCH, [$this, 'onDispatch'], 1);
    }

    /**
     * Listen to the "dispatch" event
     *
     * @param  MvcEvent $event
     * @return mixed
     */
    public function onDispatch(MvcEvent $event)
    {
        $routeMatch = $event->getRouteMatch();
        $middleware = $routeMatch->getParam('middleware', false);
        if (false === $middleware) {
            return;
        }

        $request        = $event->getRequest();
        $application    = $event->getApplication();
        $response       = $application->getResponse();
        $serviceManager = $application->getServiceManager();

        $psr7ResponsePrototype = Psr7Response::fromZend($response);

        try {
            $pipe = $this->createPipeFromSpec(
                $serviceManager,
                $psr7ResponsePrototype,
                is_array($middleware) ? $middleware : [$middleware]
            );
        } catch (MiddlewareNotCallableException $middlewareNotCallableException) {
            $return = $this->marshalMiddlewareNotCallable(
                $application::ERROR_MIDDLEWARE_CANNOT_DISPATCH,
                $middlewareNotCallableException->toMiddlewareName(),
                $event,
                $application
            );
            $event->setResult($return);
            return $return;
        }

        $caughtException = null;
        try {
            $psr7Request = Psr7Request::fromZend($request)->withAttribute(RouteMatch::class, $routeMatch);
            foreach ($routeMatch->getParams() as $key => $value) {
                $psr7Request = $psr7Request->withAttribute($key, $value);
            }
            $return = $pipe(
                $psr7Request,
                $psr7ResponsePrototype,
                function (PsrServerRequestInterface $request, PsrResponseInterface $response) {
                    throw new ReachedFinalHandlerException(
                        'Reached the final handler for middleware pipe - check the pipe configuration'
                    );
                }
            );
        } catch (\Throwable $ex) {
            $caughtException = $ex;
        } catch (\Exception $ex) {  // @TODO clean up once PHP 7 requirement is enforced
            $caughtException = $ex;
        }

        if ($caughtException !== null) {
            $event->setName(MvcEvent::EVENT_DISPATCH_ERROR);
            $event->setError($application::ERROR_EXCEPTION);
            $event->setParam('exception', $caughtException);

            $events  = $application->getEventManager();
            $results = $events->triggerEvent($event);
            $return  = $results->last();
            if (! $return) {
                $return = $event->getResult();
            }
        }

        if (! $return instanceof PsrResponseInterface) {
            $event->setResult($return);
            return $return;
        }
        $response = Psr7Response::toZend($return);
        $event->setResult($response);
        return $response;
    }

    /**
     * Create a middleware pipe from the array spec given.
     *
     * @param ContainerInterface $serviceLocator
     * @param ResponseInterface $responsePrototype
     * @param array $middlewaresToBePiped
     * @return MiddlewarePipe
     * @throws \InvalidArgumentException
     * @throws \Zend\Mvc\Exception\MiddlewareNotCallableException
     */
    private function createPipeFromSpec(
        ContainerInterface $serviceLocator,
        ResponseInterface $responsePrototype,
        array $middlewaresToBePiped
    ) {
        $pipe = new MiddlewarePipe();
        $pipe->setResponsePrototype($responsePrototype);
        foreach ($middlewaresToBePiped as $middlewareToBePiped) {
            if (null === $middlewareToBePiped) {
                throw new \InvalidArgumentException('Middleware name cannot be null');
            }

            $middlewareName = is_string($middlewareToBePiped) ? $middlewareToBePiped : get_class($middlewareToBePiped);

            if (is_string($middlewareToBePiped) && $serviceLocator->has($middlewareToBePiped)) {
                $middlewareToBePiped = $serviceLocator->get($middlewareToBePiped);
            }
            if (! is_callable($middlewareToBePiped)) {
                throw MiddlewareNotCallableException::fromMiddlewareName($middlewareName);
            }

            $pipe->pipe($middlewareToBePiped);
        }
        return $pipe;
    }

    /**
     * Marshal a middleware not callable exception event
     *
     * @param  string $type
     * @param  string $middlewareName
     * @param  MvcEvent $event
     * @param  Application $application
     * @param  \Exception $exception
     * @return mixed
     */
    protected function marshalMiddlewareNotCallable(
        $type,
        $middlewareName,
        MvcEvent $event,
        Application $application,
        \Exception $exception = null
    ) {
        $event->setName(MvcEvent::EVENT_DISPATCH_ERROR);
        $event->setError($type);
        $event->setController($middlewareName);
        $event->setControllerClass('Middleware not callable: ' . $middlewareName);
        if ($exception !== null) {
            $event->setParam('exception', $exception);
        }

        $events  = $application->getEventManager();
        $results = $events->triggerEvent($event);
        $return  = $results->last();
        if (! $return) {
            $return = $event->getResult();
        }
        return $return;
    }
}
