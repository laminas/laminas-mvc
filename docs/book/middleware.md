# Dispatching PSR-7 Middleware

[PSR-7](http://www.php-fig.org/psr/psr-7/) defines interfaces for HTTP messages,
and is now being adopted by many frameworks; Laminas itself offers a
parallel microframework targeting PSR-7 with [Mezzio](https://docs.mezzio.dev/mezzio).
What if you want to dispatch PSR-7 middleware from laminas-mvc?

laminas-mvc currently uses [laminas-http](https://docs.laminas.dev/laminas-http/)
for its HTTP transport layer, and the objects it defines are not compatible with
PSR-7, meaning the basic MVC layer does not and cannot make use of PSR-7
currently.

The package [laminas-mvc-middleware][laminas-mvc-middleware] is a laminas-mvc
application module that enables dispatching of middleware, middleware pipes, and
request handlers for route matches that contain a `middleware` parameter.

## Built-in Optional Support Deprecation

With version 2.7.0, laminas-mvc began offering the now deprecated
`Laminas\Mvc\MiddlewareListener`. The `MiddlewareListener` is always enabled, but
requires optional dependencies installed to be used.  
A new laminas-mvc module, [laminas-mvc-middleware][laminas-mvc-middleware], transparently replaces
it with `Laminas\Mvc\Middleware\MiddlewareListener` when registered with a
laminas-mvc application.

Starting with version 3.2.0, the built-in `Laminas\Mvc\MiddlewareListener` will
trigger deprecation level errors on any attempt to handle a route match containing
a `middleware` parameter.

If your application currently depends on the built-in optional middleware
support, `laminas/laminas-mvc-middleware:~1.0.0` provides a drop-in replacement.
If you use this new module, please note that the module `Laminas\Mvc\Middleware`
must be enabled in your laminas-mvc application.

[laminas-mvc-middleware]: https://docs.laminas.dev/laminas-mvc-middleware/
