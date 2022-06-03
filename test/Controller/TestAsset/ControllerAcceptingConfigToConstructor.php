<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\Controller\TestAsset;

use Laminas\Mvc\Controller\AbstractActionController;

class ControllerAcceptingConfigToConstructor extends AbstractActionController
{
    public $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }
}
