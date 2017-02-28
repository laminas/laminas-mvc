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
use Zend\Mvc\Service\FormElementManagerFactory;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\ServiceManager;
use Zend\Form\FormElementManager;

class FormElementManagerFactoryTest extends TestCase
{
    /**
     * @var ServiceManager
     */
    protected $services;

    /**
     * @var \Zend\Mvc\Controller\ControllerManager
     */
    protected $loader;

    public function setUp()
    {
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
        $this->assertInstanceof('Zend\Form\FormElementManager', $formElementManager);
    }

    public function testWillInstantiateFormFromInvokable()
    {
        $formElementManager = $this->services->get('FormElementManager');
        $form = $formElementManager->get('form');
        $this->assertInstanceof('Zend\Form\Form', $form);
    }
}
