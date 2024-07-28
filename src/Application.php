<?php

declare(strict_types=1);

namespace Laminas\Mvc;

use Laminas\EventManager\EventManagerInterface;
use Laminas\EventManager\EventsCapableInterface;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Stdlib\RequestInterface;
use Laminas\Stdlib\ResponseInterface;

/**
 * Main application class for invoking applications
 *
 * Expects the user will provide a configured ServiceManager, configured with
 * the following services:
 *
 * - EventManager
 * - ModuleManager
 * - Request
 * - Response
 * - RouteListener
 * - Router
 * - DispatchListener
 * - ViewManager
 *
 * The most common workflow is:
 * <code>
 * $services = new Laminas\ServiceManager\ServiceManager($servicesConfig);
 * $app      = new Application($appConfig, $services);
 * $app->bootstrap();
 * $response = $app->run();
 * $response->send();
 * </code>
 *
 * bootstrap() opts in to the default route, dispatch, and view listeners,
 * sets up the MvcEvent, and triggers the bootstrap event. This can be omitted
 * if you wish to setup your own listeners and/or workflow; alternately, you
 * can simply extend the class to override such behavior.
 */
class Application implements EventsCapableInterface
{
    public const ERROR_CONTROLLER_CANNOT_DISPATCH = 'error-controller-cannot-dispatch';
    public const ERROR_CONTROLLER_NOT_FOUND       = 'error-controller-not-found';
    public const ERROR_CONTROLLER_INVALID         = 'error-controller-invalid';
    public const ERROR_EXCEPTION                  = 'error-exception';
    public const ERROR_ROUTER_NO_MATCH            = 'error-router-no-match';

    /**
     * MVC event token
     *
     * @var MvcEvent
     */
    protected $event;

    protected EventManagerInterface $events;

    /** @var RequestInterface */
    protected $request;

    /** @var ResponseInterface */
    protected $response;

    public function __construct(
        protected ServiceManager $serviceManager,
        EventManagerInterface $events,
        ApplicationListenerProvider $listenerProvider,
        ?RequestInterface $request = null,
        ?ResponseInterface $response = null
    ) {
        $this->setEventManager($events);
        $listenerProvider->registerListeners($this);
        $this->request  = $request ?: $serviceManager->get('Request');
        $this->response = $response ?: $serviceManager->get('Response');
    }

    /**
     * Retrieve the application configuration
     *
     * @return array|object
     */
    public function getConfig()
    {
        return $this->serviceManager->get('config');
    }

    /**
     * Bootstrap the application
     *
     * Defines and binds the MvcEvent, and passes it the request, response, and
     * router. Attaches the ViewManager as a listener. Triggers the bootstrap
     * event.
     *
     * @return Application
     */
    public function bootstrap()
    {
        $serviceManager = $this->serviceManager;
        $events         = $this->events;

        // Setup MVC Event
        $this->event = $event  = new MvcEvent();
        $event->setName(MvcEvent::EVENT_BOOTSTRAP);
        $event->setTarget($this);
        $event->setApplication($this);
        $event->setRequest($this->request);
        $event->setResponse($this->response);
        $event->setRouter($serviceManager->get('Router'));

        // Trigger bootstrap events
        $events->triggerEvent($event);

        return $this;
    }

    /**
     * Retrieve the service manager
     *
     * @return ServiceManager
     */
    public function getServiceManager()
    {
        return $this->serviceManager;
    }

    /**
     * Get the request object
     *
     * @return RequestInterface
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Get the response object
     *
     * @return ResponseInterface
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Get the MVC event instance
     *
     * @return MvcEvent
     */
    public function getMvcEvent()
    {
        return $this->event;
    }

    /**
     * Set the event manager instance
     */
    protected function setEventManager(EventManagerInterface $eventManager): void
    {
        $eventManager->setIdentifiers([
            self::class,
            static::class,
        ]);
        $this->events = $eventManager;
    }

    public function getEventManager(): EventManagerInterface
    {
        return $this->events;
    }

    /**
     * Run the application
     *
     * @triggers route(MvcEvent)
     *           Routes the request, and sets the RouteMatch object in the event.
     * @triggers dispatch(MvcEvent)
     *           Dispatches a request, using the discovered RouteMatch and
     *           provided request.
     * @triggers dispatch.error(MvcEvent)
     *           On errors (controller not found, action not supported, etc.),
     *           populates the event with information about the error type,
     *           discovered controller, and controller class (if known).
     *           Typically, a handler should return a populated Response object
     *           that can be returned immediately.
     * @return self
     */
    public function run()
    {
        $events = $this->events;
        $event  = $this->event;

        // Define callback used to determine whether or not to short-circuit
        $shortCircuit = static function ($r) use ($event): bool {
            if ($r instanceof ResponseInterface) {
                return true;
            }
            if ($event->getError()) {
                return true;
            }
            return false;
        };

        // Trigger route event
        $event->setName(MvcEvent::EVENT_ROUTE);
        $event->stopPropagation(false); // Clear before triggering
        $result = $events->triggerEventUntil($shortCircuit, $event);
        if ($result->stopped()) {
            $response = $result->last();
            if ($response instanceof ResponseInterface) {
                $event->setName(MvcEvent::EVENT_FINISH);
                $event->setTarget($this);
                $event->setResponse($response);
                $event->stopPropagation(false); // Clear before triggering
                $events->triggerEvent($event);
                $this->response = $response;
                return $this;
            }
        }

        if ($event->getError()) {
            return $this->completeRequest($event);
        }

        // Trigger dispatch event
        $event->setName(MvcEvent::EVENT_DISPATCH);
        $event->stopPropagation(false); // Clear before triggering
        $result = $events->triggerEventUntil($shortCircuit, $event);

        // Complete response
        $response = $result->last();
        if ($response instanceof ResponseInterface) {
            $event->setName(MvcEvent::EVENT_FINISH);
            $event->setTarget($this);
            $event->setResponse($response);
            $event->stopPropagation(false); // Clear before triggering
            $events->triggerEvent($event);
            $this->response = $response;
            return $this;
        }

        $response = $this->response;
        $event->setResponse($response);
        return $this->completeRequest($event);
    }

    /**
     * Complete the request
     *
     * Triggers "render" and "finish" events, and returns response from
     * event object.
     *
     * @return Application
     */
    protected function completeRequest(MvcEvent $event)
    {
        $events = $this->events;
        $event->setTarget($this);

        $event->setName(MvcEvent::EVENT_RENDER);
        $event->stopPropagation(false); // Clear before triggering
        $events->triggerEvent($event);

        $event->setName(MvcEvent::EVENT_FINISH);
        $event->stopPropagation(false); // Clear before triggering
        $events->triggerEvent($event);

        return $this;
    }
}
