<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Service;

use ArrayObject;
use Laminas\Form\FormElementManager;
use Laminas\Mvc\Exception;
use Laminas\Mvc\Service\DiAbstractServiceFactoryFactory;
use Laminas\Mvc\Service\DiFactory;
use Laminas\Mvc\Service\DiServiceInitializerFactory;
use Laminas\Mvc\Service\FormElementManagerFactory;
use Laminas\ServiceManager\Config;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit_Framework_TestCase as TestCase;

class FormElementManagerFactoryTest extends TestCase
{
    /**
     * @var ServiceManager
     */
    protected $services;

    /**
     * @var \Laminas\Mvc\Controller\ControllerManager
     */
    protected $loader;

    public function setUp()
    {
        $formElementManagerFactory = new FormElementManagerFactory();
        $config = new ArrayObject(array('di' => array()));
        $this->services = new ServiceManager();
        $this->services->setService('Laminas\ServiceManager\ServiceLocatorInterface', $this->services);
        $this->services->setFactory('FormElementManager', $formElementManagerFactory);
        $this->services->setService('Config', $config);
        $this->services->setFactory('Di', new DiFactory());
        $this->services->setFactory('DiAbstractServiceFactory', new DiAbstractServiceFactoryFactory());
        $this->services->setFactory('DiServiceInitializer', new DiServiceInitializerFactory());
    }

    public function testWillGetFormElementManager()
    {
        $formElementManager = $this->services->get('FormElementManager');
        $this->assertInstanceof('Laminas\Form\FormElementManager', $formElementManager);
    }

    public function testWillInstantiateFormFromInvokable()
    {
        $formElementManager = $this->services->get('FormElementManager');
        $form = $formElementManager->get('form');
        $this->assertInstanceof('Laminas\Form\Form', $form);
    }

    public function testWillInstantiateFormFromDiAbstractFactory()
    {
        //without DiAbstractFactory
        $standaloneFormElementManager = new FormElementManager();
        $this->assertFalse($standaloneFormElementManager->has('LaminasTest\Mvc\Service\TestAsset\CustomForm'));
        //with DiAbstractFactory
        $formElementManager = $this->services->get('FormElementManager');
        $this->assertTrue($formElementManager->has('LaminasTest\Mvc\Service\TestAsset\CustomForm'));
        $form = $formElementManager->get('LaminasTest\Mvc\Service\TestAsset\CustomForm');
        $this->assertInstanceof('LaminasTest\Mvc\Service\TestAsset\CustomForm', $form);
    }
}
