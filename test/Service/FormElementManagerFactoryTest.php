<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Service;

use Laminas\Form\FormElementManager;
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
        $this->markTestIncomplete('Re-enable once laminas-form is migrated to laminas-servicemanager v3');

        $formElementManagerFactory = new FormElementManagerFactory();
        $this->services = new ServiceManager([
            'factories' => [
                'FormElementManager' => $formElementManagerFactory,
            ],
            'services' => [
                'config' => [],
            ],
        ]);
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
}
