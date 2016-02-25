<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Mvc\Service;

use Interop\Container\ContainerInterface;
use Zend\EventManager\EventManager;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\SharedEventManager;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\ModuleManager\Listener\ServiceListener;
use Zend\ModuleManager\ModuleManager;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\Factory\InvokableFactory;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceManager;
use Zend\ServiceManager\ServiceManagerAwareInterface;

class ServiceManagerConfig extends Config
{
    protected $config = [
        'abstract_factories' => [],
        'aliases'            => [
            'EventManager'            => EventManagerInterface::class,
            EventManager::class       => EventManagerInterface::class,
            'ModuleManager'           => ModuleManager::class,
            'ServiceListener'         => ServiceListener::class,
            'ServiceManager'          => ServiceManager::class,
            'SharedEventManager'      => SharedEventManagerInterface::class,
            SharedEventManager::class => SharedEventManagerInterface::class,
        ],
        'delegators' => [],
        'factories'  => [
            EventManagerInterface::class       => EventManagerFactory::class,
            ModuleManager::class               => ModuleManagerFactory::class,
            ServiceListener::class             => ServiceListenerFactory::class,
            SharedEventManagerInterface::class => InvokableFactory::class,
        ],
        'lazy_services' => [],
        'initializers'  => [],
        'invokables'    => [],
        'services'      => [],
        'shared'        => [
            'EventManager' => false,
            EventManager::class => false,
            EventManagerInterface::class => false,
        ],
    ];

    /**
     * Constructor
     *
     * Merges internal arrays with those passed via configuration, and also
     * defines initializers for each of:
     *
     * - EventManagerAwareInterface implementations
     * - ServiceManagerAwareInterface implementations
     * - ServiceLocatorAwareInterface implementations
     *
     * @param  array $configuration
     */
    public function __construct(array $configuration = [])
    {
        $this->config['initializers'] = array_merge($this->config['initializers'], [
            'EventManagerAwareInitializer' => function ($first, $second) {
                if ($first instanceof ContainerInterface) {
                    $container = $first;
                    $instance = $second;
                } else {
                    $container = $second;
                    $instance = $first;
                }

                if (! $instance instanceof EventManagerAwareInterface) {
                    return;
                }

                $eventManager = $instance->getEventManager();

                // If the instance has an EM WITH an SEM composed, do nothing.
                if ($eventManager instanceof EventManagerInterface
                    && $eventManager->getSharedManager() instanceof SharedEventManagerInterface
                ) {
                    return;
                }

                $instance->setEventManager($container->get('EventManager'));
            },
            'ServiceManagerAwareInitializer' => function ($first, $second) {
                if ($first instanceof ContainerInterface) {
                    $container = $first;
                    $instance = $second;
                } else {
                    $container = $second;
                    $instance = $first;
                }

                if ($container instanceof ServiceManager && $instance instanceof ServiceManagerAwareInterface) {
                    trigger_error(sprintf(
                        'ServiceManagerAwareInterface is deprecated and will be removed in version 3.0, along '
                        . 'with the ServiceManagerAwareInitializer. Please update your class %s to remove '
                        . 'the implementation, and start injecting your dependencies via factory instead.',
                        get_class($instance)
                    ), E_USER_DEPRECATED);
                    $instance->setServiceManager($container);
                }
            },
            'ServiceLocatorAwareInitializer' => function ($first, $second) {
                if ($first instanceof ContainerInterface) {
                    $container = $first;
                    $instance = $second;
                } else {
                    $container = $second;
                    $instance = $first;
                }

                if ($instance instanceof ServiceLocatorAwareInterface) {
                    trigger_error(sprintf(
                        'ServiceLocatorAwareInterface is deprecated and will be removed in version 3.0, along '
                        . 'with the ServiceLocatorAwareInitializer. Please update your class %s to remove '
                        . 'the implementation, and start injecting your dependencies via factory instead.',
                        get_class($instance)
                    ), E_USER_DEPRECATED);
                    $instance->setServiceLocator($container);
                }
            },
        ]);

        parent::__construct($configuration);
    }

    /**
     * Configure service container.
     *
     * Uses the configuration present in the instance to configure the provided
     * service container.
     *
     * Before doing so, it adds a "service" entry for the ServiceManager class,
     * pointing to the provided service container.
     *
     * @param ServiceManager $services
     * @return ServiceManager
     */
    public function configureServiceManager(ServiceManager $services)
    {
        $this->config['services'][ServiceManager::class] = $services;
        parent::configureServiceManager($services);
        return $services;
    }
}
