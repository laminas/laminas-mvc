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

Note that when adding multiple routes, the last route in the list will be checked first.

## HTTP Route Types

Laminas MVC ships with the following HTTP route types:

[Hostname](https://docs.laminas.dev/laminas-router/routing/#laminasrouterhttphostname)
:   matches domains and subdomains.  
    Example: `docs.laminas.dev`.
[Literal](https://docs.laminas.dev/laminas-router/routing/#laminasrouterhttpliteral)
:   matches an exact URI path.  
    Example: `/contact-us`
[Method](https://docs.laminas.dev/laminas-router/routing/#laminasrouterhttpmethod)
:   matches HTTP verbs.  
    Example: `post,put` for a route that submits a form.
[Part](https://docs.laminas.dev/laminas-router/routing/#laminasrouterhttppart)
:   allows crafting a tree of possible routes based on segments of the URI path.  
    Example: `/blog` can have child routes with `/rss` and `/subscribe`, which would match `/blog/rss` and `/blog/subscribe` respectively.
[Regex](https://docs.laminas.dev/laminas-router/routing/#laminasrouterhttpplaceholder)
:   matches a URI path using a regular expression.
    Example: `/product/(?<id>[0-9]+)` would match `/product/001`, with the `id` parameter containing `001`.
[Scheme](https://docs.laminas.dev/laminas-router/routing/#laminasrouterhttpscheme)
:   matches the URI scheme.  
    Example: `https`.
[Segment](https://docs.laminas.dev/laminas-router/routing/#laminasrouterhttpsegment)
:   matching one or more segments of a URI path.  
    Example: `/:blog/:article` would match `/blog/why-use-mvc`, with the `article` parameter containing `why-use-mvc`.

Learn more about routing in the [laminas-router documentation](https://docs.laminas.dev/laminas-router/routing).
