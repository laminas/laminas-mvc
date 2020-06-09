# Dispatching PSR-7 Middleware

[PSR-7](http://www.php-fig.org/psr/psr-7/) defines interfaces for HTTP messages,
and is now being adopted by many frameworks; Laminas itself offers a
parallel microframework targeting PSR-7 with [Mezzio](https://docs.mezzio.dev/mezzio).
What if you want to dispatch PSR-7 middleware from laminas-mvc?

laminas-mvc currently uses [laminas-http](https://docs.laminas.dev/laminas-http/)
for its HTTP transport layer, and the objects it defines are not compatible with
PSR-7, meaning the basic MVC layer does not and cannot make use of PSR-7
currently.

Package [laminas-mvc-middleware][laminas-mvc-middleware] is a laminas-mvc
application module that enables dispatching of middleware, middleware pipes and
request handlers for the route matches that contain a `middleware` parameter.

## Built-in Optional Support Deprecation

Starting with version 2.7.0, laminas-mvc offered now deprecated
`Laminas\Mvc\MiddlewareListener`. MiddlewareListener is always enabled but
requires optional dependencies installed to be used.  
Module [laminas-mvc-middleware][laminas-mvc-middleware] transparently replaces
it with `Laminas\Mvc\Middleware\MiddlewareListener` when registered with the
laminas-mvc application.

Starting with version 3.2.0, built-in `Laminas\Mvc\MiddlewareListener` will
trigger deprecation level errors on an attempt to handle a route match with
`middleware` parameter.

If your application currently depends on the built-in optional middleware
support, `laminas/laminas-mvc-middleware:~1.0.0` provides a drop-in replacement.
Note that module `Laminas\Mvc\Middleware` must be enabled in the laminas-mvc
application.

[laminas-mvc-middleware]: https://docs.laminas.dev/laminas-mvc-middleware/
