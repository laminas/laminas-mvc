<?php

namespace LaminasTest\Mvc\Controller\Plugin\TestAsset;


use Laminas\Form\Fieldset;
use Laminas\InputFilter\InputFilterProviderInterface;

class LinksFieldset extends Fieldset implements  InputFilterProviderInterface
{
    public function __construct()
    {
        parent::__construct('link');
        $this->add(array(
            'name' => 'foobar',
        ));
    }

    public function getInputFilterSpecification()
    {
        return array(
            'email' => array(
                'required' => false,
            ),
        );
    }
}
