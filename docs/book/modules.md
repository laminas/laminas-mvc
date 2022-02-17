# Modules

A module is a PHP namespace. It can contain controllers, classes, view scripts, configuration, tests, as well as public assets such as images, CSS, and JavaScript. For example, if you have an e-commerce application, you could have a module for presenting and selling products, and another for administrators to create products and manage orders.

## File and Directory Structure

Given a module named `Application`, here is the recommended structure:

```bash
Application/
    config/
        module.config.php
    public/
        images/
        css/
        js/
    src/
        Controller/
            ProductController.php
        Module.php
        <other classes>
    test/
        Controller/
            ProductControllerTest.php
    view/
        application/
            product/
                list.phtml
        layout/
            layout.phtml
```

## Loading

All module classes can be [autoloaded by Composer](https://getcomposer.org/doc/01-basic-usage.md#autoloading) by adding them as a namespace in `composer.json`:

```json
"autoload": {
    "psr-4": {
        "Application\\": "module/Application/src/"
    }
}
```

This means that whenever you refer to a class in the `Application` namespace, PHP will look for it in the `module/Application/src/` folder. A class named `Application\Controller\ProductController` would be found in the file `module/Application/src/Controller/ProductController.php`.

In addition to autoloading, the application needs to be aware that the `Application` namespace represents a module. This is accomplished by adding it to the module configuration of the application in `config/modules.config.php`:

```php
return [
    'Application',
    // other modules
];
```

## Configuration

// ...

## Listeners

Each module has a `Module` class, which can contain a variety of methods called listeners. These listeners are called by [laminas-modulemanager](https://docs.laminas.dev/laminas-modulemanager/) to configure the module.

For the namespace `Application`, the module manager will look for `Application\Module`. Here is a simple example that loads a module-specific configuration file:

```php
namespace Application;

class Module
{
    public function getConfig(): array
    {
        /** @var array $config */
        $config = include __DIR__ . '/../config/module.config.php';
        return $config;
    }
}
```

### getModuleDependencies()

Checks each module to verify whether all the modules it depends on were
loaded. Each of the values returned by the method is checked
against the loaded modules list: if one of the values is not in that list, a
`Laminas\ModuleManager\Exception\MissingDependencyModuleException` is thrown.

```php
namespace Application;

use Laminas\ModuleManager\Feature\DependencyIndicatorInterface;

class Module implements DependencyIndicatorInterface
{
    public function getDependencies(): array
    {
        return [
            'Product'
        ];
    }
}
```

// Add other methods following the same structure as `getModuleDependencies()`

## Best Practices
