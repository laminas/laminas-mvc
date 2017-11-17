# Available Controllers

Controllers in zend-mvc are objects implementing `Zend\Stdlib\DispatchableInterface`.
That interface describes a single method:

```php
use Zend\Stdlib\DispatchableInterface;
use Zend\Stdlib\RequestInterface as Request;
use Zend\Stdlib\ResponseInterface as Response;

class Foo implements DispatchableInterface
{
    public function dispatch(Request $request, Response $response = null)
    {
        // ... do something, and preferably return a Response ...
    }
}
```

While the pattern is straight-forward, chances are you don't want to implement
custom dispatch logic for every controller, particularly as it's not unusual or
uncommon for a single controller to handle several related types of requests.

To provide convenience, zend-mvc also defines several interfaces that, when
implemented, can provide controllers with additional capabilities.

## Common Interfaces Used With Controllers

### InjectApplicationEvent

The `Zend\Mvc\InjectApplicationEventInterface` hints to the `Application`
instance that it should inject its `MvcEvent` into the controller itself. Why
would this be useful?

Recall that the `MvcEvent` composes a number of objects: the `Request` and
`Response`, naturally, but also the router, the route matches (a `RouteMatch`
instance), and potentially the "result" of dispatching.

A controller that has the `MvcEvent` injected, then, can retrieve or inject
these. As an example:

```php
$matches = $this->getEvent()->getRouteMatch();
$id      = $matches->getParam('id', false);
if (! $id) {
    $response = $this->getResponse();
    $response->setStatusCode(500);
    $this->getEvent()->setResult('Invalid identifier; cannot complete request');
    return;
}
```

The `InjectApplicationEventInterface` defines two methods:

```php
public function setEvent(Zend\EventManager\EventInterface $event);
public function getEvent();
```

### ServiceLocatorAware

In most cases, you should define your controllers such that dependencies are
injected by the application's `ServiceManager`, via either constructor arguments
or setter methods.

However, occasionally you may have objects you wish to use in your controller
that are only valid for certain code paths. Examples include forms, paginators,
navigation, etc. In these cases, you may decide that it doesn't make sense to
inject those objects every time the controller is used.

The `ServiceLocatorAwareInterface` interface hints to the `ServiceManager` that it should inject
itself into the controller. It defines two simple methods:

```php
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

public function setServiceLocator(ServiceLocatorInterface $serviceLocator);
public function getServiceLocator();
```

