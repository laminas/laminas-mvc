<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Service;

use Laminas\EventManager\EventManagerInterface;
use Laminas\Form\Annotation\AnnotationBuilder;
use Laminas\Mvc\Service\FormAnnotationBuilderFactory;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit_Framework_TestCase as TestCase;

class FormAnnotationBuilderFactoryTest extends TestCase
{
    public function testCreateService()
    {
        $serviceLocator = new ServiceManager();
        $this->prepareServiceLocator($serviceLocator, []);

        $sut = new FormAnnotationBuilderFactory();

        $this->assertInstanceOf('\Laminas\Form\Annotation\AnnotationBuilder', $sut->createService($serviceLocator));
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


    public function testInjectFactoryInCorrectOrderV2()
    {
        $serviceLocator = new ServiceManager();
        if (method_exists($serviceLocator, 'build')) {
            $this->markTestSkipped('`laminas/laminas-servicemanager` v2 needed, skipped test');
        }

        $this->prepareServiceLocator($serviceLocator, []);
        $serviceLocator->setAllowOverride(true);

        $mockElementManager = $this
            ->getMockBuilder('Laminas\Form\FormElementManager\FormElementManagerV2Polyfill')
            ->disableOriginalConstructor()
            ->getMock();
        $serviceLocator->setService('FormElementManager', $mockElementManager);

        $mockElementManager
            ->expects($this->once())
            ->method('injectFactory')
            ->with($this->callback(function ($annotationBuilder) {
                return $annotationBuilder instanceof AnnotationBuilder;
            }), $serviceLocator);

        $sut = new FormAnnotationBuilderFactory();
        $sut->createService($serviceLocator);
    }

    public function testInjectFactoryInCorrectOrderV3()
    {
        $serviceLocator = new ServiceManager();
        if (!method_exists($serviceLocator, 'build')) {
            $this->markTestSkipped('`laminas/laminas-servicemanager` v3 needed, skipped test');
        }
        $this->prepareServiceLocator($serviceLocator, []);
        $serviceLocator->setAllowOverride(true);

        $mockElementManager = $this
            ->getMockBuilder('Laminas\Form\FormElementManager\FormElementManagerV3Polyfill')
            ->disableOriginalConstructor()
            ->getMock();
        $serviceLocator->setService('FormElementManager', $mockElementManager);

        $mockElementManager
            ->expects($this->once())
            ->method('injectFactory')
            ->with($serviceLocator, $this->callback(function ($annotationBuilder) {
                return $annotationBuilder instanceof AnnotationBuilder;
            }));

        $sut = new FormAnnotationBuilderFactory();
        $sut->__invoke($serviceLocator, AnnotationBuilder::class);
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
            ->getMockBuilder('Laminas\Form\FormElementManager')
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
