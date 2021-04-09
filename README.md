# laminas-mvc

[![Build Status](https://github.com/laminas/laminas-mvc/workflows/Continuous%20Integration/badge.svg)](https://github.com/laminas/laminas-mvc/actions?query=workflow%3A"Continuous+Integration")

`Laminas\Mvc` is an MVC implementation focusing on performance and flexibility.

The MVC layer is built on top of the following components:

- `Laminas\ServiceManager` - Laminas provides a set of default service
  definitions set up at `Laminas\Mvc\Service`. The ServiceManager creates and
  configures your application instance and workflow.
- `Laminas\EventManager` - The MVC is event driven. This component is used
  everywhere from initial bootstrapping of the application, through returning
  response and request calls, to setting and retrieving routes and matched
  routes, as well as render views.
- `Laminas\Http` - specifically the request and response objects, used within:
  `Laminas\Stdlib\DispatchableInterface`. All “controllers” are simply dispatchable
  objects.


- File issues at https://github.com/laminas/laminas-mvc/issues
- Documentation is at https://docs.laminas.dev/laminas-mvc/
