<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\Controller\Plugin\TestAsset;

use Laminas\Mvc\Controller\Plugin\AbstractPlugin;

class SamplePluginWithConstructor extends AbstractPlugin
{
    /** @var mixed */
    protected $bar;

    /**
     * @param mixed $bar
     */
    public function __construct($bar = 'baz')
    {
        $this->bar = $bar;
    }

    /**
     * @return mixed
     */
    public function getBar()
    {
        return $this->bar;
    }
}
