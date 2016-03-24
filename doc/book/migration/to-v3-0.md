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
