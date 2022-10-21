<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\Controller\TestAsset;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Validator\ValidatorPluginManager;

class ControllerWithMixedConstructorParameters extends AbstractActionController
{
    public array $config;
    /** @var mixed */
    public $foo = 'foo';
    public ?array $options;
    public SampleInterface $sample;
    public ValidatorPluginManager $validators;

    /**
     * @param mixed $foo
     */
    public function __construct(
        SampleInterface $sample,
        ValidatorPluginManager $validators,
        array $config,
        $foo,
        ?array $options = null
    ) {
        $this->sample     = $sample;
        $this->validators = $validators;
        $this->config     = $config;
        $this->foo        = $foo;
        $this->options    = $options;
    }
}
