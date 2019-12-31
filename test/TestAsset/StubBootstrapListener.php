<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\TestAsset;

use Laminas\EventManager\EventManagerInterface;
use Laminas\EventManager\ListenerAggregateInterface;
use Laminas\Mvc\MvcEvent;

class StubBootstrapListener implements ListenerAggregateInterface
{
    protected $listeners = array();

    /**
     * @see \Laminas\EventManager\ListenerAggregateInterface::attach()
     */
    public function attach (EventManagerInterface $events)
    {
        $this->listeners[] = $events->attach(MvcEvent::EVENT_BOOTSTRAP, array($this, 'onBootstrap'));
    }

    /**
     * @see \Laminas\EventManager\ListenerAggregateInterface::detach()
     */
    public function detach (EventManagerInterface $events)
    {
        foreach ($this->listeners as $index => $listener) {
            if ($events->detach($listener)) {
                unset($this->listeners[$index]);
            }
        }
    }

    public function getListeners()
    {
        return $this->listeners;
    }

    public function onBootstrap($e)
    {
    }
}
