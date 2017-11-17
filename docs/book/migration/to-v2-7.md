## Upgrading to 2.7

## Middleware

zend-mvc now registers `Zend\Mvc\MiddlewareListener` as a dispatch listener at
a priority higher than `Zend\Mvc\DispatchListener`, allowing dispatch of
[PSR-7](http://www.php-fig.org/psr/psr-7/) middleware. Read the
[middleware chapter](../middleware.md) for details on how to use this new feature.

## Application

The constructor signature of `Zend\Mvc\Application` has changed. Previously, it
was:

```php
__construct($configuration, ServiceManager $serviceManager)
```

and internally, it pulled the services `EventManager`, `Request`, and `Response`
from the provided `$serviceManager` during initialization.

The new constructor signature provides optional arguments for injecting the
event manager, request, and response:

```php
__construct(
    $configuration,
    ServiceManager $serviceManager,
    EventManager $events = null,
    RequestInterface $request = null,
    ResponseInterface $response = null
)
```

This change makes all dependencies explicit. Starting in v3.0, the new arguments
will be *required*.

The factory `Zend\Mvc\Service\ApplicationFactory` was updated to follow the new
signature.

This change should only affect users who are manually instantiating the
`Application` instance.

## EventManagerAware initializers

zend-mvc provides two mechanisms for injecting event managers into
`EventManagerAware` objects. One is the "EventManagerAwareInitializer"
registered in `Zend\Mvc\Service\ServiceManagerConfig`, and the other is the
`Zend\Mvc\Controller\ControllerManager::injectEventManager()` initializer. In
both cases, the logic was updated to be forwards compatible with
zend-eventmanager v3.

Previously each would check if the instance's `getEventManager()` method
returned an event manager instance, and, if so, inject the shared event manager:

```php
$events = $instance->getEventManager();
if ($events instanceof EventManagerInterface) {
    $events->setSharedManager($container->get('SharedEventManager'));
}
```

In zend-eventmanager v3, event managers are now injected with the shared
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

## ServiceLocatorAware initializers

zend-servicemanager v3.0 removes `Zend\ServiceManager\ServiceLocatorAwareInterface`.
Since zend-mvc provides initializers around that interface, they needed updates
to allow both forwards compatibility with zend-servicemanager v3 as well as
backwards compatibility with existing applications.

This was accomplished in two ways:

- The abstract controller implementations no longer implement
  `ServiceLocatorAwareInterface`, but continue to define the methods that the
  interface defines (namely `setServiceLocator()` and `getServiceLocator()`.
- The initializers registered by `Zend\Mvc\Service\ServiceManagerConfig` and
  `Zend\Mvc\Controller\ControllerManager` now use duck-typing to determine if
  an instance requires container injection; if so it will do so.

However, we also maintain that service locator injection is an anti-pattern;
dependencies should be injected directly into instances instead. As such,
starting in 2.7.0, we now emit a deprecation notice any time an instance is
injected by one of these initializers, and we plan to remove the initializers
for version 3.0. The deprecation notice includes the name of the class, to help
you identify what instances you will need to update before the zend-mvc v3
release.

To prepare your code, you will need to do the following within your controller:

- Find all cases where you call `getServiceLocator()`, and identify the services
  they retrieve.
- Update your controller to accept these services via the constructor.
- If you have not already, create a factory class for your controller.
- In the factory, pull the appropriate services and pass them to the
  controller's constructor.

As an example, consider the following code from a controller:

```php
$db = $this->getServiceLcoator()->get('Db\ApplicationAdapter');
```

To update your controller, you will:

- Add a `$db` property to your class.
- Update the constructor to accept the database adapter and assign it to the
  `$db` property.
- Change the above line to either read `$db = $this->db;` *or just use the
  property directly*.
- Add a factory that pulls the service and pushes it into the controller.

The controller then might look like the following:

```php
use Zend\Db\Adapter\AdapterInterface;
use Zend\Mvc\Controller\AbstractActionController;

class YourController extends AbstractActionController
{
    private $db;

    public function __construct(AdapterInterface $db)
    {
        $this->db = $db;
    }

    public function someAction()
    {
        $results = $this->db->query(/* ... */);
        /* ... */
    }
}
```

A factory would look like the following:

```php
use Interop\Container\ContainerInterface;

class YourControllerFactory
{
    public function __invoke(ContainerInterface $container)
    {
        return new YourController($container->get('Db\ApplicationAdapter'));
    }
}
```

You then also need to ensure the controller manager knows about the factory. It
likely already does, as an invokable; you will redefine it as a factory in
your `module.config.php`:

```php
return [
    'controllers' => [
        'factories' => [
            YourController::class => YourControllerFactory::class,
            /* ... */
        ],
        /* ... */
    ],
    /* ... */
];
```

While this may seem like more steps, doing so ensures your code has no hidden
dependencies, improves the testability of your code, and allows you to substitute
alternatives for either the dependencies or the controller itself.

#### Optional dependencies

In some cases, you may have dependencies that are only required for some
execution paths, such as forms, database adapters, etc. In these cases, you have
two approaches you can use:

- Split your controller into separate responsibilities, and use the more
  specific controllers. This way you don't need to inject dependencies that are
  only used in some actions. (We recommend doing this regardless, as it helps
  keep your code more maintainable.)
- Use [lazy services](http://docs.zendframework.com/zend-servicemanager/lazy-services/).
  When you configure these, zend-servicemanager gives you a proxy instance that,
  on first access, loads the full service. This allows you to delay the most
  expensive operations until absolutely needed.
