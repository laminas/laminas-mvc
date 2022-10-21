<?php

namespace LaminasTest\Mvc\Controller\Plugin\TestAsset;

use Laminas\Mvc\Controller\Plugin\AbstractPlugin;

class SamplePluginWithConstructor extends AbstractPlugin
{
    public function __construct(protected $bar = 'baz')
    {
    }

    public function getBar()
    {
        return $this->bar;
    }
}
