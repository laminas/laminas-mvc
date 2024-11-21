<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\Controller\TestAsset;

use Laminas\Mvc\Controller\AbstractActionController;

class ControllerWithUnionTypeHintedConstructorParameter extends AbstractActionController
{
    public SampleInterface|AnotherSampleInterface $sample;

    public function __construct(SampleInterface|AnotherSampleInterface $sample)
    {
        $this->sample = $sample;
    }
}
