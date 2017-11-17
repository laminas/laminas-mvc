# Controller Plugins

When using any of the abstract controller implementations shipped with zend-mvc,
or if you implement the `setPluginManager` method in your custom controllers,
you have access to a number of pre-built plugins. Additionally, you can register
your own custom plugins with the manager.

The built-in plugins are:

- [Zend\\Mvc\\Controller\\Plugin\\AcceptableViewModelSelector](#acceptableviewmodelselector-plugin)
- [Zend\\Mvc\\Controller\\Plugin\\Forward](#forward-plugin)
- [Zend\\Mvc\\Controller\\Plugin\\Layout](#layout-plugin)
- [Zend\\Mvc\\Controller\\Plugin\\Params](#params-plugin)
- [Zend\\Mvc\\Controller\\Plugin\\Redirect](#redirect-plugin)
- [Zend\\Mvc\\Controller\\Plugin\\Url](#url-plugin)

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
use Zend\Mvc\Controller\AbstractActionController;

class SomeController extends AbstractActionController
{
    protected $acceptCriteria = [
        \Zend\View\Model\ViewModel::class => [
            'text/html',
            'application/xhtml+xml',
        ],
        \Zend\View\Model\JsonModel::class => [
            'application/json',
            'application/javascript',
        ],
        \Zend\View\Model\FeedModel::class => [
            'application/rss+xml',
            'application/atom+xml',
        ],
    ];

    public function apiAction()
    {
        $viewModel = $this->acceptableViewModelSelector($this->acceptCriteria);

        // Potentially vary execution based on model returned
        if ($viewModel instanceof \Zend\View\Model\JsonModel) {
            // ...
        }
    }
}
```

The above would return a standard `Zend\View\Model\ViewModel` instance if no
criterias are met, and the specified view model types if a specific criteria
is met. Rules are matched in order, with the first match "winning".
Make sure to put your fallback view model *first* as a fallback for unknown
content types or `*/*`.

> Browsers are sending `*/*` as last content type of the Accept header so you have to define every
> acceptable view model and their content type.

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

- `fromHeader(string $header = null, mixed $default = null) : null|Zend\Http\Header\HeaderInterface`:
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

## Redirect Plugin

Redirections are quite common operations within applications. If done manually,
you will need to do the following steps:

- Assemble a url using the router.
- Create and inject a "Location" header into the `Response` object, pointing to
  the assembled URL.
- Set the status code of the `Response` object to one of the 3xx HTTP statuses.

The `Redirect` plugin does this work for you. It offers three methods:

- `toRoute(string $route = null, array $params = array(), array $options = array(), boolean $reuseMatchedParams = false) : Zend\Http\Response`:
  Redirects to a named route, using the provided `$params` and `$options` to
  assembled the URL.

- `toUrl(string $url) : Zend\Http\Response`: Simply redirects to the given URL.

- `refresh() : Zend\Http\Response`: Refresh to current route.

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
