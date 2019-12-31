# Upgrading to 3.0

With the release of Laminas, all components current at that time,
regardless of history, were tagged as v2 releases; in reality, it was the first
version of a new framework, as it was a completely new architecture from Laminas
Framework 1. As such, laminas-mvc 3.0 marks the second major release of the
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
[laminas-component-installer](http://docs.laminas.dev/laminas-component-installer/).
Install this in your application now:

```bash
$ composer require --dev laminas/laminas-component-installer
```

Once installed, the component acts as a Composer plugin, and will intercept
packages marked as components or Laminas modules, and register them with
your application configuration. Components are pushed to the top of the module
list, while modules are pushed to the end. As a development component, it will
not be installed in your production distributions.

## Updated dependencies

The v3 release now *requires*:

- laminas-http
- laminas-modulemanager
- laminas-router
- laminas-view

Additionally, the following components require their v3 releases:

- laminas-eventmanager
- laminas-servicemanager
- laminas-stdlib

The minimum supported PHP version was bumped to 5.6.

## Application class

The following changes were made to the `Laminas\Mvc\Application` constructor:

- The first `$configuration` argument was removed, as it was not used.
- Three additional, optional arguments were added:
  - `Laminas\EventManager\EventManagerInterface $events = null`
  - `Laminas\Stdlib\RequestInterface $request = null`
  - `Laminas\Stdlib\ResponseInterface $response = null`

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

The integration between [laminas-servicemanager](https://docs.laminas.dev/laminas-servicemanager) and
[laminas-di](https://github.com/laminas/laminas-di) has been moved to a new
standalone component, [laminas-servicemanager-di](https://docs.laminas.dev/laminas-servicemanager-di/).
In most cases, installing the component will restore the original behavior:

```bash
$ composer require laminas/laminas-servicemanager-di
```

> ### Manual installation
>
> The above assumes you're using the new component installer detailed in the
> [dependency reduction](#dependency-reduction) section, above. If you are not,
> you will need to inject the laminas-servicemanager-di module into your
> application manually; follow the [instructions in the laminas-servicemanager-di documentation](https://docs.laminas.dev/laminas-servicemanager-di/)
> to do so.

The new component also contains a [migration document](https://docs.laminas.dev/laminas-servicemanager-di/migration/v2-to-v3/)
detailing potential issues for users migrating to version 3.

## DispatchListener

The `marshallControllerNotFoundEvent()` method was deprecated early in the Laminas
lifecycle, and has proxied to `marshalControllerNotFoundEvent()`. It is removed
with the v3 release.

## Routing

Routing was removed from laminas-mvc, and moved to a new component,
[laminas-router](https://docs.laminas.dev/laminas-router/), which is now a
dependency of laminas-mvc.

The changes that will impact users are:

- [Query route removal](http://docs.laminas.dev/laminas-router/migration/v2-to-v3/#query-route-removal);
  this route had been deprecated since 2.3.0, and removed for the 3.0 release.
- [Namespace changes](http://docs.laminas.dev/laminas-router/migration/v2-to-v3/#namespace-change);
  with the separation to the laminas-router component, all routes changed
  namespaces from `Laminas\Mvc\Router` to `Laminas\Router`.

Follow the links above for more details on these changes, and how to migrate
your code.

## Console tooling

Console tooling, including console routes, were split off to a new component,
[laminas-mvc-console](https://docs.laminas.dev/laminas-mvc-console/). If you
were using the console tooling, install laminas-mvc-console:

```bash
$ composer require laminas/laminas-mvc-console
```

(Make sure you've already installed laminas-component-installer before you do, to
ensure the component is registered with your application!)

laminas-mvc-console exposes all of the same functionality as was in the v2 series
of laminas-mvc, but most components are in different namespaces. Please read the
[laminas-mvc-console migration guide](http://docs.laminas.dev/laminas-mvc-console/migration/v2-to-v3/)
for full details of what changes you may need to make to your application to
ensure console tooling continues to work.

> ### Migrate your console tooling
>
> Due to the amount of integration required to support console tooling via the
> MVC, we do not plan on supporting laminas-mvc-console long-term. As such, we
> recommend migrating your code to use standalone tools such as
> [zf-console](https://github.com/zfcampus/zf-console) or
> [Aura.Cli](https://github.com/auraphp/Aura.Cli).

## Filter integration

In version 2, laminas-mvc exposed a `FilterManager` service by default, and
provided specifications to laminas-modulemanager's `ServiceListener`
to allow modules to provide filter configuration.

This functionality is now removed from laminas-mvc. It is now exposed directly by
the [laminas-filter](https://docs.laminas.dev/laminas-filter/) component
itself. To add it, install laminas-filter:

```bash
$ composer require laminas/laminas-filter
```

Note: the above assumes you have already installed laminas-component-installer, per
the section above on [dependency reduction](#dependency-reduction).

## Form integration

In version 2, laminas-mvc exposed several facilities related to laminas-form:

- `FormElementManager` mapped to a factory in laminas-mvc, but created a
  `Laminas\Form\FormElementManager` instance.
- `FormAnnotationBuilder` mapped to a factory in laminas-mvc, but created a
  `Laminas\Form\Annotation\AnnotationBuilder` instance.
- The `ServiceListenerFactory` registered `Laminas\Form\FormAbstractServiceFactory`
  as an abstract factory.
- The `ModuleManagerFactory` registered specifications with the
  laminas-modulemanager `ServiceListener` to allow modules to provide form element
  configuration.

The above functionality is now removed from laminas-mvc, and exposed directly by
the [laminas-form](https://github.com/laminas/laminas-form) component. To
add/enable it, install laminas-form:

```bash
$ composer require laminas/laminas-form
```

Note: the above assumes you have already installed laminas-component-installer, per
the section above on [dependency reduction](#dependency-reduction).

## Hydrator integration

In version 2, laminas-mvc exposed a `HydratorManager` service by default, and
provided specifications to laminas-modulemanager's `ServiceListener`
to allow modules to provide hydrator configuration.

This functionality is now removed from laminas-mvc. It is now exposed directly by
the [laminas-hydrator](https://docs.laminas.dev/laminas-hydrator/) component
itself. To add it, install laminas-hydrator:

```bash
$ composer require laminas/laminas-hydrator
```

Note: the above assumes you have already installed laminas-component-installer, per
the section above on [dependency reduction](#dependency-reduction).

## InputFilter integration

In version 2, laminas-mvc exposed a `InputFilterManager` service by default, and
provided specifications to laminas-modulemanager's `ServiceListener`
to allow modules to provide validator configuration.

This functionality is now removed from laminas-mvc. It is now exposed directly by
the [laminas-inputfilter](https://docs.laminas.dev/laminas-inputfilter/) component
itself. To add it, install laminas-inputfilter:

```bash
$ composer require laminas/laminas-inputfilter
```

Note: the above assumes you have already installed laminas-component-installer, per
the section above on [dependency reduction](#dependency-reduction).

laminas-inputfilter now also exposes the `InputFilterAbstractServiceFactory` as an
abstract factory by default.

## i18n integration

Internationalization tooling, including:

- the integration translator (`MvcTranslator` service)
- the "dummy" translator
- the `TranslatorAwareTreeRouteStack` implementation
- factories for the translator and translator loader managers

were removed, and re-assigned to the [laminas-i18n](https://docs.laminas.dev/laminas-i18n/)
and [laminas-mvc-i18n](https://docs.laminas.dev/laminas-mvc-i18n/) packages.
In most cases, you can install `laminas/laminas-mvc-i18n` to restore i18n
functionality to your application:

```bash
$ composer require laminas/laminas-mvc-i18n
```

There are two categories of changes that could affect you on upgrading.

First, if you were using the `TranslatorAwareTreeRouteStack`, the class name has
changed from `Laminas\Mvc\Router\Http\TranslatorAwareTreeRouteStack` to
`Laminas\Mvc\I18n\Router\TranslatorAwareTreeRouteStack`; updating your code to
reflect that will allow it to work again.

Second, if you were extending one of the service factories for either the
`MvcTranslator` or the `TranslatorPluginManager`, the namespaces for the
factories have changed. In such situations, you have two options:

- Update your extensions to extend the new classes. See the [laminas-mvc-i18n
  migration guide](https://docs.laminas.dev/laminas-mvc-i18n/migration/v2-to-v3/)
  to determine what names have changed.
- Instead of extending, consider using [delegator factories](https://docs.laminas.dev/laminas-servicemanager/delegators/),
  as these decorate the service factory, regardless of what factory is used.

## Log integration

In version 2, laminas-mvc exposed `LogProcessorManager` and `LogWriterManager`
services by default, and provided specifications to laminas-modulemanager's
`ServiceListener` to allow modules to provide configuration for each.

This functionality is now removed from laminas-mvc. It is now exposed directly by
the [laminas-log](https://docs.laminas.dev/laminas-log/) component
itself. To add it, install laminas-log:

```bash
$ composer require laminas/laminas-log
```

Note: the above assumes you have already installed laminas-component-installer, per
the section above on [dependency reduction](#dependency-reduction).

laminas-log now also exposes `LogFilterManager` and `LogFormatterManager`,
corresponding to the following:

Service | Config Key | Provider Interface | Provider Method
------- | ---------- | ------------------ | ---------------
LogFilterManager | `log_filters` | `Laminas\Log\Filter\LogFilterProviderInterface` | `getLogFilterConfig()`
LogFormatterManager | `log_formatters` | `Laminas\Log\Formatter\LogFormatterProviderInterface` | `getLogFormatterConfig()`

This additions allow you to provide additional plugins for every aspect laminas-log
exposes.

## Plugins

The following plugins have been removed from the main laminas-mvc repository, and
into their own standalone repositories. In all cases, please be sure to install
the [component installer as detailed above](#dependency-reduction) before
installing the plugins, to automate injection into your application
configuration.

### fileprg()

The `fileprg()` plugin is now provided via the
[laminas-mvc-plugin-fileprg](https://github.com/laminas/laminas-mvc-plugin-fileprg)
component.

```bash
$ composer require laminas/laminas-mvc-plugin-fileprg
```

`Laminas\Mvc\Controller\Plugin\FilePostRedirectGet` becomes
`Laminas\Mvc\Plugin\FilePrg\FilePostRedirectGet`. However, it is still mapped as
`fileprg()`.

### flashMessenger()

The `flashMessenger()` plugin is now provided via the
[laminas-mvc-plugin-flashmessenger](https://github.com/laminas/laminas-mvc-plugin-flashmessenger)
component.

```bash
$ composer require laminas/laminas-mvc-plugin-flashmessenger
```

`Laminas\Mvc\Controller\Plugin\FlashMessenger` becomes
`Laminas\Mvc\Plugin\FlashMessenger\FlashMessenger`. However, it is still mapped as
`flashMessenger()` and `flashmessenger()`.

### identity()

The `identity()` plugin is now provided via the
[laminas-mvc-plugin-identity](https://github.com/laminas/laminas-mvc-plugin-identity)
component.

```bash
$ composer require laminas/laminas-mvc-plugin-identity
```

`Laminas\Mvc\Controller\Plugin\Identity` becomes
`Laminas\Mvc\Plugin\Identity\Identity`. However, it is still mapped as
`identity()`.

Additionally, `Laminas\Mvc\Controller\Plugin\Service\IdentityFactory` now becomes
`Laminas\Mvc\Plugin\Identity\IdentityFactory`.

### prg()

The `prg()` plugin is now provided via the
[laminas-mvc-plugin-prg](https://github.com/laminas/laminas-mvc-plugin-prg)
component.

```bash
$ composer require laminas/laminas-mvc-plugin-prg
```

`Laminas\Mvc\Controller\Plugin\PostRedirectGet` becomes
`Laminas\Mvc\Plugin\Prg\PostRedirectGet`. However, it is still mapped as `prg()`.

## Serializer integration

In version 2, laminas-mvc exposed a `SerializerAdapterManager` service by default, and
provided specifications to laminas-modulemanager's `ServiceListener`
to allow modules to provide serializer configuration.

This functionality is now removed from laminas-mvc. It is now exposed directly by
the [laminas-serializer](https://github.com/laminas/laminas-serializer) component
itself. To add it, install laminas-serializer

```bash
$ composer require laminas/laminas-serializer
```

Note: the above assumes you have already installed laminas-component-installer, per
the section above on [dependency reduction](#dependency-reduction).

## ServiceLocatorAware initializers

Starting with laminas-servicemanager v3, that component no longer defines the
`ServiceLocatorAwareInterface`. Since laminas-mvc pins against laminas-servicemanager
v3 with its own v3 release, the initializers that injected the application
container into implementations of that interface are no longer relevant. As
such, they have now been removed from each of the
`Laminas\Mvc\Service\ServiceManagerConfig` and
`Laminas\Mvc\Controller\ControllerManager` classes.

Additionally, the duck-typed `ServiceLocatorAwareInterface` implementation in
`AbstractController` was removed, as messaged in the 2.7 release.

If you relied on this functionality, you are encouraged to update your code to
use factories to inject your *actual* dependencies.

## Validator integration

In version 2, laminas-mvc exposed a `ValidatorManager` service by default, and
provided specifications to laminas-modulemanager's `ServiceListener`
to allow modules to provide validator configuration.

This functionality is now removed from laminas-mvc. It is now exposed directly by
the [laminas-validator](https://docs.laminas.dev/laminas-validator/) component
itself. To add it, install laminas-validator:

```bash
$ composer require laminas/laminas-validator
```

Note: the above assumes you have already installed laminas-component-installer, per
the section above on [dependency reduction](#dependency-reduction).

## Laminas\Mvc\View\InjectTemplateListener

The `InjectTemplateListener` attempts to map a controller name to a
template using a variety of heuristics, including an explicit map provided
during configuration, or auto-detection based on the controller class name.

In version 2, the autodetection took into consideration the `__NAMESPACE__`
route match parameter to derive subnamespaces, or would omit them completely if
`__NAMESPACE__` was not present. This caused issues when multiple modules shared
a top-level namespace (e.g., `Laminas\ApiTools` and `Laminas\ApiTools\Admin`) and each
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

## Laminas\Mvc\View\SendResponseListener

`Laminas\Mvc\View\SendResponseListener` was deprecated with the 2.2 release, and
has been an extension of `Laminas\Mvc\SendResponseListener` ever since. It is
removed with the v3 release.
