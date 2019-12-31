<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

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
