<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\TestAsset;

use Laminas\EventManager\AbstractListenerAggregate;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Mvc\MvcEvent;

class MockSendResponseListener extends AbstractListenerAggregate
{
    /**
     * @param int $priority
     */
    public function attach(EventManagerInterface $events, $priority = 1): void
    {
        $this->listeners[] = $events->attach(MvcEvent::EVENT_FINISH, [$this, 'sendResponse'], -10000);
    }

    public function sendResponse(): void
    {
    }
}
