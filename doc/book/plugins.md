# Controller Plugins

When using any of the abstract controller implementations shipped with laminas-mvc,
or if you implement the `setPluginManager` method in your custom controllers,
you have access to a number of pre-built plugins. Additionally, you can register
your own custom plugins with the manager.

The built-in plugins are:

- [Laminas\\Mvc\\Controller\\Plugin\\AcceptableViewModelSelector](#acceptableviewmodelselector-plugin)
- [Laminas\\Mvc\\Controller\\Plugin\\FlashMessenger](#flashmessenger-plugin)
- [Laminas\\Mvc\\Controller\\Plugin\\Forward](#forward-plugin)
- [Laminas\\Mvc\\Controller\\Plugin\\Identity](#identity-plugin)
- [Laminas\\Mvc\\Controller\\Plugin\\Layout](#layout-plugin)
- [Laminas\\Mvc\\Controller\\Plugin\\Params](#params-plugin)
- [Laminas\\Mvc\\Controller\\Plugin\\PostRedirectGet](#postredirectget-plugin)
- [Laminas\\Mvc\\Controller\\Plugin\\FilePostRedirectGet](#file-postredirectget-plugin)
- [Laminas\\Mvc\\Controller\\Plugin\\Redirect](#redirect-plugin)
- [Laminas\\Mvc\\Controller\\Plugin\\Url](#url-plugin)

If your controller implements the `setPluginManager()`, `getPluginManager()` and
`plugin()` methods, you can access these using their shortname via the `plugin()`
method:

```php
$plugin = $this->plugin('url');
```

For an extra layer of convenience, this shipped abstract controller
implementations have `__call()` methods defined that allow you to retrieve
plugins via method calls:

```php
$plugin = $this->url();
```

## AcceptableViewModelSelector Plugin

The `AcceptableViewModelSelector` is a helper that can be used to select an
appropriate view model based on user defined criteria will be tested against the
Accept header in the request.

As an example:

```php
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;

class SomeController extends AbstractActionController
{
   protected $acceptCriteria = [
      'Laminas\View\Model\JsonModel' => [
         'application/json',
      ],
      'Laminas\View\Model\FeedModel' => [
         'application/rss+xml',
      ],
   ];

   public function apiAction()
   {
      $viewModel = $this->acceptableViewModelSelector($this->acceptCriteria);

      // Potentially vary execution based on model returned
      if ($viewModel instanceof JsonModel) {
         // ...
      }
   }
}
```

The above would return a standard `Laminas\View\Model\ViewModel` instance if the
criteria is not met, and the specified view model types if the specific criteria
is met. Rules are matched in order, with the first match "winning."

## FlashMessenger Plugin

The `FlashMessenger` is a plugin designed to create and retrieve self-expiring,
session-based messages. It exposes a number of methods:

- `setSessionManager(Laminas\Session\ManagerInterface $manager) : FlashMessenger`:
  Allows you to specify an alternate session manager, if desired.

- `getSessionManager() : Laminas\Session\ManagerInterface`: Allows you to retrieve
  the session manager registered.

- `getContainer() : Laminas\Session\Container`: Returns the
  `Laminas\Session\Container` instance in which the flash messages are stored.

- `setNamespace(string $namespace = 'default') : FlashMessenger`:
  Allows you to specify a specific namespace in the container in which to store
  or from which to retrieve flash messages.

- `getNamespace() : string`: retrieves the name of the flash message namespace.

- `addMessage(string $message) : FlashMessenger`: Allows you to add a message to
  the current namespace of the session container.

- `hasMessages() : bool`: Lets you determine if there are any flash messages
  from the current namespace in the session container.

- `getMessages() : array`: Retrieves the flash messages from the current
  namespace of the session container

- `clearMessages() : bool`: Clears all flash messages in current namespace of
  the session container. Returns `true` if messages were cleared, `false` if
  none existed.

- `hasCurrentMessages() : bool`: Indicates whether any messages were added
  during the current request.

- `getCurrentMessages() : array`: Retrieves any messages added during the
  current request.

- `clearCurrentMessages() : bool`: Removes any messages added during the current
  request. Returns `true` if current messages were cleared, `false` if none
  existed.

- `clearMessagesFromContainer() : bool`: Clear all messages from the container.
  Returns `true` if any messages were cleared, `false` if none existed.

This plugin also provides four meaningful namespaces, namely: `INFO`, `ERROR`,
`WARNING`, and `SUCCESS`. The following functions are related to these
namespaces:

- `addInfoMessage(string $message): FlashMessenger`: Add a message to "info"
  namespace.

- `hasCurrentInfoMessages() : bool`: Check to see if messages have been added to
  "info" namespace within this request.

- `addWarningMessage(string $message) : FlashMessenger`: Add a message to
  "warning" namespace.

- `hasCurrentWarningMessages() : bool`: Check to see if messages have been added
  to "warning" namespace within this request.

- `addErrorMessage(string $message) : FlashMessenger`: Add a message to "error"
  namespace.

- `hasCurrentErrorMessages() : bool`: Check to see if messages have been added
  to "error" namespace within this request.

- `addSuccessMessage(string $message) : FlashMessenger`: Add a message to
  "success" namespace.

- `hasCurrentSuccessMessages() :bool`: Check to see if messages have been added
  to "success" namespace within this request.

Additionally, the `FlashMessenger` implements both `IteratorAggregate` and
`Countable`, allowing you to iterate over and count the flash messages in the
current namespace within the session container.

### Examples

```php
public function processAction()
{
    // ... do some work ...
    $this->flashMessenger()->addMessage('You are now logged in.');
    return $this->redirect()->toRoute('user-success');
}

public function successAction()
{
    $return = ['success' => true];
    $flashMessenger = $this->flashMessenger();
    if ($flashMessenger->hasMessages()) {
        $return['messages'] = $flashMessenger->getMessages();
    }
    return $return;
}
```

## Forward Plugin

Occasionally, you may want to dispatch additional controllers from within the
matched controller. For example, you might use this approach to build up
"widgetized" content. The `Forward` plugin helps enable this.

For the `Forward` plugin to work, the controller calling it must be
`ServiceLocatorAware`; otherwise, the plugin will be unable to retrieve a
configured and injected instance of the requested controller.

The plugin exposes a single method, `dispatch()`, which takes two arguments:

- `$name`, the name of the controller to invoke. This may be either the fully
  qualified class name, or an alias defined and recognized by the
  `ServiceManager` instance attached to the invoking controller.
- `$params` is an optional array of parameters with which to seed a `RouteMatch`
  object for purposes of this specific request. Meaning the parameters will be
  matched by their key to the routing identifiers in the config (otherwise
  non-matching keys are ignored)

`Forward` returns the results of dispatching the requested controller; it is up
to the developer to determine what, if anything, to do with those results. One
recommendation is to aggregate them in any return value from the invoking
controller.

As an example:

```php
$foo = $this->forward()->dispatch('foo', ['action' => 'process']);
return [
    'somekey' => $somevalue,
    'foo'     => $foo,
];
```

## Identity Plugin

The `Identity` plugin allows retrieving the identity from the
`AuthenticationService`.

For the `Identity` plugin to work, a `Laminas\Authentication\AuthenticationService`
name or alias must be defined and recognized by the `ServiceManager`.

`Identity` returns the identity in the `AuthenticationService` or `null` if no
identity is available.

As an example:

```php
public function testAction()
{
    if ($user = $this->identity()) {
         // someone is logged !
    } else {
         // not logged in
    }
}
```

When invoked, the `Identity` plugin will look for a service by the name or alias
`Laminas\Authentication\AuthenticationService` in the `ServiceManager`. You can
provide this service to the `ServiceManager` in a configuration file:

```php
// In a configuration file...
use Laminas\Authentication\AuthenticationService;

return [
    'service_manager' => [
        'aliases' => [
            AuthenticationService::class => 'my_auth_service',
        ],
        'invokables' => [
            'my_auth_service' => AuthenticationService::class,
        ],
    ],
];
```

The `Identity` plugin exposes two methods:

- `setAuthenticationService(AuthenticationService $authenticationService) : void`:
  Sets the authentication service instance to be used by the plugin.

- `getAuthenticationService() : AuthenticationService`: Retrieves the current
  authentication service instance if any is attached.

## Layout Plugin

The `Layout` plugin allows changing layout templates from within controller actions.

It exposes a single method, `setTemplate()`, which takes one argument,
`$template`, the name of the template to set.

As an example:

```php
$this->layout()->setTemplate('layout/newlayout');
```

It also implements the `__invoke` magic method, which allows calling the plugin
as a method call:

```php
$this->layout('layout/newlayout');
```

## Params Plugin

The `Params` plugin allows accessing parameters in actions from different sources.

It exposes several methods, one for each parameter source:

- `fromFiles(string $name = null, mixed $default = null): array|ArrayAccess|null`:
  For retrieving all or one single **file**. If `$name` is null, all files will
  be returned.

- `fromHeader(string $header = null, mixed $default = null) : null|Laminas\Http\Header\HeaderInterface`:
  For retrieving all or one single **header** parameter. If `$header` is null,
  all header parameters will be returned.

- `fromPost(string $param = null, mixed $default = null) : mixed`: For
  retrieving all or one single **post** parameter. If `$param` is null, all post
  parameters will be returned.

- `fromQuery(string $param = null, mixed $default = null) : mixed`: For
  retrieving all or one single **query** parameter. If `$param` is null, all
  query parameters will be returned.

- `fromRoute(string $param = null, mixed $default = null) : mixed`: For
  retrieving all or one single **route** parameter. If `$param` is null, all
  route parameters will be returned.

The plugin also implements the `__invoke` magic method, providing a shortcut
for invoking the `fromRoute` method:

```php
$this->params()->fromRoute('param', $default);
// or
$this->params('param', $default);
```

## Post/Redirect/Get Plugin

When a user sends a POST request (e.g. after submitting a form), their browser
will try to protect them from sending the POST again, breaking the back button,
causing browser warnings and pop-ups, and sometimes reposting the form. Instead,
when receiving a POST, we should store the data in a session container and
redirect the user to a GET request.

This plugin can be invoked with two arguments:

- `$redirect`, a string containing the redirect location, which can either be a
  named route or a URL, based on the contents of the second parameter.
- `$redirectToUrl`, a boolean that when set to `TRUE`, causes the first
  parameter to be treated as a URL instead of a route name (this is required
  when redirecting to a URL instead of a route). This argument defaults to
  `false`.

When no arguments are provided, the current matched route is used.

### Example Usage

```php
// Pass in the route/url you want to redirect to after the POST
$prg = $this->prg('/user/register', true);

if ($prg instanceof \Laminas\Http\PhpEnvironment\Response) {
    // Returned a response to redirect us.
    return $prg;
}

if ($prg === false) {
    // This wasn't a POST request, but there were no params in the flash
    // messenger; this is probably the first time the form was loaded.
    return ['form' => $myForm];
}

// $prg is an array containing the POST params from the previous request
$form->setData($prg);

// ... your form processing code here
```

## File Post/Redirect/Get Plugin

While similar to the [Post/Redirect/Get Plugin](#postredirectget-plugin),
the File PRG Plugin will work for forms with file inputs. The difference is in
the behavior: The File PRG Plugin will interact directly with your form instance
and the file inputs, rather than *only* returning the POST params from the
previous request.

By interacting directly with the form, the File PRG Plugin will turn off any
file inputs `required` flags for already uploaded files (for a partially valid
form state), as well as run the file input filters to move the uploaded files
into a new location (configured by the user).

> ### Files must be relocated on upload
>
> You **must** attach a filter for moving the uploaded files to a new location, such as the
> [RenameUpload Filter](http://docs.laminas.dev/laminas-filter/file/#renameupload),
> or else your files will be removed upon the redirect.

This plugin is invoked with three arguments:

- `$form`: the form instance.
- `$redirect`: (Optional) a string containing the redirect location, which can
  either be a named route or a URL, based on the contents of the third
  parameter. If this argument is not provided, it will default to the current
  matched route.
- `$redirectToUrl`: (Optional) a boolean that when set to `TRUE`, causes the
  second parameter to be treated as a URL instead of a route name (this is
  required when redirecting to a URL instead of a route). This argument defaults
  to `false`.

### Example Usage

```php
$myForm = new Laminas\Form\Form('my-form');
$myForm->add([
    'type' => 'Laminas\Form\Element\File',
    'name' => 'file',
]);

// NOTE: Without a filter to move the file,
//       our files will disappear between the requests
$myForm->getInputFilter()->getFilterChain()->attach(
    new Laminas\Filter\File\RenameUpload([
        'target'    => './data/tmpuploads/file',
        'randomize' => true,
    ])
);

// Pass in the form and optional the route/url you want to redirect to after the POST
$prg = $this->fileprg($myForm, '/user/profile-pic', true);

if ($prg instanceof \Laminas\Http\PhpEnvironment\Response) {
    // Returned a response to redirect us.
    return $prg;
}

if ($prg === false) {
    // First time the form was loaded.
    return array('form' => $myForm);
}

// Form was submitted.
// $prg is now an array containing the POST params from the previous request,
// but we don't have to apply it to the form since that has already been done.

// Process the form
if ($form->isValid()) {
    // ...Save the form...
    return $this->redirect()->toRoute('/user/profile-pic/success');
}

// Form not valid, but file uploads might be valid and uploaded
$fileErrors = $form->get('file')->getMessages();
if (empty($fileErrors)) {
    $tempFile = $form->get('file')->getValue();
}
```

## Redirect Plugin

Redirections are quite common operations within applications. If done manually,
you will need to do the following steps:

- Assemble a url using the router.
- Create and inject a "Location" header into the `Response` object, pointing to
  the assembled URL.
- Set the status code of the `Response` object to one of the 3xx HTTP statuses.

The `Redirect` plugin does this work for you. It offers three methods:

- `toRoute(string $route = null, array $params = array(), array $options = array(), boolean $reuseMatchedParams = false) : Laminas\Http\Response`:
  Redirects to a named route, using the provided `$params` and `$options` to
  assembled the URL.

- `toUrl(string $url) : Laminas\Http\Response`: Simply redirects to the given URL.

- `refresh() : Laminas\Http\Response`: Refresh to current route.

In each case, the `Response` object is returned. If you return this immediately,
you can effectively short-circuit execution of the request.

> ### Requires MvcEvent
>
> This plugin requires that the controller invoking it implements
> `InjectApplicationEventInterface`, and thus has an `MvcEvent` composed, as it
> retrieves the router from the event object.

As an example:

```php
return $this->redirect()->toRoute('login-success');
```

## Url Plugin

You may need to generate URLs from route definitions within your controllers;
for example, to seed the view, generate headers, etc. While the `MvcEvent`
object composes the router, doing so manually would require this workflow:

```php
$router = $this->getEvent()->getRouter();
$url    = $router->assemble($params, ['name' => 'route-name']);
```

The `Url` helper makes this slightly more convenient:

```php
$url = $this->url()->fromRoute('route-name', $params);
```

The `fromRoute()` method is the only public method defined, and is used to
generate a URL string from the provided parameters. It has the following
signature:

- `fromRoute(string $route = null, array $params = [], array $options = [], bool $reuseMatchedParams = false): string`, where:
  - `$name`: the name of the route to use for URL generation.
  - `$params`: Any parameter substitutions to use with the named route.
  - `$options`: Options used by the router when generating the URL (e.g., `force_canonical`, `query`, etc.).
  - `$reuseMatchedParams`: Whether or not to use route match parameters from the
    current URL when generating the new URL. This will only affect cases where
    the specified `$name` matches the currently matched route; the default is
    `true`.

> ### Requires MvcEvent
>
> This plugin requires that the controller invoking it implements
> `InjectApplicationEventInterface`, and thus has an `MvcEvent` composed, as it
> retrieves the router from the event object.
