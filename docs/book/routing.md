# Routing

Routing is the act of matching an HTTP request to a given controller.

Typically, routing will examine the request URI, and attempt to match the URI
path segment against provided constraints. If the constraints match, a set of
matches are returned, one of which should be the controller name to execute:

```php
'home' => [
    'type' => Laminas\Router\Http\Literal::class,
    'options' => [
        'route' => '/home',
        'defaults' => [
            'controller' => Application\Controller\IndexController::class,
            'action' => 'index',
        ],
    ],
],
```

Routing can utilize other portions of the request URI or environment as well, such as the host or scheme, query parameters, headers, or request method.

## Configuration File

Routing is configured at the module level. For a module `Application`, the configuration will be located at `module/Application/config/module.config.php`:

```php
return [
    'router' => [
        'routes' => [
            'home' => [
                'type' => Laminas\Router\Http\Literal::class,
                'options' => [
                    'route' => '/home',
                    'defaults' => [
                        'controller' => Application\Controller\IndexController::class,
                        'action' => 'index',
                    ],
                ],
            ],
            // additional routes
        ],
    ],
];
```

## HTTP Route Types

laminas-mvc ships with the following HTTP route types.

### Laminas\\Router\\Http\\Hostname

The `Hostname` route attempts to match the hostname registered in the request
against specific criteria. Typically, this will be in one of the following
forms:

* `subdomain.domain.tld`
* `:subdomain.domain.tld`

In the above, the second route would return a "subdomain" key as part of the
route match.

For any given hostname segment, you may also provide a constraint. As an
example, if the "subdomain" segment needed to match only if it started with "fw"
and contained exactly 2 digits following, the following route would be needed:

```php
$route = Hostname::factory([
    'route' => ':subdomain.domain.tld',
    'constraints' => [
        'subdomain' => 'fw\d{2}',
    ],
]);
```

In the above example, only a "subdomain" key will be returned in the
`RouteMatch`. If you wanted to also provide other information based on matching,
or a default value to return for the subdomain, you need to also provide
defaults.

```php
$route = Hostname::factory([
    'route' => ':subdomain.domain.tld',
    'constraints' => [
        'subdomain' => 'fw\d{2}',
    ],
    'defaults' => [
        'type' => 'json',
    ],
]);
```

When matched, the above will return two keys in the `RouteMatch`, "subdomain"
and "type".

### Laminas\\Router\\Http\\Literal

The `Literal` route is for doing exact matching of the URI path. Configuration
therefore is solely the path you want to match, and the "defaults", or
parameters you want returned on a match.

```php
$route = Literal::factory([
    'route' => '/foo',
    'defaults' => [
        'controller' => 'Application\Controller\IndexController',
        'action' => 'foo',
    ],
]);
```

The above route would match a path "/foo", and return the key "action" in the
`RouteMatch`, with the value "foo".

### Laminas\\Router\\Http\\Method

The `Method` route is used to match the HTTP method or 'verb' specified in the
request (See RFC 2616 Sec. 5.1.1). It can optionally be configured to match
against multiple methods by providing a comma-separated list of method tokens.

```php
$route = Method::factory([
    'verb' => 'post,put',
    'defaults' => [
        'controller' => 'Application\Controller\IndexController',
        'action' => 'form-submit',
    ],
]);
```

The above route would match an http "POST" or "PUT" request and return a
`RouteMatch` object containing a key "action" with a value of "form-submit".

### Laminas\\Router\\Http\\Part

A `Part` route allows crafting a tree of possible routes based on segments of
the URI path. It actually extends the `TreeRouteStack`.

`Part` routes are difficult to describe, so we'll simply provide a sample one
here.

