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

[laminas-mvc-middleware]: https://docs.laminas.dev/laminas-mvc-middleware/
