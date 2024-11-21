<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\Controller\TestAsset;

use Laminas\Mvc\Controller\AbstractActionController;

class ControllerWithEmptyConstructor extends AbstractActionController
{
    public function __construct()
    {
    }
}
