<?php

declare(strict_types=1);

namespace Laminas\Mvc;

use Closure;
use Laminas\EventManager\EventManagerInterface;
use Laminas\EventManager\EventsCapableInterface;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Stdlib\RequestInterface;
use Laminas\Stdlib\ResponseInterface;
use Psr\Container\ContainerInterface;

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

    /**
     * @param Closure():Request $requestFactory
     * @param Closure():Response $responseFactory
     */
    public function __construct(
        protected ContainerInterface $container,
        EventManagerInterface $events,
        ApplicationListenersProvider $listenersProvider,
        protected Closure $requestFactory,
        protected Closure $responseFactory,
    ) {
        $this->setEventManager($events);
        $listenersProvider->registerListeners($this);
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
        $container = $this->container;
        $events    = $this->events;

        // Setup MVC Event
        $this->event = $event  = new MvcEvent();
        $event->setName(MvcEvent::EVENT_BOOTSTRAP);
        $event->setTarget($this);
        $event->setApplication($this);
        $event->setRouter($container->get('Router'));

        // Trigger bootstrap events
        $events->triggerEvent($event);

        return $this;
    }

    /**
     * Retrieve the service manager
     *
     * @deprecated Since 4.0.0 and will be removed in 5.0.0
     */
    public function getServiceManager(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * Get the request object
     *
     * @deprecated Since 4.0.0 and will be removed in 5.0.0
     *
     * @return null|RequestInterface
     */
    public function getRequest()
    {
        return $this->event->getRequest();
    }

    /**
     * Get the response object
     *
     * @deprecated Since 4.0.0 and will be removed in 5.0.0
     *
     * @return null|ResponseInterface
     */
    public function getResponse()
    {
        return $this->event->getResponse();
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

        $event->setRequest(($this->requestFactory)());
        $event->setResponse(($this->responseFactory)());

        // Trigger prepare event
        $event->setName(MvcEvent::EVENT_PREPARE);
        $event->stopPropagation(false);
        $event->setTarget($this);
        $events->triggerEvent($event);

        // Define callback used to determine whether to short-circuit
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
            return $this;
        }

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
