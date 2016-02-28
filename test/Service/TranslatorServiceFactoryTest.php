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
use Zend\I18n\Translator\LoaderPluginManager;
use Zend\I18n\Translator\TranslatorInterface;
use Zend\Mvc\Service\RoutePluginManagerFactory;
use Zend\Mvc\Service\ServiceManagerConfig;
use Zend\Mvc\Service\TranslatorServiceFactory;
use Zend\ServiceManager\ServiceManager;

class TranslatorServiceFactoryTest extends TestCase
{
    public function setUp()
    {
        $this->factory = new TranslatorServiceFactory();
        $this->services = new ServiceManager();
        $this->services->setService('TranslatorPluginManager', $this->getMock(LoaderPluginManager::class));
        $this->services->setAllowOverride(true);
    }

    public function testReturnsMvcTranslatorWithTranslatorInterfaceServiceComposedWhenPresent()
    {
        $i18nTranslator = $this->getMock(TranslatorInterface::class);
        $this->services->setService(TranslatorInterface::class, $i18nTranslator);

        $translator = $this->factory->__invoke($this->services, TranslatorInterface::class);
        $this->assertInstanceOf('Zend\Mvc\I18n\Translator', $translator);
        $this->assertSame($i18nTranslator, $translator->getTranslator());
    }

    public function testReturnsMvcTranslatorWithDummyTranslatorComposedWhenExtIntlIsNotAvailable()
    {
        if (extension_loaded('intl')) {
            $this->markTestSkipped('This test will only run if ext/intl is not present');
        }

        $translator = $this->factory->__invoke($this->services, TranslatorInterface::class);
        $this->assertInstanceOf('Zend\Mvc\I18n\Translator', $translator);
        $this->assertInstanceOf('Zend\Mvc\I18n\DummyTranslator', $translator->getTranslator());
    }

    public function testReturnsMvcTranslatorWithI18nTranslatorComposedWhenNoTranslatorInterfaceOrConfigServicesPresent()
    {
        if (!extension_loaded('intl')) {
            $this->markTestSkipped('This test will only run if ext/intl is present');
        }

        $translator = $this->factory->__invoke($this->services, TranslatorInterface::class);
        $this->assertInstanceOf('Zend\Mvc\I18n\Translator', $translator);
        $this->assertInstanceOf('Zend\I18n\Translator\Translator', $translator->getTranslator());
    }

    public function testReturnsTranslatorBasedOnConfigurationWhenNoTranslatorInterfaceServicePresent()
    {
        $config = ['translator' => [
            'locale' => 'en_US',
        ]];
        (new ServiceManagerConfig(['services' => ['config' => $config]]))->configureServiceManager($this->services);

        $translator = $this->factory->__invoke($this->services, TranslatorInterface::class);
        $this->assertInstanceOf('Zend\Mvc\I18n\Translator', $translator);
        $this->assertInstanceOf('Zend\I18n\Translator\Translator', $translator->getTranslator());

        return [
            'translator' => $translator->getTranslator(),
            'services'   => $this->services,
        ];
    }

    /**
     * In this test, we check to make sure that the TranslatorServiceFactory
     * correctly passes the LoaderPluginManager from the service locator into
     * the new Translator. This functionality is required so modules can add
     * their own translation loaders via config.
     *
     * @group 6244
     */
    public function testSetsPluginManagerFromServiceLocatorBasedOnConfiguration()
    {
        if (!extension_loaded('intl')) {
            $this->markTestSkipped('This test will only run if ext/intl is present');
        }

        //minimum bootstrap
        $applicationConfig = [
            'module_listener_options' => [],
            'modules' => [],
        ];
        $config = new ServiceManagerConfig(['services' => [
            'ApplicationConfig' => $applicationConfig,
        ]]);
        $serviceLocator = new ServiceManager();
        $config->configureServiceManager($serviceLocator);
        $serviceLocator->setAllowOverride(true);
        $serviceLocator->get('ModuleManager')->loadModules();
        $serviceLocator->get('Application')->bootstrap();

        $config = [
            'di' => [],
            'translator' => [
                'locale' => 'en_US',
            ],
        ];

        $serviceLocator->setService('config', $config);
        $serviceLocator->setAllowOverride(false);

        $translator = $this->factory->__invoke($serviceLocator, TranslatorInterface::class);

        $this->assertEquals(
            $serviceLocator->get('TranslatorPluginManager'),
            $translator->getPluginManager()
        );
    }

    public function testReturnsTranslatorBasedOnConfigurationWhenNoTranslatorInterfaceServicePresentWithMinimumBootstrap()
    {
        if (!extension_loaded('intl')) {
            $this->markTestSkipped('This test will only run if ext/intl is present');
        }

        //minimum bootstrap
        $applicationConfig = [
            'module_listener_options' => [],
            'modules' => [],
        ];
        $config = new ServiceManagerConfig(['services' => [
            'ApplicationConfig' => $applicationConfig,
        ]]);
        $serviceLocator = new ServiceManager();
        $config->configureServiceManager($serviceLocator);
        $serviceLocator->setAllowOverride(true);
        $serviceLocator->get('ModuleManager')->loadModules();
        $serviceLocator->get('Application')->bootstrap();

        $config = [
            'di' => [],
            'translator' => [
                'locale' => 'en_US',
            ],
        ];

        $serviceLocator->setService('config', $config);
        $serviceLocator->setAllowOverride(false);

        //#5959
        //get any plugins with AbstractPluginManagerFactory
        $routePluginManagerFactory = new RoutePluginManagerFactory;
        $routePluginManager = $routePluginManagerFactory($serviceLocator, 'RoutePluginManager');

        $translator = $this->factory->__invoke($serviceLocator, TranslatorInterface::class);
        $this->assertInstanceOf('Zend\Mvc\I18n\Translator', $translator);
        $this->assertInstanceOf('Zend\I18n\Translator\Translator', $translator->getTranslator());
    }

    /**
     * @depends testReturnsTranslatorBasedOnConfigurationWhenNoTranslatorInterfaceServicePresent
     */
    public function testSetsInstantiatedI18nTranslatorInstanceInServiceManager($dependencies)
    {
        $translator = $dependencies['translator'];
        $services   = $dependencies['services'];
        $this->assertTrue($services->has('Zend\I18n\Translator\TranslatorInterface'));
        $this->assertSame($translator, $services->get('Zend\I18n\Translator\TranslatorInterface'));
    }

    public function testPrefersTranslatorInterfaceImplementationOverConfig()
    {
        $config = ['translator' => [
            'locale' => 'en_US',
        ]];
        $i18nTranslator = $this->getMock(TranslatorInterface::class);
        $this->services->setService('config', $config);
        $this->services->setService(TranslatorInterface::class, $i18nTranslator);

        $translator = $this->factory->__invoke($this->services, TranslatorInterface::class);
        $this->assertInstanceOf('Zend\Mvc\I18n\Translator', $translator);
        $this->assertSame($i18nTranslator, $translator->getTranslator());
    }

    public function testReturnsDummyTranslatorWhenTranslatorConfigIsBooleanFalse()
    {
        $config = ['translator' => false];
        $this->services->setService('config', $config);

        $translator = $this->factory->__invoke($this->services, TranslatorInterface::class);
        $this->assertInstanceOf('Zend\Mvc\I18n\Translator', $translator);
        $this->assertInstanceOf('Zend\Mvc\I18n\DummyTranslator', $translator->getTranslator());
    }
}
