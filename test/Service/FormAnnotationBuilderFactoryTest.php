<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Mvc\Service;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\EventManager\EventManagerInterface;
use Zend\Mvc\Service\FormAnnotationBuilderFactory;
use Zend\ServiceManager\ServiceManager;

class FormAnnotationBuilderFactoryTest extends TestCase
{
    public function testCreateService()
    {
        $serviceLocator = new ServiceManager();
        $this->prepareServiceLocator($serviceLocator, []);

        $sut = new FormAnnotationBuilderFactory();

        $this->assertInstanceOf('\Zend\Form\Annotation\AnnotationBuilder', $sut->createService($serviceLocator));
    }

    public function testCreateServiceSetsPreserveDefinedOrder()
    {


        $serviceLocator = new ServiceManager();
        $config = ['form_annotation_builder' => ['preserve_defined_order' => true]];
        $this->prepareServiceLocator($serviceLocator, $config);

        $sut = new FormAnnotationBuilderFactory();

        $service = $sut->createService($serviceLocator);

        $this->assertTrue($service->preserveDefinedOrder(), 'Preserve defined order was not set correctly');
    }

    /**
     * @param ServiceManager $manager
     * @param array          $config
     *
     * @return void
     */
    private function prepareServiceLocator(ServiceManager $manager, array $config)
    {
        $mockElementManager = $this
            ->getMockBuilder('Zend\Form\FormElementManager')
            ->disableOriginalConstructor()
            ->getMock();

        $mockEventManager = $this
            ->getMockBuilder(EventManagerInterface::class)
            ->getMock();

        $manager->setService('config', $config);
        $manager->setService('FormElementManager', $mockElementManager);
        $manager->setService('EventManager', $mockEventManager);
    }
}
