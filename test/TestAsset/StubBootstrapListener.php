<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\TestAsset;

use Laminas\EventManager\EventManagerInterface;
use Laminas\EventManager\ListenerAggregateInterface;
use Laminas\Mvc\MvcEvent;

class StubBootstrapListener implements ListenerAggregateInterface
{
    protected array $listeners = [];

    /**
     * @inheritDoc
     */
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $this->listeners[] = $events->attach(MvcEvent::EVENT_BOOTSTRAP, [$this, 'onBootstrap']);
    }

    /**
     * @inheritDoc
     */
    public function detach(EventManagerInterface $events)
    {
        foreach ($this->listeners as $index => $listener) {
            if ($events->detach($listener)) {
                unset($this->listeners[$index]);
            }
        }
    }

    public function getListeners(): array
    {
        return $this->listeners;
    }

    public function onBootstrap(MvcEvent $e): void
    {
    }
}
