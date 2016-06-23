<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Mvc\Controller\TestAsset;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Validator\ValidatorPluginManager;

class ControllerAcceptingWellKnownServicesAsConstructorParameters extends AbstractActionController
{
    public $validators;

    public function __construct(ValidatorPluginManager $validators)
    {
        $this->validators = $validators;
    }
}
