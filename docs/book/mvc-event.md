# The MvcEvent

zend-mvc defines and utilizes a custom `Zend\EventManager\Event` implementation,
`Zend\Mvc\MvcEvent`. This event is created during `Zend\Mvc\Application::bootstrap()`
and is passed when triggering all application events.  Additionally, if your
controllers implement the `Zend\Mvc\InjectApplicationEventInterface`, `MvcEvent`
will be injected into those controllers.

The `MvcEvent` adds accessors and mutators for the following:

- `Application` object.
- `Request` object.
- `Response` object.
- `Router` object.
- `RouteMatch` object.
- Result - usually the result of dispatching a controller.
- `ViewModel` object, typically representing the layout view model.

The methods it defines are:

- `setApplication($application)`
- `getApplication()`
- `setRequest($request)`
- `getRequest()`
- `setResponse($response)`
- `getResponse()`
- `setRouter($router)`
- `getRouter()`
- `setRouteMatch($routeMatch)`
- `getRouteMatch()`
- `setResult($result)`
- `getResult()`
- `setViewModel($viewModel)`
- `getViewModel()`
- `isError()`
- `setError()`
- `getError()`
- `getController()`
- `setController($name)`
- `getControllerClass()`
- `setControllerClass($class)`

The `Application`, `Request`, `Response`, `Router`, and `ViewModel` are all
injected during the `bootstrap` event. Following the `route` event, it will be
injected also with the `RouteMatch` object encapsulating the results of routing.

Since this object is passed around throughout the MVC, it is a common location
for retrieving the results of routing, the router, and the request and response
objects. Additionally, we encourage setting the results of execution in the
event, to allow event listeners to introspect them and utilize them within their
execution. As an example, the results could be passed into a view renderer.

## Order of events

The following events are triggered, in the following order:

Name             | Constant                         | Description
-----------------|----------------------------------|------------
`bootstrap`      | `MvcEvent::EVENT_BOOTSTRAP`      | Bootstrap the application by creating the ViewManager.
`route`          | `MvcEvent::EVENT_ROUTE`          | Perform routing (or route-related actions).
`dispatch`       | `MvcEvent::EVENT_DISPATCH`       | Dispatch the matched route to a controller/action.
`dispatch.error` | `MvcEvent::EVENT_DISPATCH_ERROR` | Event triggered in case of a problem during dispatch process (e.g., unknown controller).
`render`         | `MvcEvent::EVENT_RENDER`         | Prepare the data and delegate the rendering to the view layer.
`render.error`   | `MvcEvent::EVENT_RENDER_ERROR`   | Event triggered in case of a problem during the render process (e.g., no renderer found).
`finish`         | `MvcEvent::EVENT_FINISH`         | Perform tasks once everything else is done.

The following sections provide more detail on each event.

## `MvcEvent::EVENT_BOOTSTRAP` ("bootstrap")

### Listeners

The following classes listen to this event (sorted from higher priority to lower
priority):

Class                            | Priority | Method Called | Triggers | Description
---------------------------------|---------:|---------------|----------|------------
`Zend\Mvc\View\Http\ViewManager` | 10000    | `onBootstrap` | none     | Prepares the view layer (instantiate a `Zend\Mvc\View\Http\ViewManager`).

### Triggered By

This event is triggered by the following classes:

Class                  | In Method
-----------------------|----------
`Zend\Mvc\Application` | `bootstrap`

## `MvcEvent::EVENT_ROUTE` ("route")

### Listeners

The following classes listen to this event (sorted from higher priority to lower
priority):

Class                          | Priority | Method Called | Triggers | Description
-------------------------------|---------:|---------------|----------|------------
`Zend\Mvc\ModuleRouteListener` | 1        | `onRoute`     | none     | Determines if the module namespace should be prepended to the controller name. This is the case if the route match contains a parameter key matching the `MODULE_NAMESPACE` constant.
`Zend\Mvc\RouteListener`       | 1        | `onRoute`     | `MvcEvent::EVENT_DISPATCH_ERROR` (if no route is matched) | Tries to match the request to the router and return a `RouteMatch` object.

### Triggered By

This event is triggered by the following classes:

Class                  | In Method | Description
-----------------------|-----------|------------
`Zend\Mvc\Application` | `run`     | Uses a short circuit callback that allows halting propagation of the event if an error is raised during routing.

## `MvcEvent::EVENT_DISPATCH` ("dispatch")

### Listeners

The following classes listen to this event (sorted from higher priority to lower
priority):

#### Console context only

The following listeners are only attached in a console context:

