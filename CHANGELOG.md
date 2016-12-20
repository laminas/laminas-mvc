# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 3.0.4 - 2016-12-20

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#210](https://github.com/zendframework/zend-mvc/pull/210) copies the 
  `RouteMatch` and its parameters to the PSR-7 `ServerRequest` object so that
  they are available to middleware.

## 3.0.3 - 2016-08-29

### Added

- [#198](https://github.com/zendframework/zend-mvc/pull/198) adds a factory for
  the `SendResponseListener`, to ensure that it is injected with an event
  manager instance from the outset; this fixes issues with delegator factories
  that registered listeners with it in previous versions.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#184](https://github.com/zendframework/zend-mvc/pull/184) provides a
  performance optimization for `DELETE` requests to `AbstractRestfulController`
  instances.
- [#187](https://github.com/zendframework/zend-mvc/pull/187) removes a typehint
  for `Exception` from an argument in
  `DispatchListener::marshalControllerNotFoundEvent()`, allowing it to be used
  with PHP 7 `Throwable` instances.

## 3.0.2 - 2016-06-30

### Added

- [#163](https://github.com/zendframework/zend-mvc/pull/163) adds support to the
  `AcceptableViewModelSelector` plugin for controller maps in the `view_manager`
  configuration in the format:

  ```php
  [
      'ControllerClassName' => 'view/name',
  ]
  ```

  This fixes an issue observed when running with Apigility.

- [#163](https://github.com/zendframework/zend-mvc/pull/163) adds support to the
  `InjectTemplateListener` for specifying whether or not to prefer the
  controller matched during routing via routing configuration:

  ```php
  'route-name' => [
      /* ... */
      'options' => [
          /* ... */
          'defaults' => [
              /* ... */
              'prefer_route_match_controller' => true,
          ],
      ],
  ],
  ```

  This allows actions that might otherwise skip injection of the template
  to force the injection.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#161](https://github.com/zendframework/zend-mvc/pull/161) fixes the
  `DispatchListener::marshalBadControllerEvent()` method to allow either
  `Throwable` or `Exception` types for the `$exception` argument.

## 3.0.1 - 2016-06-23

### Added

- [#165](https://github.com/zendframework/zend-mvc/pull/165) adds a new
  controller factory, `LazyControllerAbstractFactory`, that provides a
  Reflection-based approach to instantiating controllers. You may register it
  either as an abstract factory or as a named factory in your controller
  configuration:

  ```php
  'controllers' => [
      'abstract_factories' => [
          'Zend\Mvc\Controller\LazyControllerAbstractFactory`,
      ],
      'factories' => [
          'MyModule\Controller\FooController' => 'Zend\Mvc\Controller\LazyControllerAbstractFactory`,
      ],
  ],
  ```

  The factory uses the typehints to lookup services in the container, using
  aliases for well-known services such as the `FilterManager`,
  `ValidatorManager`, etc. If an `array` typehint is used with a `$config`
  parameter, the `config` service is injected; otherwise, an empty array is
  provided. For all other types, a null value is injected.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 3.0.0 - 2016-05-31

New major version! Please see:

- [doc/book/migration/to-v3-0.md](doc/book/migration/to-v3-0.md)

for full details on how to migrate your v2 application.

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- [#99](https://github.com/zendframework/zend-mvc/pull/99) removes all router
  functionality (everything in the `Zend\Mvc\Router` namespace. This
  functionality is now provided by the [zend-router](https://zendframework.github.io/zend-router/)
  component, which becomes a requirement of zend-mvc. The removal also includes
  all service factories related to routing, as they are provided by zend-router.
- [#99](https://github.com/zendframework/zend-mvc/pull/99) removes all
  console-related functionality, including the `AbstractConsoleController`, the
  `CreateConsoleNotFoundModel` controller plugin, the `ConsoleResponseSender`,
  and all classes under the `Zend\Mvc\View\Console` namespace; these are now
  provided by the [zend-mvc-console](https://zendframework.github.io/zend-mvc-console/)
  component. (That component also includes console-specific routes, which were
  removed from zend-router.) All service factories related to console
  functionality are also now provided by zend-mvc-console.
- [#104](https://github.com/zendframework/zend-mvc/pull/104) removes the `prg()`
  plugin. It can now be installed separately via the
  zendframework/zend-mvc-plugin-prg package.
- [#108](https://github.com/zendframework/zend-mvc/pull/108) removes the
  `fileprg()`, `flashMessenger()`, and `identity()` plugins. These can be
  installed via, respectively, the zendframework/zend-mvc-plugin-fileprg,
  zendframework/zend-mvc-plugin-flashmessenger, and
  zendframework/zend-mvc-plugin-identity packages.
- [#110](https://github.com/zendframework/zend-mvc/pull/110) removes the
  internationalization functionality from the component, including factories for
  the translator and translator loader manager. This functionality is
  now provided by the [zend-i18n](https://zendframework.github.io/zend-i18n/)
  and [zend-mvc-i18n](https://zendframework.github.io/zend-mvc-i18n/) packages;
  installing `zendframework/zend-mvc-i18n` will restore i18n functionality in
  your application.
- [#115](https://github.com/zendframework/zend-mvc/pull/115) removes the
  requirement for zend-filter in the `InjectTemplateListener` by inlining the
  logic from `Zend\Filter\Word\CamelCaseToDash`.
- [#116](https://github.com/zendframework/zend-mvc/pull/116) removes the
  functionality related to integrating zend-servicemanager and zend-di. If you
  used this functionality previously, it is now available via a separate
  package, [zend-servicemanager-di](https://zendframework.github.io/zend-servicemanager-di/]).
- [#117](https://github.com/zendframework/zend-mvc/pull/117) removes the
  functionality related to exposing and configuring the zend-filter
  `FilterPluginManager`. That functionality is now exposed directly by the
  zend-filter component.
- [#118](https://github.com/zendframework/zend-mvc/pull/118) removes the
  functionality related to exposing and configuring the zend-validator
  `ValidatorPluginManager`. That functionality is now exposed directly by the
  zend-validator component.
- [#119](https://github.com/zendframework/zend-mvc/pull/119) removes the
  functionality related to exposing and configuring the zend-serializer
  `SerializerAdapterManager`. That functionality is now exposed directly by the
  zend-serializer component.
- [#120](https://github.com/zendframework/zend-mvc/pull/120) removes the
  functionality related to exposing and configuring the zend-hydrator
  `HydratorManager`. That functionality is now exposed directly by the
  zend-hydrator component.
- [#54](https://github.com/zendframework/zend-mvc/pull/54) removes the
  `$configuration` argument (first required argument) from the
  `Zend\Mvc\Application` constructor. If you were directly instantiating an
  `Application` instance previously (whether in your bootstrap, a factory, or
  tests), you will need to update how you instantiate the instance. (The
  argument was removed as the value was never used.)
- [#121](https://github.com/zendframework/zend-mvc/pull/121) removes the
  functionality related to exposing and configuring the zend-log
  `ProcessorPluginManager` and `WriterPluginManager`. That functionality is now
  exposed directly by the zend-log component (with the addition of exposing the
  `FilterPluginManager` and `FormatterPluginManager` as well).
- [#123](https://github.com/zendframework/zend-mvc/pull/123) removes the
  functionality related to exposing and configuring the zend-inputfilter
  `InputFilterManager`. That functionality is now exposed directly by the
  zend-inputfilter component.
- [#124](https://github.com/zendframework/zend-mvc/pull/124) removes the
  functionality related to exposing and configuring zend-form, including the
  `FormElementManager`, `FormAnnotationBuilder`, and the
  `FormAbstractServiceFactory`. The functionality is now exposed directly by the
  zend-form component.
- [#125](https://github.com/zendframework/zend-mvc/pull/125) removes the
  functionality from the `ViewHelperManager` factory for fetching configuration
  classes from other components and using them to configure the instance. In all
  cases, this is now done by the components themselves.
- [#128](https://github.com/zendframework/zend-mvc/pull/128) removes the
  `ControllerLoaderFactory`, and the `ControllerLoader` service alias; use
  `ControllerManagerFactory` and `ControllerManager`, respectively, instead.
- [#128](https://github.com/zendframework/zend-mvc/pull/128) removes
  `Zend\Mvc\View\SendResponseListener`; use `Zend\Mvc\SendResponseListener`
  instead.
- [#128](https://github.com/zendframework/zend-mvc/pull/128) removes
  `Application::send()`, which has been a no-op since 2.2.
- [#128](https://github.com/zendframework/zend-mvc/pull/128) removes
  `DispatchListener::marshallControllerNotFoundEvent()`, which has proxied to
  `marshalControllerNotFoundEvent()` since 2.2.
- [#128](https://github.com/zendframework/zend-mvc/pull/128) removes
  the `ServiceLocatorAwareInterface` implementation
  (`setServiceLocator()`/`getServiceLocator()` methods) from
  `AbstractController`. You will need to inject your dependencies specifically
  going forward.
- [#128](https://github.com/zendframework/zend-mvc/pull/128) removes
  the `ServiceLocatorAwareInterface` initializers defined in
  `Zend\Mvc\Service\ServiceManagerConfig` and
  `Zend\Mvc\Controller\ControllerManager`. You will need to inject your
  dependencies specifically going forward.
- [#139](https://github.com/zendframework/zend-mvc/pull/139) removes support for
  pseudo-module template resolution using the `__NAMESPACE__` routing
  configuration option, as it often led to conflicts when multiple modules
  shared a common top-level namespace. Auto-resolution now always takes into
  account the full namespace (minus the `Controller` segment).

### Fixed

- [#113](https://github.com/zendframework/zend-mvc/pull/113) updates
  `AbstractRestfulController` to make usage of zend-json for deserializing JSON
  requests optional. `json_decode()` is now used by default, falling back to
  `Zend\Json\Json::decode()` if it is available. If neither are available, an
  exception is now thrown.
- [#115](https://github.com/zendframework/zend-mvc/pull/115) and
  [#128](https://github.com/zendframework/zend-mvc/pull/128) update the
  dependency list, per https://github.com/zendframework/maintainers/wiki/zend-mvc-v3-refactor:-reduce-components#required-components,
  to do the following:
  - Makes the following components required:
    - zend-http
    - zend-modulemanager
    - zend-router
    - zend-view
  - Makes the following components optional:
    - zend-json
    - zend-psr7bridge
  - And pares the suggestion list down to:
    - zend-mvc-console
    - zend-mvc-i18n
    - zend-mvc-plugin-fileprg
    - zend-mvc-plugin-flashmessenger
    - zend-mvc-plugin-identity
    - zend-mvc-plugin-prg
    - zend-servicemanager-di
- [#128](https://github.com/zendframework/zend-mvc/pull/128) bumps the minimum
  supported version of zend-eventmanager, zend-servicemanager, and zend-stdlib
  to their v3 releases.
- [#128](https://github.com/zendframework/zend-mvc/pull/128) bumps the minimum
  supported PHP version to 5.6.

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
