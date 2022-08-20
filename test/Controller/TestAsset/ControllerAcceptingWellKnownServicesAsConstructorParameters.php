<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\Controller\TestAsset;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Validator\ValidatorPluginManager;

class ControllerAcceptingWellKnownServicesAsConstructorParameters extends AbstractActionController
{
    public ValidatorPluginManager $validators;

    public function __construct(ValidatorPluginManager $validators)
    {
        $this->validators = $validators;
    }
}
