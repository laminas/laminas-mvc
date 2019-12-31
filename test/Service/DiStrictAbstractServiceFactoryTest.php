<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Service;

use Laminas\Di\Config;
use Laminas\Di\Di;
use Laminas\Mvc\Service\DiStrictAbstractServiceFactory;
use Laminas\ServiceManager\ServiceManager;

class DiStrictAbstractServiceFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testSetGetAllowedServiceNames()
    {
        $instance = new DiStrictAbstractServiceFactory($this->getMock('Laminas\Di\Di'));
        $instance->setAllowedServiceNames(array('first-service', 'second-service'));
        $allowedServices = $instance->getAllowedServiceNames();
        $this->assertCount(2, $allowedServices);
        $this->assertContains('first-service', $allowedServices);
        $this->assertContains('second-service', $allowedServices);
    }

    public function testWillOnlyCreateServiceInWhitelist()
    {
        $instance = new DiStrictAbstractServiceFactory($this->getMock('Laminas\Di\Di'));
        $instance->setAllowedServiceNames(array('a-whitelisted-service-name'));
        $im = $instance->instanceManager();
        $im->addSharedInstance(new \stdClass(), 'a-whitelisted-service-name');
        $locator = $this->getMock('Laminas\ServiceManager\ServiceLocatorInterface');

        $this->assertTrue($instance->canCreateServiceWithName($locator, 'a-whitelisted-service-name', 'a-whitelisted-service-name'));
        $this->assertInstanceOf('stdClass', $instance->createServiceWithName($locator, 'a-whitelisted-service-name', 'a-whitelisted-service-name'));

        $this->assertFalse($instance->canCreateServiceWithName($locator, 'not-whitelisted', 'not-whitelisted'));
        $this->setExpectedException('Laminas\ServiceManager\Exception\InvalidServiceNameException');
        $instance->createServiceWithName($locator, 'not-whitelisted', 'not-whitelisted');
    }

    public function testWillFetchDependenciesFromServiceManagerBeforeDi()
    {
        $controllerName = __NAMESPACE__ . '\TestAsset\ControllerWithDependencies';
        $config = new Config(array(
            'instance' => array(
                $controllerName => array('parameters' => array('injected' => 'stdClass')),
            ),
        ));
        $locator = new ServiceManager();
        $testService = new \stdClass();
        $locator->setService('stdClass', $testService);

        $di = new Di;
        $config->configure($di);
        $instance = new DiStrictAbstractServiceFactory($di, DiStrictAbstractServiceFactory::USE_SL_BEFORE_DI);
        $instance->setAllowedServiceNames(array($controllerName));
        $service = $instance->createServiceWithName($locator, $controllerName, $controllerName);
        $this->assertSame($testService, $service->injectedValue);
    }
}
