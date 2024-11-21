<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\Controller\Plugin\TestAsset;

use Laminas\Mvc\Controller\Plugin\AbstractPlugin;

class SamplePluginWithConstructor extends AbstractPlugin
{
    public function __construct(protected string|array $bar = 'baz')
    {
    }

    public function getBar(): string|array
    {
        return $this->bar;
    }
}
