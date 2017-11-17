# Examples

## Controllers

### Accessing the Request and Response

When using one of the abstract controller implementations, the request and
response object are composed directly into the controller as soon as
`dispatch()` is called. You may access them as follows:

```php
// Using explicit accessor methods
$request  = $this->getRequest();
$response = $this->getResponse();

// Using direct property access
$request  = $this->request;
$response = $this->response;
```

Additionally, if your controller implements `InjectApplicationEventInterface`
(as all shipped abstract controllers do), you can access these objects from the
attached `MvcEvent`:

```php
$event    = $this->getEvent();
$request  = $event->getRequest();
$response = $event->getResponse();
```

The above can be useful when composing event listeners into your controller.

### Accessing routing parameters

The parameters returned when routing completes are wrapped in a
`Zend\Router\RouteMatch` object.  This object is detailed in the section on
[Routing](routing.md).

Within your controller, if you implement `InjectApplicationEventInterface` (as
all shipped abstract controllers do), you can access this object from the
attached `MvcEvent`:

```php
$event   = $this->getEvent();
$matches = $event->getRouteMatch();
```

Once you have the `RouteMatch` object, you can pull parameters from it.

The same can be done using the [Params plugin](plugins.md#params-plugin).

### Returning early

You can short-circuit execution of the application at any point by returning a
`Response` from your controller or any event. When such a value is discovered,
it halts further execution of the event manager, bubbling up to the
`Application` instance, where it is immediately returned.

As an example, the `Redirect` plugin returns a `Response`, which can be returned
immediately so as to complete the request as quickly as possible. Other use
cases might be for returning JSON or XML results from web service endpoints,
returning "401 Unauthorized" results, etc.

## Bootstrapping

### Registering module-specific listeners

You may want module-specific listeners; these allow you to introduce
authorization, logging, caching, or other concerns into your application.

Each `Module` class can have an optional `onBootstrap()` method. This method is
used for module-specific configuration, and is the ideal location to setup event
listeners for you module. The `onBootstrap()` method is called for **every**
module on **every** page request and should **only** be used for performing
**lightweight** tasks such as registering event listeners.

The base `Application` class shipped with the framework has an `EventManager`
associated with it, and once the modules are initialized, it triggers the
[bootstrap](mvc-event.md#mvceventevent_bootstrap-bootstrap) event, which
provides a `getApplication()` method on the event.

One way to accomplish module-specific listeners is to listen to that event, and
register listeners at that time. As an example:

```php
namespace SomeCustomModule;

class Module
{
    /**
     * @param  \Zend\Mvc\MvcEvent $e The MvcEvent instance
     * @return void
     */
    public function onBootstrap($e)
    {
        $application = $e->getApplication();
        $config      = $application->getConfig();
        $view        = $application->getServiceManager()->get('ViewHelperManager');
        // You must have these keys in you application config
        $view->headTitle($config['view']['base_title']);

        // This is your custom listener
        $listener   = new Listeners\ViewListener();
        $listener->setView($view);
        $listener->attach($application->getEventManager());
    }
}
```

The above demonstrates several things. First, it demonstrates a listener on the
application's [bootstrap](mvc-event.md#mvceventevent_bootstrap-bootstrap) event
(the `onBootstrap()` method). Second, it demonstrates that listener, and how it
can be used to register listeners with the application. It grabs the
`Application` instance; from the `Application`, it is able to grab the attached
service manager and configuration. These are then used to retrieve the view,
configure some helpers, and then register a listener aggregate with the
application event manager.
