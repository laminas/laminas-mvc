<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Mvc\Service;

use Laminas\EventManager\EventManagerAwareInterface;
use Laminas\EventManager\EventManagerInterface;
use Laminas\ServiceManager\ConfigInterface;
use Laminas\ServiceManager\ServiceLocatorAwareInterface;
use Laminas\ServiceManager\ServiceManager;
use Laminas\ServiceManager\ServiceManagerAwareInterface;

class ServiceManagerConfig implements ConfigInterface
{
    /**
     * Services that can be instantiated without factories
     *
     * @var array
     */
    protected $invokables = array(
        'SharedEventManager' => 'Laminas\EventManager\SharedEventManager',
    );

    /**
     * Service factories
     *
     * @var array
     */
    protected $factories = array(
        'EventManager'  => 'Laminas\Mvc\Service\EventManagerFactory',
        'ModuleManager' => 'Laminas\Mvc\Service\ModuleManagerFactory',
    );

    /**
     * Abstract factories
     *
     * @var array
     */
    protected $abstractFactories = array();

    /**
     * Aliases
     *
     * @var array
     */
    protected $aliases = array(
        'Laminas\EventManager\EventManagerInterface' => 'EventManager',
    );

    /**
     * Shared services
     *
     * Services are shared by default; this is primarily to indicate services
     * that should NOT be shared
     *
     * @var array
     */
    protected $shared = array(
        'EventManager' => false,
    );

    /**
     * Constructor
     *
     * Merges internal arrays with those passed via configuration
     *
     * @param  array $configuration
     */
    public function __construct(array $configuration = array())
    {
        if (isset($configuration['invokables'])) {
            $this->invokables = array_merge($this->invokables, $configuration['invokables']);
        }

        if (isset($configuration['factories'])) {
            $this->factories = array_merge($this->factories, $configuration['factories']);
        }

        if (isset($configuration['abstract_factories'])) {
            $this->abstractFactories = array_merge($this->abstractFactories, $configuration['abstract_factories']);
        }

        if (isset($configuration['aliases'])) {
            $this->aliases = array_merge($this->aliases, $configuration['aliases']);
        }

        if (isset($configuration['shared'])) {
            $this->shared = array_merge($this->shared, $configuration['shared']);
        }

    }

    /**
     * Configure the provided service manager instance with the configuration
     * in this class.
     *
     * In addition to using each of the internal properties to configure the
     * service manager, also adds an initializer to inject ServiceManagerAware
     * and ServiceLocatorAware classes with the service manager.
     *
     * @param  ServiceManager $serviceManager
     * @return void
     */
    public function configureServiceManager(ServiceManager $serviceManager)
    {
        foreach ($this->invokables as $name => $class) {
            $serviceManager->setInvokableClass($name, $class);
        }

        foreach ($this->factories as $name => $factoryClass) {
            $serviceManager->setFactory($name, $factoryClass);
        }

        foreach ($this->abstractFactories as $factoryClass) {
            $serviceManager->addAbstractFactory($factoryClass);
        }

        foreach ($this->aliases as $name => $service) {
            $serviceManager->setAlias($name, $service);
        }

        foreach ($this->shared as $name => $value) {
            $serviceManager->setShared($name, $value);
        }

        $serviceManager->addInitializer(function ($instance) use ($serviceManager) {
            if ($instance instanceof EventManagerAwareInterface
                && !$instance->getEventManager() instanceof EventManagerInterface
            ) {
                $instance->setEventManager($serviceManager->get('EventManager'));
            }
        });

        $serviceManager->addInitializer(function ($instance) use ($serviceManager) {
            if ($instance instanceof ServiceManagerAwareInterface) {
                $instance->setServiceManager($serviceManager);
            }
        });

        $serviceManager->addInitializer(function ($instance) use ($serviceManager) {
            if ($instance instanceof ServiceLocatorAwareInterface) {
                $instance->setServiceLocator($serviceManager);
            }
        });

        $serviceManager->setService('ServiceManager', $serviceManager);
        $serviceManager->setAlias('Laminas\ServiceManager\ServiceLocatorInterface', 'ServiceManager');
        $serviceManager->setAlias('Laminas\ServiceManager\ServiceManager', 'ServiceManager');
    }
}
