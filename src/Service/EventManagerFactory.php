<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Mvc\Service;

use Interop\Container\ContainerInterface;
use Zend\EventManager\EventManager;
use Zend\ServiceManager\Factory\FactoryInterface;

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
        $shared = $container->has('SharedEventManager') ? $container->get('SharedEventManager') : null;

        return new EventManager($shared);
    }
}
