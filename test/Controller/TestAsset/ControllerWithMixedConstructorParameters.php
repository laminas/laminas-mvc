<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Mvc\Controller\TestAsset;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Validator\ValidatorPluginManager;

class ControllerWithMixedConstructorParameters extends AbstractActionController
{
    public $config;
    public $foo = 'foo';
    public $options;
    public $sample;
    public $validators;

    public function __construct(
        SampleInterface $sample,
        ValidatorPluginManager $validators,
        array $config,
        $foo,
        array $options = null
    ) {
        $this->sample = $sample;
        $this->validators = $validators;
        $this->config = $config;
        $this->foo = $foo;
        $this->options = $options;
    }
}
