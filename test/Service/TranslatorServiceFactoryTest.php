<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Service;

use Laminas\Mvc\Service\TranslatorServiceFactory;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit_Framework_TestCase as TestCase;

class TranslatorServiceFactoryTest extends TestCase
{
    public function setUp()
    {
        $this->factory = new TranslatorServiceFactory();
        $this->services = new ServiceManager();
    }

    public function testReturnsMvcTranslatorWithTranslatorInterfaceServiceComposedWhenPresent()
    {
        $i18nTranslator = $this->getMock('Laminas\I18n\Translator\TranslatorInterface');
        $this->services->setService('Laminas\I18n\Translator\TranslatorInterface', $i18nTranslator);

        $translator = $this->factory->createService($this->services);
        $this->assertInstanceOf('Laminas\Mvc\I18n\Translator', $translator);
        $this->assertSame($i18nTranslator, $translator->getTranslator());
    }

    public function testReturnsMvcTranslatorWithDummyTranslatorComposedWhenExtIntlIsNotAvailable()
    {
        if (extension_loaded('intl')) {
            $this->markTestSkipped('This test will only run if ext/intl is not present');
        }

        $translator = $this->factory->createService($this->services);
        $this->assertInstanceOf('Laminas\Mvc\I18n\Translator', $translator);
        $this->assertInstanceOf('Laminas\Mvc\I18n\DummyTranslator', $translator->getTranslator());
    }

    public function testReturnsMvcTranslatorWithI18nTranslatorComposedWhenNoTranslatorInterfaceOrConfigServicesPresent()
    {
        if (!extension_loaded('intl')) {
            $this->markTestSkipped('This test will only run if ext/intl is present');
        }

        $translator = $this->factory->createService($this->services);
        $this->assertInstanceOf('Laminas\Mvc\I18n\Translator', $translator);
        $this->assertInstanceOf('Laminas\I18n\Translator\Translator', $translator->getTranslator());
    }

    public function testReturnsTranslatorBasedOnConfigurationWhenNoTranslatorInterfaceServicePresent()
    {
        if (!extension_loaded('intl')) {
            $this->markTestSkipped('This test will only run if ext/intl is present');
        }

        $config = array('translator' => array(
            'locale' => 'en_US',
        ));
        $this->services->setService('Config', $config);

        $translator = $this->factory->createService($this->services);
        $this->assertInstanceOf('Laminas\Mvc\I18n\Translator', $translator);
        $this->assertInstanceOf('Laminas\I18n\Translator\Translator', $translator->getTranslator());

        return array(
            'translator' => $translator->getTranslator(),
            'services'   => $this->services,
        );
    }

    /**
     * @depends testReturnsTranslatorBasedOnConfigurationWhenNoTranslatorInterfaceServicePresent
     */
    public function testSetsInstantiatedI18nTranslatorInstanceInServiceManager($dependencies)
    {
        $translator = $dependencies['translator'];
        $services   = $dependencies['services'];
        $this->assertTrue($services->has('Laminas\I18n\Translator\TranslatorInterface'));
        $this->assertSame($translator, $services->get('Laminas\I18n\Translator\TranslatorInterface'));
    }

    public function testPrefersTranslatorInterfaceImplementationOverConfig()
    {
        $config = array('translator' => array(
            'locale' => 'en_US',
        ));
        $this->services->setService('Config', $config);

        $i18nTranslator = $this->getMock('Laminas\I18n\Translator\TranslatorInterface');
        $this->services->setService('Laminas\I18n\Translator\TranslatorInterface', $i18nTranslator);

        $translator = $this->factory->createService($this->services);
        $this->assertInstanceOf('Laminas\Mvc\I18n\Translator', $translator);
        $this->assertSame($i18nTranslator, $translator->getTranslator());
    }

    public function testReturnsDummyTranslatorWhenTranslatorConfigIsBooleanFalse()
    {
        $config = array('translator' => false);
        $this->services->setService('Config', $config);
        $translator = $this->factory->createService($this->services);
        $this->assertInstanceOf('Laminas\Mvc\I18n\Translator', $translator);
        $this->assertInstanceOf('Laminas\Mvc\I18n\DummyTranslator', $translator->getTranslator());
    }
}
