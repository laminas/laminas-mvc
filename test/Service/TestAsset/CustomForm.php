<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Service\TestAsset;

use Laminas\Form\Form;
use Laminas\Stdlib\Hydrator\ClassMethods as ClassMethodsHydrator;

class CustomForm extends Form
{
    public function __construct()
    {
        parent::__construct('test_form');

        $this->setAttribute('method', 'post')
             ->setHydrator(new ClassMethodsHydrator());

        $this->add(array(
            'name' => 'submit',
            'attributes' => array(
                'type' => 'submit'
            )
        ));
    }
}
