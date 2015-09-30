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
use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\ServiceManager;

class ServiceManagerConfig extends Config
{
    protected $config = [
        'abstract_factories' => [],
        'aliases'            => [
            'Zend\EventManager\EventManagerInterface' => 'EventManager',
            'Zend\ServiceManager\ServiceManager'      => 'ServiceManager',
        ],
        'delegators' => [],
        'factories'  => [
            'EventManager'    => EventManagerFactory::class,
            'ModuleManager'   => ModuleManagerFactory::class,
            'ServiceListener' => ServiceListenerFactory::class,
        ],
        'lazy_services' => [],
        'initializers'  => [],
        'invokables'    => [
            'SharedEventManager' => 'Zend\EventManager\SharedEventManager',
        ],
        'services' => [],
        'shared'   => [
            'EventManager' => false,
        ],
    ];

    /**
     * Constructor
     *
     * Merges internal arrays with those passed via configuration
     *
     * @param  array $configuration
     */
    public function __construct(array $configuration = [])
    {
        $this->config['initializers'] = array_merge($this->config['initializers'], [
            'EventManagerAwareInitializer' => function (ContainerInterface $container, $instance) {
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
        ]);


        parent::__construct($configuration);
    }
}
