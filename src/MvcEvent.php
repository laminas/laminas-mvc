<?php

declare(strict_types=1);

namespace Laminas\Mvc;

use Laminas\EventManager\Event;
use Laminas\Router\RouteMatch;
use Laminas\Router\RouteStackInterface;
use Laminas\Stdlib\RequestInterface as Request;
use Laminas\Stdlib\ResponseInterface as Response;
use Laminas\View\Model\ModelInterface as Model;
use Laminas\View\Model\ViewModel;

class MvcEvent extends Event
{
    /**
     * Mvc events triggered by eventmanager
     */
    public const EVENT_BOOTSTRAP      = 'bootstrap';
    public const EVENT_DISPATCH       = 'dispatch';
    public const EVENT_DISPATCH_ERROR = 'dispatch.error';
    public const EVENT_FINISH         = 'finish';
    public const EVENT_RENDER         = 'render';
    public const EVENT_RENDER_ERROR   = 'render.error';
    public const EVENT_ROUTE          = 'route';

    /** @var Application */
    protected $application;

    /** @var null|Request */
    protected $request;

    /** @var null|Response */
    protected $response;

    /** @var mixed */
    protected $result;

    /** @var RouteStackInterface */
    protected $router;

    /** @var null|RouteMatch */
    protected $routeMatch;

    /** @var Model */
    protected $viewModel;

    /**
     * Set application instance
     *
     * @return MvcEvent
     */
    public function setApplication(Application $application)
    {
        $this->setParam('application', $application);
        $this->application = $application;
        return $this;
    }

    /**
     * Get application instance
     *
     * @return Application
     */
    public function getApplication()
    {
        return $this->application;
    }

    /**
     * Get router
     *
     * @return RouteStackInterface
     */
    public function getRouter()
    {
        return $this->router;
    }

    /**
     * Set router
     *
     * @return MvcEvent
     */
    public function setRouter(RouteStackInterface $router)
    {
        $this->setParam('router', $router);
        $this->router = $router;
        return $this;
    }

    /**
     * Get route match
     *
     * @return null|RouteMatch
     */
    public function getRouteMatch()
    {
        return $this->routeMatch;
    }

    /**
     * Set route match
     *
     * @return MvcEvent
     */
    public function setRouteMatch(RouteMatch $matches)
    {
        $this->setParam('route-match', $matches);
        $this->routeMatch = $matches;
        return $this;
    }

    /**
     * Get request
     *
     * @return null|Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Set request
     *
     * @return MvcEvent
     */
    public function setRequest(Request $request)
    {
        $this->setParam('request', $request);
        $this->request = $request;
        return $this;
    }

    /**
     * Get response
     *
     * @return null|Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Set response
     *
     * @return MvcEvent
     */
    public function setResponse(Response $response)
    {
        $this->setParam('response', $response);
        $this->response = $response;
        return $this;
    }

    /**
     * Set the view model
     *
     * @return MvcEvent
     */
    public function setViewModel(Model $viewModel)
    {
        $this->viewModel = $viewModel;
        return $this;
    }

    /**
     * Get the view model
     *
     * @return Model
     */
    public function getViewModel()
    {
        if (null === $this->viewModel) {
            $this->setViewModel(new ViewModel());
        }
        return $this->viewModel;
    }

    /**
     * Get result
     *
     * @return mixed
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Set result
     *
     * @return MvcEvent
     */
    public function setResult(mixed $result)
    {
        $this->setParam('__RESULT__', $result);
        $this->result = $result;
        return $this;
    }

    /**
     * Does the event represent an error response?
     *
     * @return bool
     */
    public function isError()
    {
        return (bool) $this->getParam('error', false);
    }

    /**
     * Set the error message (indicating error in handling request)
     *
     * @param  string $message
     * @return MvcEvent
     */
    public function setError($message)
    {
        $this->setParam('error', $message);
        return $this;
    }

    /**
     * Retrieve the error message, if any
     *
     * @return string
     */
    public function getError()
    {
        return $this->getParam('error', '');
    }

    /**
     * Get the currently registered controller name
     *
     * @return string
     */
    public function getController()
    {
        return $this->getParam('controller');
    }

    /**
     * Set controller name
     *
     * @param  string $name
     * @return MvcEvent
     */
    public function setController($name)
    {
        $this->setParam('controller', $name);
        return $this;
    }

    /**
     * Get controller class
     *
     * @return string
     */
    public function getControllerClass()
    {
        return $this->getParam('controller-class');
    }

    /**
     * Set controller class
     *
     * @param string $class
     * @return MvcEvent
     */
    public function setControllerClass($class)
    {
        $this->setParam('controller-class', $class);
        return $this;
    }
}
