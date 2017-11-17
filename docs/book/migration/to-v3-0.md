# Upgrading to 3.0

With the release of Zend Framework 2, all components current at that time,
regardless of history, were tagged as v2 releases; in reality, it was the first
version of a new framework, as it was a completely new architecture from Zend
Framework 1. As such, zend-mvc 3.0 marks the second major release of the
component.

The primary goal for version 3 was to reduce the number of dependencies, and to
split out unrelated, tangential, or extension functionality. As such, there are
a number of changes that will impact users.

## Dependency reduction

In order to remove dependencies, we needed to provide alternate ways to ensure
that default functionality, such as service registration and event listener
attachment, could still occur.

The solution to this problem was to expose each component as a module. This
however, raised another problem: you now have to register components as modules
in your application.

To solve this new problem, we created a new component,
[zend-component-installer](http://docs.zendframework.com/zend-component-installer/).
Install this in your application now:

```bash
$ composer require --dev zendframework/zend-component-installer
```

Once installed, the component acts as a Composer plugin, and will intercept
packages marked as components or Zend Framework modules, and register them with
your application configuration. Components are pushed to the top of the module
list, while modules are pushed to the end. As a development component, it will
not be installed in your production distributions.

## Updated dependencies

The v3 release now *requires*:

- zend-http
- zend-modulemanager
- zend-router
- zend-view

Additionally, the following components require their v3 releases:

- zend-eventmanager
- zend-servicemanager
- zend-stdlib

The minimum supported PHP version was bumped to 5.6.

## Application class

The following changes were made to the `Zend\Mvc\Application` constructor:

- The first `$configuration` argument was removed, as it was not used.
- Three additional, optional arguments were added:
  - `Zend\EventManager\EventManagerInterface $events = null`
  - `Zend\Stdlib\RequestInterface $request = null`
  - `Zend\Stdlib\ResponseInterface $response = null`

End-users using the skeleton application and the default `Application` factory
will not notice a change. Those who are directly instantiating the `Application`
instance (in production or test code) or who have created their own factory for
the class will need to update their code.

### send method

The `send()` method has been deprecated since the 2.2 release, and a no-op since
then as well. It is removed starting with the v3 release.

## ControllerLoader

The `ControllerLoader` service was deprecated early in the v2 lifecycle, and
aliased to `ControllerManager`. The `ControllerLoader` factory was kept to
prevent BC breaks due to extending the class.

v3 removes the `ControllerLoaderFactory`, as well as the `ControllerLoader`
service alias.

## DI-ServiceManager integration

The integration between [zend-servicemanager](https://docs.zendframework.com/zend-servicemanager) and
[zend-di](https://github.com/zendframework/zend-di) has been moved to a new
standalone component, [zend-servicemanager-di](https://docs.zendframework.com/zend-servicemanager-di/).
In most cases, installing the component will restore the original behavior:

```bash
$ composer require zendframework/zend-servicemanager-di
```

> ### Manual installation
>
> The above assumes you're using the new component installer detailed in the
> [dependency reduction](#dependency-reduction) section, above. If you are not,
> you will need to inject the zend-servicemanager-di module into your
> application manually; follow the [instructions in the zend-servicemanager-di documentation](https://docs.zendframework.com/zend-servicemanager-di/)
> to do so.

The new component also contains a [migration document](https://docs.zendframework.com/zend-servicemanager-di/migration/v2-to-v3/)
detailing potential issues for users migrating to version 3.

## DispatchListener

The `marshallControllerNotFoundEvent()` method was deprecated early in the ZF2
lifecycle, and has proxied to `marshalControllerNotFoundEvent()`. It is removed
with the v3 release.

## Routing

Routing was removed from zend-mvc, and moved to a new component,
[zend-router](https://docs.zendframework.com/zend-router/), which is now a
dependency of zend-mvc.

The changes that will impact users are:

- [Query route removal](http://docs.zendframework.com/zend-router/migration/v2-to-v3/#query-route-removal);
  this route had been deprecated since 2.3.0, and removed for the 3.0 release.
- [Namespace changes](http://docs.zendframework.com/zend-router/migration/v2-to-v3/#namespace-change);
  with the separation to the zend-router component, all routes changed
  namespaces from `Zend\Mvc\Router` to `Zend\Router`.

Follow the links above for more details on these changes, and how to migrate
your code.

## Console tooling

Console tooling, including console routes, were split off to a new component,
[zend-mvc-console](https://docs.zendframework.com/zend-mvc-console/). If you
were using the console tooling, install zend-mvc-console:

```bash
$ composer require zendframework/zend-mvc-console
```

(Make sure you've already installed zend-component-installer before you do, to
ensure the component is registered with your application!)

zend-mvc-console exposes all of the same functionality as was in the v2 series
of zend-mvc, but most components are in different namespaces. Please read the
[zend-mvc-console migration guide](http://docs.zendframework.com/zend-mvc-console/migration/v2-to-v3/)
for full details of what changes you may need to make to your application to
ensure console tooling continues to work.

> ### Migrate your console tooling
>
> Due to the amount of integration required to support console tooling via the
> MVC, we do not plan on supporting zend-mvc-console long-term. As such, we
> recommend migrating your code to use standalone tools such as
> [zf-console](https://github.com/zfcampus/zf-console) or
> [Aura.Cli](https://github.com/auraphp/Aura.Cli).

## Filter integration

In version 2, zend-mvc exposed a `FilterManager` service by default, and
provided specifications to zend-modulemanager's `ServiceListener`
to allow modules to provide filter configuration.

This functionality is now removed from zend-mvc. It is now exposed directly by
the [zend-filter](https://docs.zendframework.com/zend-filter/) component
itself. To add it, install zend-filter:

```bash
$ composer require zendframework/zend-filter
```

Note: the above assumes you have already installed zend-component-installer, per
the section above on [dependency reduction](#dependency-reduction).

## Form integration

In version 2, zend-mvc exposed several facilities related to zend-form:

- `FormElementManager` mapped to a factory in zend-mvc, but created a
  `Zend\Form\FormElementManager` instance.
- `FormAnnotationBuilder` mapped to a factory in zend-mvc, but created a
  `Zend\Form\Annotation\AnnotationBuilder` instance.
- The `ServiceListenerFactory` registered `Zend\Form\FormAbstractServiceFactory`
  as an abstract factory.
- The `ModuleManagerFactory` registered specifications with the
  zend-modulemanager `ServiceListener` to allow modules to provide form element
  configuration.

The above functionality is now removed from zend-mvc, and exposed directly by
the [zend-form](https://github.com/zendframework/zend-form) component. To
add/enable it, install zend-form:

```bash
$ composer require zendframework/zend-form
```

Note: the above assumes you have already installed zend-component-installer, per
the section above on [dependency reduction](#dependency-reduction).

## Hydrator integration

In version 2, zend-mvc exposed a `HydratorManager` service by default, and
provided specifications to zend-modulemanager's `ServiceListener`
to allow modules to provide hydrator configuration.

This functionality is now removed from zend-mvc. It is now exposed directly by
the [zend-hydrator](https://docs.zendframework.com/zend-hydrator/) component
itself. To add it, install zend-hydrator:

```bash
$ composer require zendframework/zend-hydrator
```

Note: the above assumes you have already installed zend-component-installer, per
the section above on [dependency reduction](#dependency-reduction).

## InputFilter integration

In version 2, zend-mvc exposed a `InputFilterManager` service by default, and
provided specifications to zend-modulemanager's `ServiceListener`
to allow modules to provide validator configuration.

This functionality is now removed from zend-mvc. It is now exposed directly by
the [zend-inputfilter](https://docs.zendframework.com/zend-inputfilter/) component
itself. To add it, install zend-inputfilter:

```bash
$ composer require zendframework/zend-inputfilter
```

Note: the above assumes you have already installed zend-component-installer, per
the section above on [dependency reduction](#dependency-reduction).

zend-inputfilter now also exposes the `InputFilterAbstractServiceFactory` as an
abstract factory by default.

## i18n integration

Internationalization tooling, including:

- the integration translator (`MvcTranslator` service)
- the "dummy" translator
- the `TranslatorAwareTreeRouteStack` implementation
- factories for the translator and translator loader managers

were removed, and re-assigned to the [zend-i18n](https://docs.zendframework.com/zend-i18n/)
and [zend-mvc-i18n](https://docs.zendframework.com/zend-mvc-i18n/) packages.
In most cases, you can install `zendframework/zend-mvc-i18n` to restore i18n
functionality to your application:

```bash
$ composer require zendframework/zend-mvc-i18n
```

There are two categories of changes that could affect you on upgrading.

First, if you were using the `TranslatorAwareTreeRouteStack`, the class name has
changed from `Zend\Mvc\Router\Http\TranslatorAwareTreeRouteStack` to
`Zend\Mvc\I18n\Router\TranslatorAwareTreeRouteStack`; updating your code to
reflect that will allow it to work again.

Second, if you were extending one of the service factories for either the
`MvcTranslator` or the `TranslatorPluginManager`, the namespaces for the
factories have changed. In such situations, you have two options:

- Update your extensions to extend the new classes. See the [zend-mvc-i18n
  migration guide](https://docs.zendframework.com/zend-mvc-i18n/migration/v2-to-v3/)
  to determine what names have changed.
- Instead of extending, consider using [delegator factories](https://docs.zendframework.com/zend-servicemanager/delegators/),
  as these decorate the service factory, regardless of what factory is used.

## Log integration

In version 2, zend-mvc exposed `LogProcessorManager` and `LogWriterManager`
services by default, and provided specifications to zend-modulemanager's
`ServiceListener` to allow modules to provide configuration for each.

This functionality is now removed from zend-mvc. It is now exposed directly by
the [zend-log](https://docs.zendframework.com/zend-log/) component
itself. To add it, install zend-log:

```bash
$ composer require zendframework/zend-log
```

Note: the above assumes you have already installed zend-component-installer, per
the section above on [dependency reduction](#dependency-reduction).

zend-log now also exposes `LogFilterManager` and `LogFormatterManager`,
corresponding to the following:

Service | Config Key | Provider Interface | Provider Method
------- | ---------- | ------------------ | ---------------
LogFilterManager | `log_filters` | `Zend\Log\Filter\LogFilterProviderInterface` | `getLogFilterConfig()`
LogFormatterManager | `log_formatters` | `Zend\Log\Formatter\LogFormatterProviderInterface` | `getLogFormatterConfig()`

This additions allow you to provide additional plugins for every aspect zend-log
exposes.

## Plugins

The following plugins have been removed from the main zend-mvc repository, and
into their own standalone repositories. In all cases, please be sure to install
the [component installer as detailed above](#dependency-reduction) before
installing the plugins, to automate injection into your application
configuration.

### fileprg()

The `fileprg()` plugin is now provided via the
[zend-mvc-plugin-fileprg](https://github.com/zendframework/zend-mvc-plugin-fileprg)
component.

```bash
$ composer require zendframework/zend-mvc-plugin-fileprg
```

`Zend\Mvc\Controller\Plugin\FilePostRedirectGet` becomes
`Zend\Mvc\Plugin\FilePrg\FilePostRedirectGet`. However, it is still mapped as
`fileprg()`.

### flashMessenger()

The `flashMessenger()` plugin is now provided via the
[zend-mvc-plugin-flashmessenger](https://github.com/zendframework/zend-mvc-plugin-flashmessenger)
component.

```bash
$ composer require zendframework/zend-mvc-plugin-flashmessenger
```

`Zend\Mvc\Controller\Plugin\FlashMessenger` becomes
`Zend\Mvc\Plugin\FlashMessenger\FlashMessenger`. However, it is still mapped as
`flashMessenger()` and `flashmessenger()`.

### identity()

The `identity()` plugin is now provided via the
[zend-mvc-plugin-identity](https://github.com/zendframework/zend-mvc-plugin-identity)
component.

```bash
$ composer require zendframework/zend-mvc-plugin-identity
```

`Zend\Mvc\Controller\Plugin\Identity` becomes
`Zend\Mvc\Plugin\Identity\Identity`. However, it is still mapped as
`identity()`.

Additionally, `Zend\Mvc\Controller\Plugin\Service\IdentityFactory` now becomes
`Zend\Mvc\Plugin\Identity\IdentityFactory`.

### prg()

The `prg()` plugin is now provided via the
[zend-mvc-plugin-prg](https://github.com/zendframework/zend-mvc-plugin-prg)
component.

```bash
$ composer require zendframework/zend-mvc-plugin-prg
```

`Zend\Mvc\Controller\Plugin\PostRedirectGet` becomes
`Zend\Mvc\Plugin\Prg\PostRedirectGet`. However, it is still mapped as `prg()`.

## Serializer integration

In version 2, zend-mvc exposed a `SerializerAdapterManager` service by default, and
provided specifications to zend-modulemanager's `ServiceListener`
to allow modules to provide serializer configuration.

This functionality is now removed from zend-mvc. It is now exposed directly by
the [zend-serializer](https://github.com/zendframework/zend-serializer) component
itself. To add it, install zend-serializer

```bash
$ composer require zendframework/zend-serializer
```

Note: the above assumes you have already installed zend-component-installer, per
the section above on [dependency reduction](#dependency-reduction).

## ServiceLocatorAware initializers

Starting with zend-servicemanager v3, that component no longer defines the
`ServiceLocatorAwareInterface`. Since zend-mvc pins against zend-servicemanager
v3 with its own v3 release, the initializers that injected the application
container into implementations of that interface are no longer relevant. As
such, they have now been removed from each of the
`Zend\Mvc\Service\ServiceManagerConfig` and
`Zend\Mvc\Controller\ControllerManager` classes.

Additionally, the duck-typed `ServiceLocatorAwareInterface` implementation in
`AbstractController` was removed, as messaged in the 2.7 release.

If you relied on this functionality, you are encouraged to update your code to
use factories to inject your *actual* dependencies.

## Validator integration

In version 2, zend-mvc exposed a `ValidatorManager` service by default, and
provided specifications to zend-modulemanager's `ServiceListener`
to allow modules to provide validator configuration.

This functionality is now removed from zend-mvc. It is now exposed directly by
the [zend-validator](https://docs.zendframework.com/zend-validator/) component
itself. To add it, install zend-validator:

```bash
$ composer require zendframework/zend-validator
```

Note: the above assumes you have already installed zend-component-installer, per
the section above on [dependency reduction](#dependency-reduction).

## Zend\Mvc\View\InjectTemplateListener

The `InjectTemplateListener` attempts to map a controller name to a
template using a variety of heuristics, including an explicit map provided
during configuration, or auto-detection based on the controller class name.

In version 2, the autodetection took into consideration the `__NAMESPACE__`
route match parameter to derive subnamespaces, or would omit them completely if
`__NAMESPACE__` was not present. This caused issues when multiple modules shared
a top-level namespace (e.g., `ZF\Apigility` and `ZF\Apigility\Admin`) and each
had a controller with the same name.

To avoid naming conflicts, version 3 removes this aspect of autodetection, and
instead provides exactly one workflow for mapping:

- Strip the `Controller` subnamespace, if present (e.g., the namespace
  `Application\Controller\\` is normalized to `Application\\`).
- Strip the `Controller` suffix in the class name, if present (e.g.,
  `IndexController` is normalized to `Index`).
- Inflect CamelCasing to dash-separated (e.g., `ShowUsers` becomes
  `show-users`).
- Replace the namespace separator with a slash.

As a full example, the controller service name
`TestSomething\With\Controller\CamelCaseController` will always map to
`test-something/with/camel-case`, regardless of the `__NAMESPACE__` value
provided in routing configuration.

If needed, you can emulate the version 2 behavior in version 3 via namespace
whitelisting in the controller &lt;=&gt; template map.

## Zend\Mvc\View\SendResponseListener

`Zend\Mvc\View\SendResponseListener` was deprecated with the 2.2 release, and
has been an extension of `Zend\Mvc\SendResponseListener` ever since. It is
removed with the v3 release.