```php
$route = Part::factory([
    'route' => [
        'type' => 'literal',
        'options' => [
            'route' => '/',
            'defaults' => [
                'controller' => 'Application\Controller\IndexController',
                'action' => 'index',
            ],
        ],
    ],
    'route_plugins' => $routePlugins,
    'may_terminate' => true,
    'child_routes' => [
        'blog' => [
            'type' => 'literal',
            'options' => [
                'route' => '/blog',
                'defaults' => [
                    'controller' => 'Application\Controller\BlogController',
                    'action' => 'index',
                ],
            ],
            'may_terminate' => true,
            'child_routes' => [
                'rss' => [
                    'type' => 'literal',
                    'options' => [
                        'route' => '/rss',
                        'defaults' => [
                            'action' => 'rss',
                        ]
                    ],
                    'may_terminate' => true,
                    'child_routes' => [
                        'subrss' => [
                            'type' => 'literal',
                            'options' => [
                                'route' => '/sub',
                                'defaults' => [
                                    'action' => 'subrss',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'forum' => [
            'type' => 'literal',
            'options' => [
                'route' => 'forum',
                'defaults' => [
                    'controller' => 'Application\Controller\ForumController',
                    'action' => 'index',
                ],
            ],
        ],
    ],
]);
```

The above would match the following:

- `/` would load the "Index" controller, "index" action.
- `/blog` would load the "Blog" controller, "index" action.
- `/blog/rss` would load the "Blog" controller, "rss" action.
- `/blog/rss/sub` would load the "Blog" controller, "subrss" action.
- `/forum` would load the "Forum" controller, "index" action.

You may use any route type as a child route of a `Part` route.

