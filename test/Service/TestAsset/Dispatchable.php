<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\Service\TestAsset;

use Laminas\Mvc\Controller\AbstractActionController;

class Dispatchable extends AbstractActionController
{
    /**
     * Override, so we can test injection
     *
     * @return mixed
     */
    public function getEventManager()
    {
        return $this->events;
    }
}
