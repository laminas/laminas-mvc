<?php

namespace Laminas\Mvc\Controller;

use Interop\Container\ContainerInterface;
use Laminas\Console\Adapter\AdapterInterface as ConsoleAdapterInterface;
use Laminas\Filter\FilterPluginManager;
use Laminas\Hydrator\HydratorPluginManager;
use Laminas\InputFilter\InputFilterPluginManager;
use Laminas\Log\FilterPluginManager as LogFilterManager;
use Laminas\Log\FormatterPluginManager as LogFormatterManager;
use Laminas\Log\ProcessorPluginManager as LogProcessorManager;
use Laminas\Log\WriterPluginManager as LogWriterManager;
use Laminas\Serializer\AdapterPluginManager as SerializerAdapterManager;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\Factory\AbstractFactoryInterface;
use Laminas\Stdlib\DispatchableInterface;
use Laminas\Validator\ValidatorPluginManager;
use ReflectionClass;
use ReflectionParameter;

/**
 * Reflection-based factory for controllers.
 *
 * To ease development, this factory may be used for controllers with
 * type-hinted arguments that resolve to services in the application
 * container; this allows omitting the step of writing a factory for
 * each controller.
 *
 * You may use it as either an abstract factory:
 *
 * <code>
 * 'controllers' => [
 *     'abstract_factories' => [
 *         LazyControllerAbstractFactory::class,
 *     ],
 * ],
 * </code>
 *
 * Or as a factory, mapping a controller class name to it:
 *
 * <code>
 * 'controllers' => [
 *     'factories' => [
 *         MyControllerWithDependencies::class => LazyControllerAbstractFactory::class,
 *     ],
 * ],
 * </code>
 *
 * The latter approach is more explicit, and also more performant.
 *
 * The factory has the following constraints/features:
 *
 * - A parameter named `$config` typehinted as an array will receive the
 *   application "config" service (i.e., the merged configuration).
 * - Parameters type-hinted against array, but not named `$config` will
 *   be injected with an empty array.
 * - Scalar parameters will be resolved as null values.
 * - If a service cannot be found for a given typehint, the factory will
 *   raise an exception detailing this.
 * - Some services provided by Laminas components do not have
 *   entries based on their class name (for historical reasons); the
 *   factory contains a map of these class/interface names to the
 *   corresponding service name to allow them to resolve.
 *
 * `$options` passed to the factory are ignored in all cases, as we cannot
 * make assumptions about which argument(s) they might replace.
 */
class LazyControllerAbstractFactory implements AbstractFactoryInterface
{
    /**
     * Maps known classes/interfaces to the service that provides them; only
     * required for those services with no entry based on the class/interface
     * name.
     *
     * Extend the class if you wish to add to the list.
     *
     * @var string[]
     */
    protected $aliases = [
        ConsoleAdapterInterface::class  => 'ConsoleAdapter',
        FilterPluginManager::class      => 'FilterManager',
        HydratorPluginManager::class    => 'HydratorManager',
        InputFilterPluginManager::class => 'InputFilterManager',
        LogFilterManager::class         => 'LogFilterManager',
        LogFormatterManager::class      => 'LogFormatterManager',
        LogProcessorManager::class      => 'LogProcessorManager',
        LogWriterManager::class         => 'LogWriterManager',
        SerializerAdapterManager::class => 'SerializerAdapterManager',
        ValidatorPluginManager::class   => 'ValidatorManager',
    ];

    /**
     * {@inheritDoc}
     *
     * @return DispatchableInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $reflectionClass = new ReflectionClass($requestedName);

        if (null === ($constructor = $reflectionClass->getConstructor())) {
            return new $requestedName();
        }

        $reflectionParameters = $constructor->getParameters();

        if (empty($reflectionParameters)) {
            return new $requestedName();
        }

        $parameters = array_map(
            $this->resolveParameter($container, $requestedName),
            $reflectionParameters
        );

        return new $requestedName(...$parameters);
    }

    /**
     * {@inheritDoc}
     */
    public function canCreate(ContainerInterface $container, $requestedName)
    {
        if (! class_exists($requestedName)) {
            return false;
        }

        return in_array(DispatchableInterface::class, class_implements($requestedName), true);
    }

    /**
     * Resolve a parameter to a value.
     *
     * Returns a callback for resolving a parameter to a value.
     *
     * @param ContainerInterface $container
     * @param string $requestedName
     * @return callable
     */
    private function resolveParameter(ContainerInterface $container, $requestedName)
    {
        /**
         * @param ReflectionParameter $parameter
         * @return mixed
         * @throws ServiceNotFoundException If type-hinted parameter cannot be
         *   resolved to a service in the container.
         */
        return function (ReflectionParameter $parameter) use ($container, $requestedName) {
            if ($parameter->getType()
                && $parameter->getType()->getName() === 'array') {
                if ($parameter->getName() === 'config'
                    && $container->has('config')
                ) {
                    return $container->get('config');
                }
                return [];
            }

            if (! $parameter->getType() || $parameter->getType()->isBuiltin()) {
                return;
            }

            $type = $parameter->getType()->getName();
            $type = isset($this->aliases[$type]) ? $this->aliases[$type] : $type;

            if (! $container->has($type)) {
                throw new ServiceNotFoundException(sprintf(
                    'Unable to create controller "%s"; unable to resolve parameter "%s" using type hint "%s"',
                    $requestedName,
                    $parameter->getName(),
                    $type
                ));
            }

            return $container->get($type);
        };
    }
}
