<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\Controller\TestAsset;

use Laminas\Mvc\Controller\AbstractActionController;

class ControllerWithScalarParameters extends AbstractActionController
{
    public mixed $foo = 'foo';
    public mixed $bar = 'bar';

    public function __construct(mixed $foo, mixed $bar)
    {
        $this->foo = $foo;
        $this->bar = $bar;
    }
}
