# Automating Controller Factories

Writing a factory class for each and every controller that has dependencies
can be tedious, particularly in early development as you are still sorting
out dependencies.

As of version 3.0.1, zend-mvc ships with `Zend\Mvc\Controller\LazyControllerAbstractFactory`,
which provides a reflection-based approach to controller instantiation,
resolving constructor dependencies to the relevant services. The factory may be
used as either an abstract factory, or mapped to specific controller names as a
factory:

```php
use Zend\Mvc\Controller\LazyControllerAbstractFactory;

return [
    /* ... */
    'controllers' => [
        'abstract_factories' => [
            LazyControllerAbstractFactory::class,
        ],
        'factories' => [
            'MyModule\Controller\FooController' => LazyControllerAbstractFactory::class,
        ],
    ],
    /* ... */
];
```

Mapping controllers to the factory is more explicit and performant.

The factory operates with the following constraints/features:

- A parameter named `$config` typehinted as an array will receive the
  application "config" service (i.e., the merged configuration).
- Parameters typehinted against array, but not named `$config`, will
  be injected with an empty array.
- Scalar parameters will be resolved as null values.
- If a service cannot be found for a given typehint, the factory will
  raise an exception detailing this.
- Some services provided by Zend Framework components do not have
  entries based on their class name (for historical reasons); the
  factory contains a map of these class/interface names to the
  corresponding service name to allow them to resolve. These include:
    - `Zend\Console\Adapter\AdapterInterface` maps to `ConsoleAdapter`,
    - `Zend\Filter\FilterPluginManager` maps to `FilterManager`,
    - `Zend\Hydrator\HydratorPluginManager` maps to `HydratorManager`,
    - `Zend\InputFilter\InputFilterPluginManager` maps to `InputFilterManager`,
    - `Zend\Log\FilterPluginManager` maps to `LogFilterManager`,
    - `Zend\Log\FormatterPluginManager` maps to `LogFormatterManager`,
    - `Zend\Log\ProcessorPluginManager` maps to `LogProcessorManager`,
    - `Zend\Log\WriterPluginManager` maps to `LogWriterManager`,
    - `Zend\Serializer\AdapterPluginManager` maps to `SerializerAdapterManager`,
    - `Zend\Validator\ValidatorPluginManager` maps to `ValidatorManager`,

`$options` passed to the factory are ignored in all cases, as we cannot
make assumptions about which argument(s) they might replace.

Once your dependencies have stabilized, we recommend writing a dedicated
factory, as reflection can introduce performance overhead.

## References

This feature was inspired by [a blog post by Alexandre Lemaire](http://circlical.com/blog/2016/3/9/preparing-for-zend-f).
