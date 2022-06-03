<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\Service\TestAsset;

use Laminas\EventManager\EventManagerAwareInterface;
use Laminas\EventManager\EventManagerInterface;

class EventManagerAwareObject implements EventManagerAwareInterface
{
    public static $defaultEvents;

    protected $events;

    public function setEventManager(EventManagerInterface $events)
    {
        $this->events = $events;
    }

    /**
     * @return EventManagerInterface
     */
    public function getEventManager()
    {
        if (
            ! $this->events instanceof EventManagerInterface
            && static::$defaultEvents instanceof EventManagerInterface
        ) {
            $this->setEventManager(static::$defaultEvents);
        }
        return $this->events;
    }
}
