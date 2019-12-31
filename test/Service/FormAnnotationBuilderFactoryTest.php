<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Service;

use Laminas\Mvc\Service\FormAnnotationBuilderFactory;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit_Framework_TestCase as TestCase;

class FormAnnotationBuilderFactoryTest extends TestCase
{
    public function testCreateService()
    {
        $mockElementManager = $this->getMock('Laminas\Form\FormElementManager');

        $serviceLocator = new ServiceManager();
        $serviceLocator->setService('FormElementManager', $mockElementManager);
        $serviceLocator->setService('Config', []);

        $sut = new FormAnnotationBuilderFactory();

        $this->assertInstanceOf('\Laminas\Form\Annotation\AnnotationBuilder', $sut->createService($serviceLocator));
    }

    public function testCreateServiceSetsPreserveDefinedOrder()
    {
        $mockElementManager = $this->getMock('Laminas\Form\FormElementManager');

        $serviceLocator = new ServiceManager();
        $serviceLocator->setService('FormElementManager', $mockElementManager);
        $config = ['form_annotation_builder' => ['preserve_defined_order' => true]];
        $serviceLocator->setService('Config', $config);

        $sut = new FormAnnotationBuilderFactory();

        $service = $sut->createService($serviceLocator);

        $this->assertTrue($service->preserveDefinedOrder(), 'Preserve defined order was not set correctly');
    }
}
