<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Mvc\Service;

use Interop\Container\ContainerInterface;
use Laminas\EventManager\EventManager;
use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use ReflectionClass;

class EventManagerFactory implements FactoryInterface
{
    /**
     * Create an EventManager instance
     *
     * Creates a new EventManager instance, seeding it with a shared instance
     * of SharedEventManager.
     *
     * @param  ContainerInterface $container
     * @param  string $name
     * @param  null|array $options
     * @return EventManager
     */
    public function __invoke(ContainerInterface $container, $name, array $options = null)
    {
        if ($this->acceptsSharedManagerToConstructor()) {
            // laminas-eventmanager v3
            return new EventManager(
                $container->has('SharedEventManager') ? $container->get('SharedEventManager') : null
            );
        }

        // laminas-eventmanager v2
        $events = new EventManager();

        if ($container->has('SharedEventManager')) {
            $events->setSharedManager($container->get('SharedEventManager'));
        }

        return $events;
    }

    /**
     * Create and return EventManager instance
     *
     * For use with laminas-servicemanager v2; proxies to __invoke().
     *
     * @param ServiceLocatorInterface $container
     * @return EventManager
     */
    public function createService(ServiceLocatorInterface $container)
    {
        return $this($container, EventManager::class);
    }

    /**
     * Does the EventManager accept the shared manager to the constructor?
     *
     * In laminas-eventmanager v3, the EventManager accepts the shared manager
     * instance to the constructor *only*, while in v2, it must be injected
     * via the setSharedManager() method.
     *
     * @return bool
     */
    private function acceptsSharedManagerToConstructor()
    {
        $r = new ReflectionClass(EventManager::class);
        return ! $r->hasMethod('setSharedManager');
    }
}
