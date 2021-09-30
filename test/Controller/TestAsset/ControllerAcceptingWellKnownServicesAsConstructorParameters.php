<?php

namespace LaminasTest\Mvc\Controller\TestAsset;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Validator\ValidatorPluginManager;

class ControllerAcceptingWellKnownServicesAsConstructorParameters extends AbstractActionController
{
    public $validators;

    public function __construct(ValidatorPluginManager $validators)
    {
        $this->validators = $validators;
    }
}
