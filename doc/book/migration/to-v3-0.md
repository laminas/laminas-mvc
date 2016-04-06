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
[zend-component-installer](http://zendframework.github.io/zend-component-installer/).
Install this in your application now:

```bash
$ composer require --dev zendframework/zend-component-installer
```

Once installed, the component acts as a Composer plugin, and will intercept
packages marked as components or Zend Framework modules, and register them with
your application configuration. Components are pushed to the top of the module
list, while modules are pushed to the end. As a development component, it will
not be installed in your production distributions.

## DI-ServiceManager integration

The integration between [zend-servicemanager](https://zendframework.github.io/zend-servicemanager) and
[zend-di](https://github.com/zendframework/zend-di) has been moved to a new
standalone component, [zend-servicemanager-di](https://zendframework.github.io/zend-servicemanager-di/).
In most cases, installing the component will restore the original behavior:

```bash
$ composer require zendframework/zend-servicemanager-di
```

> ### Manual installation
>
> The above assumes you're using the new component installer detailed in the
> [dependency reduction](#dependency-reduction) section, above. If you are not,
> you will need to inject the zend-servicemanager-di module into your
> application manually; follow the [instructions in the zend-servicemanager-di documentation](https://zendframework.github.io/zend-servicemanager-di/)
> to do so.

The new component also contains a [migration document](https://zendframework.github.io/zend-servicemanager-di/migration/v2-to-v3/)
detailing potential issues for users migrating to version 3.

## Routing

Routing was removed from zend-mvc, and moved to a new component,
[zend-router](https://zendframework.github.io/zend-router/), which is now a
dependency of zend-mvc.

The changes that will impact users are:

- [Query route removal](http://zendframework.github.io/zend-router/migration/v2-to-v3/#query-route-removal); 
  this route had been deprecated since 2.3.0, and removed for the 3.0 release.
- [Namespace changes](http://zendframework.github.io/zend-router/migration/v2-to-v3/#namespace-change);
  with the separation to the zend-router component, all routes changed
  namespaces from `Zend\Mvc\Router` to `Zend\Router`.

Follow the links above for more details on these changes, and how to migrate
your code.

## Console tooling

Console tooling, including console routes, were split off to a new component,
[zend-mvc-console](https://zendframework.github.io/zend-mvc-console/). If you
were using the console tooling, install zend-mvc-console:

```bash
$ composer require zendframework/zend-mvc-console
```

(Make sure you've already installed zend-component-installer before you do, to
ensure the component is registered with your application!)

zend-mvc-console exposes all of the same functionality as was in the v2 series
of zend-mvc, but most components are in different namespaces. Please read the
[zend-mvc-console migration guide](http://zendframework.github.io/zend-mvc-console/migration/v2-to-v3/)
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
the [zend-filter](https://zendframework.github.io/zend-filter/) component
itself. To add it, install zend-filter:

```bash
$ composer require zendframework/zend-filter
```

Note: the above assumes you have already installed zend-component-installer, per
the section above on [dependency reduction](#dependency-reduction).

## i18n integration

Internationalization tooling, including:

- the integration translator (`MvcTranslator` service)
- the "dummy" translator
- the `TranslatorAwareTreeRouteStack` implementation
- factories for the translator and translator loader managers

were removed, and re-assigned to the [zend-i18n](https://zendframework.github.io/zend-i18n/)
and [zend-mvc-i18n](https://zendframework.github.io/zend-mvc-i18n/) packages.
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
  migration guide](https://zendframework.github.io/zend-mvc-i18n/migration/v2-to-v3/)
  to determine what names have changed.
- Instead of extending, consider using [delegator factories](https://zendframework.github.io/zend-servicemanager/delegators/),
  as these decorate the service factory, regardless of what factory is used.

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

## Validator integration

In version 2, zend-mvc exposed a `ValidatorManager` service by default, and
provided specifications to zend-modulemanager's `ServiceListener`
to allow modules to provide validator configuration.

This functionality is now removed from zend-mvc. It is now exposed directly by
the [zend-validator](https://zendframework.github.io/zend-validator/) component
itself. To add it, install zend-validator:

```bash
$ composer require zendframework/zend-validator
```

Note: the above assumes you have already installed zend-component-installer, per
the section above on [dependency reduction](#dependency-reduction).