> ### Part routes are an implementation detail
>
> `Part` routes are not meant to be used directly. When you add definitions for
> `child_routes` to any route type, that route will become a `Part` route. As
> already said, describing `Part` routes with words is difficult, so hopefully
> the additional [examples at the end](#http-routing-examples) will provide
> further insight.

> ### Route plugins
>
> In the above example, the `$routePlugins` is an instance of
> `Laminas\Router\RoutePluginManager`, containing essentially the following
> configuration:
>
> ```php
> $routePlugins = new Laminas\Router\RoutePluginManager();
> $plugins = [
>     'hostname' => 'Laminas\Router\Http\Hostname',
>     'literal'  => 'Laminas\Router\Http\Literal',
>     'part'     => 'Laminas\Router\Http\Part',
>     'regex'    => 'Laminas\Router\Http\Regex',
>     'scheme'   => 'Laminas\Router\Http\Scheme',
>     'segment'  => 'Laminas\Router\Http\Segment',
>     'wildcard' => 'Laminas\Router\Http\Wildcard',
>     'query'    => 'Laminas\Router\Http\Query',
>     'method'   => 'Laminas\Router\Http\Method',
> ];
> foreach ($plugins as $name => $class) {
>     $routePlugins->setInvokableClass($name, $class);
> }
> ```
>
> When using `Laminas\Router\Http\TreeRouteStack`, the `RoutePluginManager` is
> set up by default, and the developer does not need to worry about autoloading
> of standard HTTP routes.

### Laminas\\Router\\Http\\Regex

A `Regex` route utilizes a regular expression to match against the URI path. Any
valid regular expression is allowed; our recommendation is to use named captures
for any values you want to return in the `RouteMatch`.

Since regular expression routes are often complex, you must specify a "spec" or
specification to use when assembling URLs from regex routes. The spec is simply
a string; replacements are identified using `%keyname%` within the string, with
the keys coming from either the captured values or named parameters passed to
the `assemble()` method.

Just like other routes, the `Regex` route can accept "defaults", parameters to
include in the `RouteMatch` when successfully matched.

```php
$route = Regex::factory([
    'regex' => '/blog/(?<id>[a-zA-Z0-9_-]+)(\.(?<format>(json|html|xml|rss)))?',
    'defaults' => [
        'controller' => 'Application\Controller\BlogController',
        'action'     => 'view',
        'format'     => 'html',
    ],
    'spec' => '/blog/%id%.%format%',
]);
```

The above would match `/blog/001-some-blog_slug-here.html`, and return four
items in the `RouteMatch`, an "id", the "controller", the "action", and the
"format". When assembling a URL from this route, the "id" and "format" values
would be used to fill the specification.

### Laminas\\Router\\Http\\Scheme

The `Scheme` route matches the URI scheme only, and must be an exact match. As
such, this route, like the `Literal` route, simply takes what you want to match
and the "defaults", parameters to return on a match.

```php
$route = Scheme::factory([
    'scheme' => 'https',
    'defaults' => [
        'https' => true,
    ],
]);
```

The above route would match the "https" scheme, and return the key "https" in
the `RouteMatch` with a boolean `true` value.

### Laminas\\Router\\Http\\Segment

A `Segment` route allows matching any segment of a URI path. Segments are
denoted using a colon, followed by alphanumeric characters; if a segment is
optional, it should be surrounded by brackets.  As an example, `/:foo[/:bar]`
would match a `/` followed by text and assign it to the key "foo"; if any
additional `/` characters are found, any text following the last one will be
assigned to the key "bar".

The separation between literal and named segments can be anything. For example,
the above could be done as `/:foo{-}[-:bar]` as well. The `{-}` after the `:foo`
parameter indicates a set of one or more delimiters, after which matching of the
parameter itself ends.

Each segment may have constraints associated with it. Each constraint should
simply be a regular expression expressing the conditions under which that
segment should match.

Also, as you can in other routes, you may provide defaults to use; these are
particularly useful when using optional segments.

As a complex example:

```php
$route = Segment::factory([
    'route' => '/:controller[/:action]',
    'constraints' => [
        'controller' => '[a-zA-Z][a-zA-Z0-9_-]+',
        'action'     => '[a-zA-Z][a-zA-Z0-9_-]+',
    ],
    'defaults' => [
        'controller' => 'Application\Controller\IndexController',
        'action'     => 'index',
    ],
]);
```

### Laminas\\Router\\Http\\Query (Deprecated)

> #### Potential security issue
>
> Misuse of this route can lead to potential security issues.

> #### Deprecated
>
> This route part is deprecated since you can now add query parameters without a
> query route. It was removed in version 3 of the router.

The `Query` route part allows you to specify and capture query string parameters
for a given route.

The intention of the `Query` part is that you do not instantiate it in its own
right, but use it as a child of another route part.

An example of its usage would be:

```php
$route = Part::factory([
    'route' => [
        'type'    => 'literal',
        'options' => [
            'route'    => 'page',
            'defaults' => [],
        ],
    ],
    'may_terminate' => true,
    'route_plugins' => $routePlugins,
    'child_routes'  => [
        'query' => [
            'type' => 'Query',
            'options' => [
                'defaults' => [
                    'foo' => 'bar',
                ],
            ],
        ],
    ],
]);
```

This then allows you to create query strings using the url view helper.

```php
$this->url(
    'page/query',
    [
        'name'   => 'my-test-page',
        'format' => 'rss',
        'limit'  => 10,
    ]
);
```

Per the above example, you must add `/query` (the name we gave to our query
route segment) to your route name in order to append a query string. If you do
not specify `/query` in the route name, then no query string will be appended.

Our example "page" route has only one defined parameter of "name"
(`/page[/:name]`), meaning that the remaining parameters of "format" and "limit"
will then be appended as a query string.

The output from our example should then be
`/page/my-test-page?format=rss&limit=10`

### Laminas\\Router\\Http\\Wildcard (Deprecated)

> #### Potential security issue
>
> Misuse of this route type can lead to potential security issues.

> #### Deprecated
>
> This route type is deprecated. Use the `Segment` route type.

The `Wildcard` route type matches all remaining segments of a URI path.

## HTTP Routing Examples

Most of the routing definitions will be done in module configuration files, so
the following examples will show how to set up routes in config files.

### Simple example with two literal routes

```php
return [
    'router' => [
        'routes' => [
            // Literal route named "home"
            'home' => [
                'type' => 'literal',
                'options' => [
                    'route' => '/',
                    'defaults' => [
                        'controller' => 'Application\Controller\IndexController',
                        'action' => 'index',
                    ],
                ],
            ],
            // Literal route named "contact"
            'contact' => [
                'type' => 'literal',
                'options' => [
                    'route' => 'contact',
                    'defaults' => [
                        'controller' => 'Application\Controller\ContactController',
                        'action' => 'form',
                    ],
                ],
            ],
        ],
    ],
];
```

### A complex example with child routes

```php
return [
    'router' => [
        'routes' => [
            // Literal route named "home"
            'home' => [
                'type' => 'literal',
                'options' => [
                    'route' => '/',
                    'defaults' => [
                        'controller' => 'Application\Controller\IndexController',
                        'action' => 'index',
                    ],
                ],
            ],
            // Literal route named "blog", with child routes
            'blog' => [
                'type' => 'literal',
                'options' => [
                    'route' => '/blog',
                    'defaults' => [
                        'controller' => 'Application\Controller\BlogController',
                        'action' => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    // Segment route for viewing one blog post
                    'post' => [
                        'type' => 'segment',
                        'options' => [
                            'route' => '/[:slug]',
                            'constraints' => [
                                'slug' => '[a-zA-Z0-9_-]+',
                            ],
                            'defaults' => [
                                'action' => 'view',
                            ],
                        ],
                    ],
                    // Literal route for viewing blog RSS feed
                    'rss' => [
                        'type' => 'literal',
                        'options' => [
                            'route' => '/rss',
                            'defaults' => [
                                'action' => 'rss',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
```

When using child routes, naming of the routes follows the `parent/child`
pattern, so to use the child routes from the above example:

```php
echo $this->url('blog'); // gives "/blog"
echo $this->url('blog/post', ['slug' => 'my-post']); // gives "/blog/my-post"
echo $this->url('blog/rss'); // gives "/blog/rss"
```

### An example with multiple Hostnames and subdomains within a single application

```php
return [
    'router' => [
        'routes' => [
            'modules.laminas.dev' => [
                'type' => 'Laminas\Router\Http\Hostname',
                'options' => [
                    'route' => ':4th.[:3rd.]:2nd.:1st', // domain levels from right to left
                    'constraints' => [
                        '4th' => 'modules',
                        '3rd' => '.*?', // optional 3rd level domain such as .ci, .dev or .test
                        '2nd' => 'laminas',
                        '1st' => 'com',
                    ],
                    // Purposely omit default controller and action
                    // to let the child routes control the route match
                ],
                // child route controllers may span multiple modules as desired
                'child_routes' => [
                    'index' => [
                        'type' => 'Laminas\Router\Http\Literal',
                        'options' => [
                            'route' => '/',
                            'defaults' => [
                                'controller' => 'Module\Controller\Index',
                                'action' = > 'index',
                            ],
                        ],
                        'may_terminate' => true,
                    ],
                ],
            ],
            'packages.laminas.dev' => [
                'type' => 'Laminas\Router\Http\Hostname',
                'options' => [
                    'route' => ':4th.[:3rd.]:2nd.:1st', // domain levels from right to left
                    'constraints' => [
                        '4th' => 'packages',
                        '3rd' => '.*?', // optional 3rd level domain such as .ci, .dev or .test
                        '2nd' => 'laminas',
                        '1st' => 'com',
                    ],
                    // Purposely omit default controller and action
                    // to let the child routes control the route match
                ],
                // child route controllers may span multiple modules as desired
                'child_routes' => [
                    'index' => [
                        'type' => 'Laminas\Router\Http\Literal',
                        'options' => [
                            'route' => '/',
                            'defaults' => [
                                'controller' => 'Package\Controller\Index',
                                'action' = > 'index',
                            ],
                        ],
                        'may_terminate' => true,
                    ],
                ],
            ],
        ],
    ],
];
```

The above would match the following:

- `modules.laminas.dev` would dispatch the `Index` controller's `index`
  action of the `Module` module.
- `modules.ci.laminas.dev` would dispatch the `Index` controller's `index`
  action of the `Module` module.
- `packages.laminas.dev` would dispatch the `Index` controller's `index`
  action of the `Package` module.
- `packages.dev.laminas.dev` would dispatch the `Index` controller's
  `index` action of the `Package` module.

The `Url` controller plugin or view helper may be used to generate URLs
following the above example:

```php
// reuse the route matched parameters to generate URLs
echo $this->url('modules.laminas.dev/index', [], [], true);
echo $this->url('packages.laminas.dev/index', [], [], true);
```

> ### may_terminate and child_routes placement
>
> When defining child routes pay attention that the `may_terminate` and
> `child_routes` definitions are in same level as the `options` and `type`
> definitions. A common pitfall is to have those two definitions nested in
> `options`, which will not result in the desired routes.
