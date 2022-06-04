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
     * @param int $priority
     */
    public function attach(EventManagerInterface $events, $priority = 1): void
    {
        $this->listeners[] = $events->attach(MvcEvent::EVENT_BOOTSTRAP, [$this, 'onBootstrap']);
    }

    public function detach(EventManagerInterface $events): void
    {
        foreach ($this->listeners as $index => $listener) {
            $events->detach($listener);
            unset($this->listeners[$index]);
        }
    }

    public function getListeners(): array
    {
        return $this->listeners;
    }

    public function onBootstrap(): void
    {
    }
}
