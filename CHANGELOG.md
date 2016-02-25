# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 2.7.0 - TBD

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

- Two initializers registered by `Zend\Mvc\Service\ServiceManagerConfig` are now
  deprecated, and will be removed starting in version 3.0:
  - `ServiceManagerAwareInitializer`, which injects classes implementing
    `Zend\ServiceManager\ServiceManagerAwareInterface` with the service manager
    instance. Users should create factories for such classes that directly
    inject their dependencies instead.
  - `ServiceLocatorAwareInitializer`, which injects classes implementing
    `Zend\ServiceManager\ServiceLocatorAwareInterface` with the service manager
    instance. Users should create factories for such classes that directly
    inject their dependencies instead.

### Removed

- Nothing.

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

## 2.6.4 - TBD

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 2.6.3 - 2016-02-23

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#74](https://github.com/zendframework/zend-mvc/pull/74) fixes the
  `FormAnnotationBuilderFactory`'s usage of the
  `FormElementManager::injectFactory()` method to ensure it works correctly on
  all versions.

## 2.6.2 - 2016-02-22

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#71](https://github.com/zendframework/zend-mvc/pull/71) fixes the
  `ViewHelperManagerFactory` to be backwards-compatible with v2 by ensuring that
  the factories for each of the `url`, `basepath`, and `doctype` view helpers
  are registered using the fully qualified class names present in
  `Zend\View\HelperPluginManager`; these changes ensure requests for these
  helpers resolve to these override factories, instead of the
  `InvokableFactory`.

## 2.6.1 - 2016-02-16

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#69](https://github.com/zendframework/zend-mvc/pull/69) largely reverts
  [#30](https://github.com/zendframework/zend-mvc/pull/30), having the component
  utilize the `HydratorPluginManager` from zend-stdlib 2.7.5. This was done to
  provide backwards compatibility; while zend-stdlib Hydrator types can be used
  in place of zend-hydrator types, the reverse is not true.

  You can make your code forwards-compatible with version 3, where the
  `HydratorPluginManager` will be pulled from zend-hydrator, by updating your
  typehints to use the zend-hydrator classes instead of those from zend-stdlib;
  the instances returned from the zend-stdlib `HydratorPluginManager`, because
  they extend those from zend-hydrator, remain compatible. 

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
