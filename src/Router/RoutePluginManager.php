<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Mvc\Router;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\AbstractPluginManager;
use Zend\Stdlib\ArrayUtils;

/**
 * Plugin manager implementation for routes
 *
 * Enforces that routes retrieved are instances of RouteInterface. It overrides
 * configure() to map invokables to the component-specific
 * RouteInvokableFactory.
 *
 * The manager is marked to not share by default, in order to allow multiple
 * route instances of the same type.
 */
class RoutePluginManager extends AbstractPluginManager
{
    /**
     * Only RouteInterface instances are valid
     *
     * @var string
     */
    protected $instanceOf = RouteInterface::class;

    /**
     * Do not share instances.
     *
     * @var bool
     */
    protected $shareByDefault = false;

    /**
     * Constructor
     *
     * Ensure that the instance is seeded with the RouteInvokableFactory as an
     * abstract factory.
     *
     * @param ContainerInterface $container
     * @param array $config
     */
    public function __construct(ContainerInterface $container, array $config = [])
    {
        $config = ArrayUtils::merge(['abstract_factories' => [
            RouteInvokableFactory::class,
        ]], $config);

        parent::__construct($container, $config);
    }

    /**
     * Pre-process configuration.
     *
     * Checks for invokables, and, if found, maps them to the
     * component-specific RouteInvokableFactory; removes the invokables entry
     * before passing to the parent.
     *
     * @param array $config
     * @return void
     */
    protected function configure(array $config)
    {
        if (isset($config['invokables']) && ! empty($config['invokables'])) {
            $aliases   = $this->createAliasesForInvokables($config['invokables']);
            $factories = $this->createFactoriesForInvokables($config['invokables']);

            if (! empty($aliases)) {
                $config['aliases'] = isset($config['aliases'])
                    ? array_merge($config['aliases'], $aliases)
                    : $aliases;
            }

            $config['factories'] = isset($config['factories'])
                ? array_merge($config['factories'], $factories)
                : $factories;

            unset($config['invokables']);
        }

        parent::configure($config);
    }

     /**
     * Create aliases for invokable classes.
     *
     * If an invokable service name does not match the class it maps to, this
     * creates an alias to the class (which will later be mapped as an
     * invokable factory).
     *
     * @param array $invokables
     * @return array
     */
    protected function createAliasesForInvokables(array $invokables)
    {
        $aliases = [];
        foreach ($invokables as $name => $class) {
            if ($name === $class) {
                continue;
            }
            $aliases[$name] = $class;
        }
        return $aliases;
    }

    /**
     * Create invokable factories for invokable classes.
     *
     * If an invokable service name does not match the class it maps to, this
     * creates an invokable factory entry for the class name; otherwise, it
     * creates an invokable factory for the entry name.
     *
     * @param array $invokables
     * @return array
     */
    protected function createFactoriesForInvokables(array $invokables)
    {
        $factories = [];
        foreach ($invokables as $name => $class) {
            if ($name === $class) {
                $factories[$name] = RouteInvokableFactory::class;
                continue;
            }

            $factories[$class] = RouteInvokableFactory::class;
        }
        return $factories;
    }
}
