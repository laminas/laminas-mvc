<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\Controller\TestAsset;

use Laminas\Mvc\Controller\AbstractController;
use Laminas\Mvc\MvcEvent;

class AbstractControllerStub extends AbstractController
{
    public function onDispatch(MvcEvent $e)
    {
        // noop
    }
}
