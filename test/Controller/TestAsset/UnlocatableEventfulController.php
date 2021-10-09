<?php

namespace LaminasTest\Mvc\Controller\TestAsset;

use Laminas\EventManager\EventInterface as Event;
use Laminas\Mvc\InjectApplicationEventInterface;
use Laminas\Stdlib\DispatchableInterface;
use Laminas\Stdlib\RequestInterface as Request;
use Laminas\Stdlib\ResponseInterface as Response;

class UnlocatableEventfulController implements DispatchableInterface, InjectApplicationEventInterface
{
    protected $event;

    public function setEvent(Event $event)
    {
        $this->event = $event;
    }

    public function getEvent()
    {
        return $this->event;
    }

    public function dispatch(Request $request, Response $response = null)
    {
    }
}
