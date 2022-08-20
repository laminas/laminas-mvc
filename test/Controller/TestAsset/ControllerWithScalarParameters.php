<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\Controller\TestAsset;

use Laminas\Mvc\Controller\AbstractActionController;

class ControllerWithScalarParameters extends AbstractActionController
{
    /** @var mixed */
    public $foo = 'foo';
    /** @var mixed */
    public $bar = 'bar';

    /**
     * @param mixed $foo
     * @param mixed $bar
     */
    public function __construct($foo, $bar)
    {
        $this->foo = $foo;
        $this->bar = $bar;
    }
}
