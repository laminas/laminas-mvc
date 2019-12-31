<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

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
