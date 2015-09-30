<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Mvc;

use ReflectionProperty;
use Zend\EventManager\EventManager;

/**
 * Offer methods for introspecting event manager events and listeners.
 */
trait EventManagerIntrospectionTrait
{
    public function getEventsFromEventManager(EventManager $events)
    {
        $r = new ReflectionProperty($events, 'events');
        $r->setAccessible(true);
        $listeners = $r->getValue($events);
        return array_keys($listeners);
    }

    public function getListenersForEvent($event, EventManager $events, $withPriority = false)
    {
        $r = new ReflectionProperty($events, 'events');
        $r->setAccessible(true);
        $listeners = $r->getValue($events);

        if (! isset($listeners[$event])) {
            return $this->traverseListeners([]);
        }

        return $this->traverseListeners($listeners[$event], $withPriority);
    }

    public function traverseListeners(array $queue, $withPriority = false)
    {
        krsort($queue, SORT_NUMERIC);

        foreach ($queue as $priority => $listeners) {
            $priority = (int) $priority;
            foreach ($listeners as $listener) {
                if ($withPriority) {
                    yield $priority => $listener;
                } else {
                    yield $listener;
                }
            }
        }
    }
}
