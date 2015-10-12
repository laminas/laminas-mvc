# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 3.0.0 - TBD

### Added

- [#31](https://github.com/zendframework/zend-mvc/pull/31) adds three required
  arguments to the `Zend\Mvc\Application` constructor: an EventManager
  instance, a Request instance, and a Response instance.
- [#36](https://github.com/zendframework/zend-mvc/pull/36) adds more than a
  dozen service factories, primarily to separate conditional factories into
  discrete factories.
- [#32](https://github.com/zendframework/zend-mvc/pull/32) adds
  `Zend\Mvc\MiddlewareListener`, which allows dispatching PSR-7-based middleware
  implementing the signature `function (ServerRequestInterface $request,
  ResponseInterface $response)`. To dispatch such middleware, point the
  `middleware` "default" for a given route to a service name or callable that
  will resolve to the middleware:

  ```php
  [ 'router' => 'routes' => [
      'path' => [
          'type' => 'Literal',
          'options' => [
              'route' => '/path',
              'defaults' => [
                  'middleware' => 'ServiceNameForPathMiddleware',
              ],
          ],
      ],
  ]
  ```

  This new listener listens at the same priority as the `DispatchListener`, but,
  due to being registered earlier, will invoke first; if the route match does
  not resolve to middleware, it will fall through to the original
  `DispatchListener`, allowing normal ZF2-style controller dispatch.

### Deprecated

- Nothing.

### Removed

- [#36](https://github.com/zendframework/zend-mvc/pull/36) removes
  `Zend\Mvc\Service\ConfigFactory`, as the functionality is now incorporated
  into `Zend\ModuleManager\Listener\ServiceListener`.
- [#36](https://github.com/zendframework/zend-mvc/pull/36) removes
  the `ServiceLocatorAware` intializer, as zend-servicemanager v3 no longer
  defines the interface.
- [#36](https://github.com/zendframework/zend-mvc/pull/36) removes
  `Zend\Mvc\Service\ControllerLoaderFactory` and replaces it with
  `Zend\Mvc\Service\ControllerManagerFactory`.
- [#36](https://github.com/zendframework/zend-mvc/pull/36) removes
  `Zend\Mvc\Service\DiFactory`, `Zend\Mvc\Service\DiAbstractServiceFactory`,
  `Zend\Mvc\Service\DiStrictAbstractServiceFactory`,
  `Zend\Mvc\Service\DiStrictAbstractServiceFactoryFactory`,
  and `Zend\Mvc\Service\DiServiceInitializerFactory`, as zend-servicemanager v3
  removes `Zend\Di` integration.

### Fixed

- [#31](https://github.com/zendframework/zend-mvc/pull/31) updates the component
  to use zend-eventmanager v3.
- [#36](https://github.com/zendframework/zend-mvc/pull/36) updates the component
  to use zend-servicemanager v3, and zend-modulemanager v3. This involves:
  - Updating all factories implementing either `FactoryInterface` or
    `AbstractFactoryInterface` to the new signatures of those interfaces.
  - Updating all plugin managers to the updates to `AbstractPluginManager`.
  - Updating how plugin manager factories work (they're now passed the container
    instance in their constructor arguments, as well as any build options).
  - Added a `RouteInvokableFactory`, which can act as either a
   `FactoryInterface` or `AbstractFactoryInterface` for loading invokable route
   classes, including by fully qualified class name. This is registered as an
   abstract factory by default with the `RoutePluginManager`.
  - The `DispatchListener` now receives the controller manager instance at
    instantiation.
  - The `ViewManager` implementations were updated, and most functionality
    within separated into discrete factories. (Previously these instances
    injected services and aliases into the service manager instance, which is no
    longer possible or desirable with the zend-servicemanager v3 changes.)
  - `Application::init()` now pulls the configured service manager from the
    `Zend\ModuleManager\Listener\ServiceListener` instance before retrieving and
    bootstrapping the `Application` instance; this ensure it is fully
    configured at that time.

## 2.6.1 - TBD

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 2.6.0 - 2015-09-22

### Added

- [#30](https://github.com/zendframework/zend-mvc/pull/30) updates the component
  to use zend-hydrator for hydrator functionality; this provides forward
  compatibility with zend-hydrator, and backwards compatibility with
  hydrators from older versions of zend-stdlib.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 2.5.3 - 2015-09-22

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#29](https://github.com/zendframework/zend-mvc/pull/29) updates the
  zend-stdlib dependency to reference `>=2.5.0,<2.7.0` to ensure hydrators
  will work as expected following extraction of hydrators to the zend-hydrator
  repository.

## 2.5.2 - 2015-09-14

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#27](https://github.com/zendframework/zend-mvc/pull/27) fixes a condition
  where non-view model results from controllers could cause errors to be
  raisedin the `DefaultRenderingStrategy`.
