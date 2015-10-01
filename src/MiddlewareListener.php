<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Mvc;

use ArrayObject;
use Zend\EventManager\AbstractListenerAggregate;
use Zend\EventManager\EventManagerInterface;
use Zend\Mvc\Exception\InvalidControllerException;
use Zend\Stdlib\ArrayUtils;
use Zend\Psr7Bridge\Psr7ServerRequest as Psr7Request;
use Zend\Psr7Bridge\Psr7Response;

class MiddlewareListener extends AbstractListenerAggregate
{
    /**
     * Attach listeners to an event manager
     *
     * @param  EventManagerInterface $events
     * @return void
     */
    public function attach(EventManagerInterface $events)
    {
        $this->listeners[] = $events->attach(MvcEvent::EVENT_DISPATCH, [$this, 'onDispatch'], 1000);
    }

    /**
     * Listen to the "dispatch" event
     *
     * @param  MvcEvent $e
     * @return mixed
     */
    public function onDispatch(MvcEvent $e)
    {
        $routeMatch = $e->getRouteMatch();
        $middleware = $routeMatch->getParam('middleware', false);
        if (false === $middleware) {
            return;
        }

        $request        = $e->getRequest();
        $application    = $e->getApplication();
        $response       = $application->getResponse();
        $serviceManager = $application->getServiceManager();
        $middlewareName = is_string($middleware) ? $middleware : get_class($middleware);

        if (is_string($middleware) && $serviceManager->has($middleware)) {
            $middleware = $serviceManager->get($middleware);
        }
        if (!is_callable($middleware)) {
            $return = $this->marshalMiddlewareNotCallable($application::ERROR_MIDDLEWARE_CANNOT_DISPATCH, $middlewareName, $e, $application);
            $e->setResult($return);
            return $return;
        }
        try {
            $return = $middleware(Psr7Request::fromZend($request), Psr7Response::fromZend($response));
        } catch (\Exception $ex) {
          $e->setError($application::ERROR_EXCEPTION)
            ->setController($middlewareName)
            ->setControllerClass(get_class($middleware))
            ->setParam('exception', $ex);
          $results = $events->trigger(MvcEvent::EVENT_DISPATCH_ERROR, $e);
          $return = $results->last();
          if (! $return) {
              $return = $e->getResult();
          }
        }

        if (! $return instanceof \Psr\Http\Message\ResponseInterface) {
            $e->setResult($return);
            return $return;
        }
        $response = Psr7Response::toZend($return);
        $e->setResult($response);
        return $response;
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
        $event->setError($type)
              ->setController($middlewareName)
              ->setControllerClass('Middleware not callable: ' . $middlewareName);
        if ($exception !== null) {
            $event->setParam('exception', $exception);
        }

        $events  = $application->getEventManager();
        $results = $events->trigger(MvcEvent::EVENT_DISPATCH_ERROR, $event);
        $return  = $results->last();
        if (! $return) {
            $return = $event->getResult();
        }
        return $return;
    }
}