> #### ServiceLocatorInterface is deprecated
>
> `ServiceLocatorAwareInterface` [was removed from zend-servicemanager v3.0](http://docs.zendframework.com/zend-servicemanager/migration/#miscellaneous-interfaces-traits-and-classes),
> and, as such, starting in zend-mvc 2.7.0, the `AbstractController`
> implementation no longer implements the interface, though it implements the
> methods the interface defines; this allows forwards compatibility with
> zend-servicemanager v3.
>
> However, also starting with the zend-mvc 2.7.0 release, `ServiceLocatorAwareInterface`
> usage is deprecated. We recommend injecting dependencies explicitly instead of
> pulling them from a composed `ServiceManager` instance.
>
> In cases where an object will not be used in all code paths, we recommend
> splitting into discrete controllers, or using [lazy services](http://docs.zendframework.com/zend-servicemanager/lazy-services/).

### EventManagerAware

Typically, it's nice to be able to tie into a controller's workflow without
needing to extend it or hardcode behavior into it. The solution for this is to
use the `EventManager`.

You can hint to the `ServiceManager` that you want an `EventManager` injected by
implementing the interface `EventManagerAwareInterface`, which tells the
`ServiceManager` to inject an `EventManager`.

To do this, you define two methods. The first, a setter, should also set any
`SharedEventManager` identifiers you want to listen on, and the second, a getter,
should return the composed `EventManager` instance.

```php
use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerInterface;

public function setEventManager(EventManagerInterface $events);
public function getEventManager();
```

### Controller Plugins

Code re-use is a common goal for developers. Another common goal is convenience.
However, this is often difficult to achieve cleanly in abstract, general
systems.

Within your controllers, you'll often find yourself repeating tasks from one
controller to another.  Some common examples:

- Generating URLs.
- Redirecting.
- Setting and retrieving flash messages (self-expiring session messages).
- Invoking and dispatching additional controllers.

To facilitate these actions while also making them available to alternate
controller implementations, we've created a `PluginManager` implementation for
the controller layer, `Zend\Mvc\Controller\PluginManager`, building on the
`Zend\ServiceManager\AbstractPluginManager` functionality. To utilize it,
implement the `setPluginManager(PluginManager $plugins)` method, and set up your
code to use the controller-specific implementation by default:

```php
use Zend\Mvc\Controller\PluginManager;

public function setPluginManager(PluginManager $plugins)
{
    $this->plugins = $plugins;
    $this->plugins->setController($this);

    return $this;
}

public function getPluginManager()
{
    if (!$this->plugins) {
        $this->setPluginManager(new PluginManager());
    }

    return $this->plugins;
}

public function plugin($name, array $options = null)
{
    return $this->getPluginManager()->get($name, $options);
}
```

## AbstractActionController

Implementing each of the above interfaces is a lesson in redundancy; you won't
often want to do it.  As such, we've developed abstract, base controllers you
can extend to get started.

The first is `Zend\Mvc\Controller\AbstractActionController`. This controller
implements each of the above interfaces, and uses the following assumptions:

- An "action" parameter is expected in the `RouteMatch` object composed in the
  attached `MvcEvent`.  If none is found, a `notFoundAction()` is invoked.
- The "action" parameter is converted to a camelCased format and appended with
  the word "Action" to create a method name. As examples: "foo" maps to
  `fooAction`, "foo-bar" or "foo.bar" or "foo\_bar" to `fooBarAction`. The
  controller then checks to see if that method exists. If not, the
  `notFoundAction()` method is invoked; otherwise, the discovered method is
  called.
- The results of executing the given action method are injected into the
  `MvcEvent`'s "result" property (via `setResult()`, and accessible via
  `getResult()`).

Essentially, a route mapping to an `AbstractActionController` needs to return
both the "controller" and "action" keys in its matches.

Creation of action controllers looks like the following example:

```php
namespace Foo\Controller;

use Zend\Mvc\Controller\AbstractActionController;

class BarController extends AbstractActionController
{
    public function bazAction()
    {
        return ['title' => __METHOD__];
    }

    public function batAction()
    {
        return ['title' => __METHOD__];
    }
}
```

### Interfaces and Collaborators

`AbstractActionController` implements each of the following interfaces:

- `Zend\Stdlib\DispatchableInterface`
- `Zend\Mvc\InjectApplicationEventInterface`
- `Zend\ServiceManager\ServiceLocatorAwareInterface` (starting with zend-mvc
  2.7.0, only the methods defined by the interface, not the interface itself)
- `Zend\EventManager\EventManagerAwareInterface`

The composed `EventManager` will be configured to listen on the following contexts:

- `Zend\Stdlib\DispatchableInterface`
- `Zend\Mvc\Controller\AbstractActionController`
- `Zend\Mvc\Controller\AbstractController`

Additionally, if you extend the class, it will listen on the name of the
extending class.

## AbstractRestfulController

`Zend\Mvc\Controller\AbstractRestfulController` provides a native RESTful
implementation that maps HTTP request methods to controller methods, using the
following matrix:

- **GET** maps to either `get()` or `getList()`, depending on whether or not an
  "id" parameter is found in the route matches. If one is, it is passed as an
  argument to `get()`; if not, `getList()` is invoked. In the former case, you
  should provide a representation of the given entity with that identification;
  in the latter, you should provide a list of entities.
- **POST** maps to `create()`. That method expects a `$data` argument, usually
  the `$_POST` superglobal array. The data should be used to create a new
  entity, and the response should typically be an HTTP 201 response with the
  Location header indicating the URI of the newly created entity and the
  response body providing the representation.
- **PUT** maps to `update()`, and requires that an "id" parameter exists in the
  route matches; that value is passed as an argument to the method. It should
  attempt to update the given entity, and, if successful, return either a 200 or
  202 response status, as well as the representation of the entity.
- **DELETE** maps to `delete()`, and requires that an "id" parameter exists in
  the route matches; that value is passed as an argument to the method. It
  should attempt to delete the given entity, and, if successful, return either a
  200 or 204 response status.

Additionally, you can map "action" methods to the `AbstractRestfulController`,
just as you would in the `AbstractActionController`; these methods will be
suffixed with "Action", differentiating them from the RESTful methods listed
above. This allows you to perform such actions as providing forms used to submit
to the various RESTful methods, or to add RPC methods to your RESTful API.

### Interfaces and Collaborators

`AbstractRestfulController` implements each of the following interfaces:

- `Zend\Stdlib\DispatchableInterface`
- `Zend\Mvc\InjectApplicationEventInterface`
- `Zend\ServiceManager\ServiceLocatorAwareInterface` (starting with zend-mvc
  2.7.0, only the methods defined by the interface, not the interface itself)
- `Zend\EventManager\EventManagerAwareInterface`

The composed `EventManager` will be configured to listen on the following contexts:

- `Zend\Stdlib\DispatchableInterface`
- `Zend\Mvc\Controller\AbstractRestfulController`
- `Zend\Mvc\Controller\AbstractController`

Additionally, if you extend the class, it will listen on the name of the
extending class.

## AbstractConsoleController

> For version 3, the integration component [zend-mvc-console](https://docs.zendframework.com/zend-mvc-console/) must be installed. It can be done via Composer:
> ````bash
> composer require zendframework/zend-mvc-console
> ```
> If you are not using the component installer, you will need to [add this component as a module](https://docs.zendframework.com/zend-mvc-console/intro/#manual-installation).

`Zend\Mvc\Console\Controller\AbstractConsoleController` extends from [AbstractActionController](#abstractactioncontroller)
and provides the following functionality:

- The method `setConsole(Zend\Console\Adapter\AdapterInterface $console)` allows
  injecting a console adapter representing the current console environment. By
  default, the `ControllerManager` will inject this for you as part of
  controller instantiation.
- The method `getConsole()` allows you to retrieve the current console adapter
  instance, allowing you to retrieve console capabilities and generate console
  output.
- The `dispatch()` method will throw an exception if invoked in a non-console
  environment, ensuring that you do not need to do any checks within your action
  methods for the environment.

### Interfaces and Collaborators

`AbstractRestfulController` implements each of the following interfaces:

- `Zend\Stdlib\DispatchableInterface`
- `Zend\Mvc\InjectApplicationEventInterface`
- `Zend\ServiceManager\ServiceLocatorAwareInterface` (starting with zend-mvc
  2.7.0, only the methods defined by the interface, not the interface itself)
- `Zend\EventManager\EventManagerAwareInterface`

The composed `EventManager` will be configured to listen on the following contexts:

- `Zend\Stdlib\DispatchableInterface`
- `Zend\Mvc\Console\Controller\AbstractConsoleController`
- `Zend\Mvc\Controller\AbstractActionController`
- `Zend\Mvc\Controller\AbstractController`

Additionally, if you extend the class, it will listen on the name of the
extending class.
