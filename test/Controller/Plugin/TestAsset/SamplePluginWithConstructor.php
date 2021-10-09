<?php

namespace LaminasTest\Mvc\Controller\Plugin\TestAsset;

use Laminas\Mvc\Controller\Plugin\AbstractPlugin;

class SamplePluginWithConstructor extends AbstractPlugin
{
    protected $bar;

    public function __construct($bar = 'baz')
    {
        $this->bar = $bar;
    }

    public function getBar()
    {
        return $this->bar;
    }
}
