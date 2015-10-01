# Migration Guide

This is a guide for migration from version 2 to version 3 of zend-mvc.

## Application

The constructor signature of `Zend\Mvc\Application` has changed. Previously, it
was:

```php
__construct($configuration, ServiceManager $serviceManager)
```

and internally, it pulled the services `EventManager`, `Request`, and `Response`
from the provided `$serviceManager` during initialization.

The new constructor signature is:

```php
__construct(
    $configuration,
    ServiceManager $serviceManager,
    EventManager $events,
    RequestInterface $request,
    ResponseInterface $response
)
```

making all dependencies explicit. The factory
`Zend\Mvc\Service\ApplicationFactory` was updated to follow the new signature.

This change should only affect users who are manually instantiating the
`Application` instance.

## EventManager initializer and ControllerManager event manager injection

zend-mvc provides two mechanisms for injecting event managers into
`EventManagerAware` objects. One is the "EventManagerAwareInitializer"
registered in `Zend\Mvc\Service\ServiceManagerConfig`, and the other is internal
logic in `Zend\Mvc\Controller\ControllerManager`. In both cases, the logic was
updated due to changes in the v3 version of zend-eventmanager. 

Previously each would check if the instance's `getEventManager()` method
returned an event manager instance, and, if so, inject the shared event manager:

```php
$events = $instance->getEventManager();
if ($events instanceof EventManagerInterface) {
    $events->setSharedManager($container->get('SharedEventManager'));
}
```

In zend-eventmanager v3, event manager's are now injected with the shared
manager at instantiation, and no setter exists for providing the shared manager.
As such, the above logic changed to:

```php
$events = $instance->getEventManager();
if (! $events || ! $events->getSharedManager()) {
    $instance->setEventManager($container->get('EventManager'));
}
```

In other words, it re-injects with a new event manager instance if the instance
pulled does not have a shared manager composed.

This likely will not cause regressions in existing code, but may be something to
be aware of if you were previously depending on lazy-loaded event manager
state.