Class                                                    | Priority | Method Called               | Description
---------------------------------------------------------|---------:|-----------------------------|------------
`Zend\Mvc\View\Console\InjectNamedConsoleParamsListener` | 1000     | `injectNamedParams`         | Merge all params (route match params and params in the command), and add them to the `Request` object.
`Zend\Mvc\View\Console\CreateViewModelListener`          | -80      | `createViewModelFromArray`  | If the controller action returns an associative array, this listener casts it to a `ConsoleModel` object.
`Zend\Mvc\View\Console\CreateViewModelListener`          | -80      | `createViewModelFromString` | If the controller action returns a string, this listener casts it to a `ConsoleModel` object.
`Zend\Mvc\View\Console\CreateViewModelListener`          | -80      | `createViewModelFromNull`   | If the controller action returns null, this listener casts it to a `ConsoleModel` object.
`Zend\Mvc\View\Console\InjectViewModelListener`          | -100     | `injectViewModel`           | Inserts the `ViewModel` (in this case, a `ConsoleModel`) and adds it to the MvcEvent object. It either (a) adds it as a child to the default, composed view model, or (b) replaces it if the result is marked as terminal.

#### HTTP context only

The following listeners are only attached in an HTTP context:

Class                                        | Priority | Method Called              | Description
---------------------------------------------|---------:|----------------------------|------------
`Zend\Mvc\View\Http\CreateViewModelListener` | -80      | `createViewModelFromArray` | If the controller action returns an associative array, this listener casts it to a `ViewModel` object.
`Zend\Mvc\View\Http\CreateViewModelListener` | -80      | `createViewModelFromNull`  | If the controller action returns null, this listener casts it to a `ViewModel` object.
`Zend\Mvc\View\Http\RouteNotFoundStrategy`   | -90      | `prepareNotFoundViewModel` | Creates and return a 404 `ViewModel`.
`Zend\Mvc\View\Http\InjectTemplateListener`  | -90      | `injectTemplate`           | Injects a template into the view model, if none present. Template name is derived from the controller found in the route match, and, optionally, the action, if present.
`Zend\Mvc\View\Http\InjectViewModelListener` | -100     | `injectViewModel`          | Inserts the `ViewModel` (in this case, a `ViewModel`) and adds it to the `MvcEvent` object. It either (a) adds it as a child to the default, composed view model, or (b) replaces it if the result is marked as terminable.

#### All contexts

The following listeners are attached for all contexts (sorted from higher
priority to lower priority):

Class                         | Priority | Method Called | Triggers | Description
------------------------------|---------:|---------------|----------|------------
`Zend\Mvc\MiddlewareListener` | 1        | `onDispatch`  | `MvcEvent::EVENT_DISPATCH_ERROR` (if an exception is raised during dispatch processes) | Load and dispatch the matched PSR-7 middleware from the service manager (and throws various exceptions if it does not).
`Zend\Mvc\DispatchListener`   | 1        | `onDispatch`  | `MvcEvent::EVENT_DISPATCH_ERROR` (if an exception is raised during dispatch processes) |  Load and dispatch the matched controller from the service manager (and throws various exceptions if it does not).
`Zend\Mvc\AbstractController` | 1        | `onDispatch`  | none     | The `onDispatch` method of the `AbstractController` is an abstract method. In `AbstractActionController`, for instance, it calls the action method.

### Triggered By

This event is triggered by the following classes:

Class                                    | In Method   | Description
-----------------------------------------|-------------|------------
`Zend\Mvc\Application`                   | `run`       | Uses a short circuit callback to halt propagation of the event if an error is raised during routing.
`Zend\Mvc\Controller\AbstractController` | `dispatch`  | If a listener returns a `Response` object, it halts propagation. Note: every `AbstractController` listens to this event and executes the `onDispatch` method when it is triggered.

## `MvcEvent::EVENT_DISPATCH_ERROR` ("dispatch.error")

### Listeners

The following classes listen to this event (sorted from higher priority to lower
priority):

#### Console context only

The following listeners are only attached in a console context:

Class                                           | Priority | Method Called               | Description
------------------------------------------------|---------:|-----------------------------|------------
`Zend\Mvc\View\Console\RouteNotFoundStrategy`   | 1        | `handleRouteNotFoundError ` | Detect if an error is a "route not found" condition. If a “controller not found” or “invalid controller” error type is encountered, sets the response status code to 404.
`Zend\Mvc\View\Console\ExceptionStrategy`       | 1        | `prepareExceptionViewModel` | Create an exception view model, and sets the status code to 404.
`Zend\Mvc\View\Console\InjectViewModelListener` | -100     | `injectViewModel`           | Inserts the `ViewModel` (in this case, a `ConsoleModel`) and adds it to the `MvcEvent` object. It either (a) adds it as a child to the default, composed view model, or (b) replaces it if the result is marked as terminable.

#### HTTP context only

The following listeners are only attached in an HTTP context:

