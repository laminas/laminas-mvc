<?php

namespace LaminasTest\Mvc\Controller\TestAsset;

use Laminas\Mvc\Controller\AbstractActionController;

class ControllerWithTypeHintedConstructorParameter extends AbstractActionController
{
    public $sample;

    public function __construct(SampleInterface $sample)
    {
        $this->sample = $sample;
    }
}
