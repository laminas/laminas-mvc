<?php
namespace LaminasTest\Mvc\Service\TestAsset;

use Laminas\Mvc\Controller\AbstractActionController;

class Dispatchable extends AbstractActionController
{
    /**
     * Override, so we can test injection
     */
    public function getEventManager()
    {
        return $this->events;
    }
}