Class                                        | Priority | Method Called               | Description
---------------------------------------------|---------:|-----------------------------|------------
`Zend\Mvc\View\Http\RouteNotFoundStrategy`   | 1        | `detectNotFoundError`       | Detect if an error is a 404 condition. If a “controller not found” or “invalid controller” error type is encountered, sets the response status code to 404.
`Zend\Mvc\View\Http\RouteNotFoundStrategy`   | 1        | `prepareNotFoundViewModel`  | Create and return a 404 view model.
`Zend\Mvc\View\Http\ExceptionStrategy`       | 1        | `prepareExceptionViewModel` | Create an exception view model and set the status code to 404.
`Zend\Mvc\View\Http\InjectViewModelListener` | -100     | `injectViewModel`           | Inserts the `ViewModel` (in this case, a `ViewModel`) and adds it to the MvcEvent object. It either (a) adds it as a child to the default, composed view model, or (b) replaces it if the result is marked as terminable.

#### All contexts

The following listeners are attached for all contexts:

Class                       | Priority | Method Called        | Description
----------------------------|---------:|----------------------|------------
`Zend\Mvc\DispatchListener` | 1        | `reportMonitorEvent` | Used for monitoring when Zend Server is used.

### Triggered By

Class                         | In Method
------------------------------|----------
`Zend\Mvc\MiddlewareListener` | `onDispatch`
`Zend\Mvc\DispatchListener`   | `onDispatch`
`Zend\Mvc\DispatchListener`   | `marshallControllerNotFoundEvent`
`Zend\Mvc\DispatchListener`   | `marshallBadControllerEvent`

## `MvcEvent::EVENT_RENDER` ("render")

### Listeners

The following classes listen to this event (sorted from higher priority to lower
priority):

#### Console context only

The following listeners are only attached in a console context:

Class                                            | Priority | Method Called | Description
-------------------------------------------------|---------:|---------------|------------
`Zend\Mvc\View\Console\DefaultRenderingStrategy` | -10000   | `render`      | Render the view.

#### HTTP context only

The following listeners are only attached in an HTTP context:

Class                                         | Priority | Method Called | Description
----------------------------------------------|---------:|---------------|------------
`Zend\Mvc\View\Http\DefaultRenderingStrategy` | -10000   | `render`      | Render the view.

### Triggered By

This event is triggered by the following classes:

Class                  | In Method         | Description
-----------------------|-------------------|------------
`Zend\Mvc\Application` | `completeRequest` | This event is triggered just before the `MvcEvent::FINISH` event.

## `MvcEvent::EVENT_RENDER_ERROR` ("render.error")

### Listeners

The following classes listen to this event (sorted from higher priority to lower
priority):

#### Console context only

The following listeners are only attached in a console context:

Class                                           | Priority | Method Called               | Description
------------------------------------------------|---------:|-----------------------------|------------
`Zend\Mvc\View\Console\ExceptionStrategy`       | 1        | `prepareExceptionViewModel` | Create an exception view model and set the status code to 404.
`Zend\Mvc\View\Console\InjectViewModelListener` | -100     | `injectViewModel`           | Inserts the `ViewModel` (in this case, a `ConsoleModel`) and adds it to the `MvcEvent` object. It either (a) adds it as a child to the default, composed view model, or (b) replaces it if the result is marked as terminable.

#### HTTP context only

The following listeners are only attached in an HTTP context:

Class                                           | Priority | Method Called               | Description
------------------------------------------------|---------:|-----------------------------|------------
`Zend\Mvc\View\Http\ExceptionStrategy`          | 1        | `prepareExceptionViewModel` | Create an exception view model and set the status code to 404.
`Zend\Mvc\View\Http\InjectViewModelListener`    | -100     | `injectViewModel`           | Inserts the `ViewModel` (in this case, a `ViewModel`) and adds it to the MvcEvent object. It either (a) adds it as a child to the default, composed view model, or (b) replaces it if the result is marked as terminable.
`Zend\Mvc\View\Http\DefaultRenderingStrategy`   | -10000   | `render`                    | Render the view

### Triggered By

This event is triggered by the following classes:

Class                                         | In Method | Description
----------------------------------------------|-----------|------------
`Zend\Mvc\View\Http\DefaultRenderingStrategy` | `render`  | This event is triggered if an exception is raised during rendering.

## `MvcEvent::EVENT_FINISH` ("finish")

### Listeners

The following classes listen to this event (sorted from higher priority to lower
priority):

Class                           | Priority | Method Called  | Description
--------------------------------|---------:|----------------|------------
`Zend\Mvc\SendResponseListener` | -10000   | `sendResponse` | Triggers the `SendResponseEvent` in order to prepare the response (see the next chapter for more information about `SendResponseEvent`).

### Triggered By

This event is triggered by the following classes:

Class                  | In Method         | Description
-----------------------|-------------------|------------
`Zend\Mvc\Application` | `run`             | This event is triggered once the `MvcEvent::ROUTE` event returns a correct `ResponseInterface`.
`Zend\Mvc\Application` | `run`             | This event is triggered once the `MvcEvent::DISPATCH` event returns a correct `ResponseInterface`.
`Zend\Mvc\Application` | `completeRequest` | This event is triggered after `MvcEvent::RENDER` (at this point, the view is already rendered).
