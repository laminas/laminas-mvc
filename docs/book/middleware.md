# Dispatching PSR-7 Middleware

[PSR-7](http://www.php-fig.org/psr/psr-7/) defines interfaces for HTTP messages,
and is now being adopted by many frameworks; Zend Framework itself offers a
parallel microframework targeting PSR-7 with [Expressive](https://docs.zendframework.com/zend-expressive).
What if you want to dispatch PSR-7 middleware from zend-mvc?

zend-mvc currently uses [zend-http](https://github.com/zendframework/zend-http)
for its HTTP transport layer, and the objects it defines are not compatible with
PSR-7, meaning the basic MVC layer does not and cannot make use of PSR-7
currently.

However, starting with version 2.7.0, zend-mvc offers
`Zend\Mvc\MiddlewareListener`. This [dispatch](mvc-event.md#mvceventevent_dispatch-dispatch)
listener listens prior to the default `DispatchListener`, and executes if the
route matches contain a "middleware" parameter, and the service that resolves to
is callable. When those conditions are met, it uses the [PSR-7 bridge](https://github.com/zendframework/zend-psr7bridge)
to convert the zend-http request and response objects into PSR-7 instances, and
then invokes the middleware.

## Mapping routes to middleware

The first step is to map a route to PSR-7 middleware. This looks like any other
[routing](routing.md) configuration, with one small change: instead of providing
a "controller" in the routing defaults, you provide "middleware":

```php
// Via configuration:
return [
    'router' =>
        'routes' => [
            'home' => [
                'type' => 'literal',
                'options' => [
                    'route' => '/',
                    'defaults' => [
                        'middleware' => 'Application\Middleware\IndexMiddleware',
                    ],
                ],
            ],
        ],
    ],
];

// Manually:
$route = Literal::factory([
    'route' => '/',
    'defaults' => [
        'middleware' => 'Application\Middleware\IndexMiddleware',
    ],
]);
```

Middleware may be provided as PHP callables, or as service names.

**As of 3.1.0** you may also specify an `array` of middleware, and middleware
may be [http-interop/http-middleware](https://github.com/http-interop/http-middleware)
compatible. Each item in the array must be a PHP callable, service name, or
http-middleware instance. These will then be piped into a
`Zend\Stratigility\MiddlewarePipe` instance in the order in which they are
present in the array.

> ### No action required
>
> Unlike action controllers, middleware typically is single purpose, and, as
> such, does not require a default `action` parameter.

## Middleware services

In a normal zend-mvc dispatch cycle, controllers are pulled from a dedicated
`ControllerManager`. Middleware, however, are pulled from the application
service manager.

Middleware retrieved *must* be PHP callables. The `MiddlewareListener` will
create an error response if non-callable middleware is indicated.

## Writing middleware

Prior to 3.1.0, when dispatching middleware, the `MiddlewareListener` calls it
with two arguments, the PSR-7 request and response, respectively. As such, your
middleware signature should look like the following:

```php
namespace Application\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class IndexMiddleware
{
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        // do some work
    }
}
```

Starting in 3.1.0, the `MiddlewareListener` always adds middleware to a
`Zend\Stratigility\MiddlewarePipe` instance, and invokes it as
[http-interop/http-middleware](https://github.com/http-interop/http-middleware),
passing it a PSR-7 `ServerRequestInterface` and an http-interop
`DelegateInterface`.

As such, ideally your middleware should implement the `MiddlewareInterface` from
[http-interop/http-middleware](https://github.com/http-interop/http-middleware):

```php
namespace Application\Middleware;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;

class IndexMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        // do some work
    }
}
```

Alternately, you may still write `callable` middleware using the following
signature:

```php
function (ServerREquestInterface $request, ResponseInterface $response, callable $next)
{
    // do some work
}
```

In the above case, the `DelegateInterface` is decorated as a callable.

In all versions, within your middleware, you can pull information from the
composed request, and return a response.

> ### Routing parameters
>
> At the time of the 2.7.0 release, route match parameters were not yet injected
> into the PSR-7 `ServerRequest` instance, and thus not available as request
> attributes.
>
> With the 3.0 release, they are pushed into the PSR-7 `ServerRequest` as
> attributes, and may thus be fetched using
> `$request->getAttribute($attributeName)`.

## Middleware return values

Ideally, your middleware should return a PSR-7 response. When it does, it is
converted back to a zend-http response and returned by the `MiddlewareListener`,
causing the application to short-circuit and return the response immediately.

You can, however, return arbitrary values. If you do, the result is pushed into
the `MvcEvent` as the event result, allowing later dispatch listeners to
manipulate the results.
