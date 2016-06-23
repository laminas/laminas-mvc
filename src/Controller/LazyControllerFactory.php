<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Mvc\Controller;

use Interop\Container\ContainerInterface;
use ReflectionClass;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\AbstractFactoryInterface;
use Zend\Stdlib\DispatchableInterface;

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
 *         LazyControllerFactory::class,
 *     ],
 * ],
 * </code>
 *
 * Or as a factory, mapping a controller class name to it:
 *
 * <code>
 * 'controllers' => [
 *     'factories' => [
 *         MyControllerWithDependencies::class => LazyControllerFactory::class,
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
 * - Some services provided by Zend Framework components do not have
 *   entries based on their class name (for historical reasons); the
 *   factory contains a map of these class/interface names to the
 *   corresponding service name to allow them to resolve.
 *
 * `$options` passed to the factory are ignored in all cases, as we cannot
 * make assumptions about which argument(s) they might replace.
 */
class LazyControllerFactory implements AbstractFactoryInterface
{
    /**
     * Maps known classes/interfaces to the service that provides them; only
     * required for those services with no entry based on the class/interface
     * name.
     *
     * @var string[]
     */
    private $aliases = [
        'Zend\Console\Adapter\AdapterInterface'     => 'ConsoleAdapter',
        'Zend\Filter\FilterPluginManager'           => 'FilterManager',
        'Zend\Hydrator\HydratorPluginManager'       => 'HydratorManager',
        'Zend\InputFilter\InputFilterPluginManager' => 'InputFilterManager',
        'Zend\Log\FilterPluginManager'              => 'LogFilterManager',
        'Zend\Log\FormatterPluginManager'           => 'LogFormatterManager',
        'Zend\Log\ProcessorPluginManager'           => 'LogProcessorManager',
        'Zend\Log\WriterPluginManager'              => 'LogWriterManager',
        'Zend\Serializer\AdapterPluginManager'      => 'SerializerAdapterManager',
        'Zend\Validator\ValidatorPluginManager'     => 'ValidatorManager',
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

        $parameters = [];
        foreach ($reflectionParameters as $parameter) {
            if ($parameter->isArray()
                && $parameter->getName() === 'config'
                && $container->has('config')
            ) {
                $parameters[] = $container->get('config');
                continue;
            }

            if ($parameter->isArray()) {
                $parameters[] = [];
                continue;
            }

            if (! $parameter->getClass()) {
                $parameters[] = null;
                continue;
            }

            $type = $parameter->getClass()->getName();
            $type = array_key_exists($type, $this->aliases) ? $this->aliases[$type] : $type;

            if (! $container->has($type)) {
                throw new ServiceNotFoundException(sprintf(
                    'Unable to create controller "%s"; unable to resolve parameter "%s" using type hint "%s"',
                    $requestedName,
                    $parameter->getName(),
                    $type
                ));
            }

            $parameters[] = $container->get($type);
        }

        return $reflectionClass->newInstanceArgs($parameters);
    }

    /**
     * {@inheritDoc}
     */
    public function canCreate(ContainerInterface $container, $requestedName)
    {
        if (! is_string($requestedName) || ! class_exists($requestedName)) {
            return false;
        }

        $implements = class_implements($requestedName);
        return in_array(DispatchableInterface::class, $implements, true);
    }
}
