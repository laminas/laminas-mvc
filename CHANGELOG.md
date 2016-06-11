# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 2.7.9 - 2016-06-11

### Added

- [#149](https://github.com/zendframework/zend-mvc/pull/149) and
  [#158](https://github.com/zendframework/zend-mvc/pull/158) add a dependency
  on zendframework/zend-servicemanager-di in order to provide both backwards and
  forwards compatibility for the DI/ServiceManager integration.

### Deprecated

- [#158](https://github.com/zendframework/zend-mvc/pull/158) deprecates each of
  the following classes, which now have equivalents in the
  zend-servicemanager-di package (which is required by zend-mvc v2, but optional
  starting with zend-mvc v3):
  - `Zend\Mvc\Service\DiAbstractServiceFactoryFactory`
  - `Zend\Mvc\Service\DiFactory`
  - `Zend\Mvc\Service\DiServiceInitializerFactory`
  - `Zend\Mvc\Service\DiStrictAbstractServiceFactory`
  - `Zend\Mvc\Service\DiStrictAbstractServiceFactoryFactory`
- [#152](https://github.com/zendframework/zend-mvc/pull/152) formally marks the
  `ControllerLoaderFactory` as deprecated via annotation (though it has been
  noted as such in the documentation for several years). Use
  `Zend\Mvc\Service\ControllerManagerFactory` instead.

### Removed

- Nothing.

### Fixed

- [#149](https://github.com/zendframework/zend-mvc/pull/149) and
  [#158](https://github.com/zendframework/zend-mvc/pull/158) fix an "undefined
  variable" issue with `Zend\Mvc\Service\DiAbstractServiceFactoryFactory`.
- [#153](https://github.com/zendframework/zend-mvc/pull/153) removes the
  typehint from the `$exception` argument of `DispatchListener::marshalBadControllerEvent()`,
  fixing an issue when PHP 7 Error types are caught and passed to the method.

## 2.7.8 - 2016-05-31

### Added

- [#138](https://github.com/zendframework/zend-mvc/pull/138) adds support for
  PHP 7 `Throwable`s within each of:
  - `DispatchListener`
  - `MiddlewareListener`
  - The console `RouteNotFoundStrategy` and `ExceptionStrategy`
  - The HTTP `DefaultRenderingStrategy` and `RouteNotFoundStrategy`

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 2.7.7 - 2016-04-12

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#122](https://github.com/zendframework/zend-mvc/pull/122) fixes the
  `FormAnnotationBuilderFactory` to use the container's `get()` method instead
  of `build()` to retrieve the event manager instance.

## 2.7.6 - 2016-04-06

### Added

- [#94](https://github.com/zendframework/zend-mvc/pull/94) adds a documentation
  recipe for using middleware withing MVC event listeners.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#107](https://github.com/zendframework/zend-mvc/pull/107) fixes an incorrect
  import statement in the `DiStrictAbstractServiceFactoryFactory` that prevented
  it from working.
- [#112](https://github.com/zendframework/zend-mvc/pull/112) fixes how the
  `Forward` plugin detects and detaches event listeners to ensure it works
  against either v2 or v3 releases of zend-eventmanager.

## 2.7.5 - 2016-04-06

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#111](https://github.com/zendframework/zend-mvc/pull/111) fixes a bug in how
  the `ConsoleExceptionStrategyFactory` whereby it was overwriting the default
  exception message template with an empty string when no configuration for it
  was provided.

## 2.7.4 - 2016-04-03

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#114](https://github.com/zendframework/zend-mvc/pull/114) fixes an issue in
  the `ServiceLocatorAware` initializer whereby plugin manager instances were
  falsely identified as the container instance when under zend-servicemanager v2.

## 2.7.3 - 2016-03-08

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#97](https://github.com/zendframework/zend-mvc/pull/97) re-introduces the
  `ServiceManager` factory definition inside `ServiceManagerConfig`, to ensure
  backwards compatibility.

## 2.7.2 - 2016-03-08

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#95](https://github.com/zendframework/zend-mvc/pull/95) re-introduces the
  various zend-di aliases and factories in `Zend\Mvc\Service\ServiceListenerFactory`,
  which were accidently removed in the 2.7.0 release.
- [#96](https://github.com/zendframework/zend-mvc/pull/96) fixes shared event
  detachment/attachment within the `Forward` plugin to work with both v2 and v3
  of zend-eventmanager.
- [#93](https://github.com/zendframework/zend-mvc/pull/93) ensures that the
  Console `Catchall` route factory will not fail when the `defaults` `$options`
  array key is missing.
- [#43](https://github.com/zendframework/zend-mvc/pull/43) updates the
  `AbstractRestfulController` to ensure it can accept textual (e.g., XML, YAML)
  data.
- [#79](https://github.com/zendframework/zend-mvc/pull/79) updates the
  continuous integration configuration to ensure we test against lowest and
  highest accepted dependencies, and those in the current lockfile.

## 2.7.1 - 2016-03-02

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#88](https://github.com/zendframework/zend-mvc/pull/88) addresses backwards
  compatibility concerns raised by users due to the new deprecation notices
  emitted by `ServiceLocatorAware` initializers; in particular, all
  `AbstractController` implementations were raising a deprecation wen first
  pulled from the `ControllerManager`.
  
  At this time, notices are now only raised in the following conditions:

  - When a non-controller, non-plugin manager, `ServiceLocatorAware` instance
    is detected.
  - When a plugin manager instance is detected that is `ServiceLocatorAware` and
    does not have a composed service locator. In this situation, the deprecation
    notice indicates that the factory for the plugin manager should be updated
    to inject the service locator via the constructor.
  - For controllers that do not extend `AbstractController` but do implement
    `ServiceLocatorAware`.
  - When calling `getServiceLocator()` from within an `AbstractController`
    extension; this properly calls out the practice that should be avoided and
    which requires updates to the controller.

## 2.7.0 - 2016-03-01

### Added

- [#31](https://github.com/zendframework/zend-mvc/pull/31) adds three new
  optional arguments to the `Zend\Mvc\Application` constructor: an EventManager
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
- [#84](https://github.com/zendframework/zend-mvc/pull/84) publishes the
  documentation to https://zendframework.github.io/zend-mvc/

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

- `Zend\Mvc\Controller\AbstractController` no longer directly implements
  `Zend\ServiceManager\ServiceLocatorAwareInterface`, but still implements the
  methods defined in that interface. This was done to provide
  forwards-compatibility, as zend-servicemanager v3 no longer defines the
  interface. All initializers that do `ServiceLocatorInterface` injection were
  updated to also inject when just the methods are present.

### Fixed

- [#31](https://github.com/zendframework/zend-mvc/pull/31) and
  [#76](https://github.com/zendframework/zend-mvc/pull/76) update the component
  to be forwards-compatible with zend-eventmanager v3.
- [#36](https://github.com/zendframework/zend-mvc/pull/36),
  [#76](https://github.com/zendframework/zend-mvc/pull/76),
  [#80](https://github.com/zendframework/zend-mvc/pull/80),
  [#81](https://github.com/zendframework/zend-mvc/pull/81), and
  [#82](https://github.com/zendframework/zend-mvc/pull/82) update the component
  to be forwards-compatible with zend-servicemanager v3. Several changes were
  introduced to support this effort:
  - Added a `RouteInvokableFactory`, which can act as either a
    `FactoryInterface` or `AbstractFactoryInterface` for loading invokable route
    classes, including by fully qualified class name. This is registered as an
    abstract factory by default with the `RoutePluginManager`.
  - The `DispatchListener` now receives the controller manager instance at
    instantiation.
  - The `ViewManager` implementations were updated, and most functionality
    within separated into discrete factories.

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
