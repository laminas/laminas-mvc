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
use ReflectionClass;
use ReflectionProperty;
use Zend\Mvc\Service\ServiceListenerFactory;
use Zend\ServiceManager\ServiceManager;

class ServiceListenerFactoryTest extends TestCase
{
    public function setUp()
    {
        $sm = $this->sm = $this->getMockBuilder(ServiceManager::class)
                               ->setMethods(['get'])
                               ->getMock();

        $this->factory  = new ServiceListenerFactory();
    }

    private function isServiceManagerV3()
    {
        $r = new ReflectionClass(ServiceManager::class);
        return $r->hasMethod('configure');
    }

    /**
     * @expectedException        Zend\ServiceManager\Exception\ServiceNotCreatedException
     * @expectedExceptionMessage The value of service_listener_options must be an array, string given.
     */
    public function testInvalidOptionType()
    {
        $this->sm->expects($this->once())
                 ->method('get')
                 ->will($this->returnValue(['service_listener_options' => 'string']));

        $this->factory->__invoke($this->sm, 'ServiceListener');
    }

    /**
     * @expectedException        Zend\ServiceManager\Exception\ServiceNotCreatedException
     * @expectedExceptionMessage Invalid service listener options detected, 0 array must contain service_manager key.
     */
    public function testMissingServiceManager()
    {
        $config['service_listener_options'][0]['service_manager'] = null;
        $config['service_listener_options'][0]['config_key']      = 'test';
        $config['service_listener_options'][0]['interface']       = 'test';
        $config['service_listener_options'][0]['method']          = 'test';

        $this->sm->expects($this->once())
                 ->method('get')
                 ->will($this->returnValue($config));

        $this->factory->__invoke($this->sm, 'ServiceListener');
    }

    /**
     * @expectedException        Zend\ServiceManager\Exception\ServiceNotCreatedException
     * @expectedExceptionMessage Invalid service listener options detected, service_manager must be a string, integer given.
     */
    public function testInvalidTypeServiceManager()
    {
        $config['service_listener_options'][0]['service_manager'] = 1;
        $config['service_listener_options'][0]['config_key']      = 'test';
        $config['service_listener_options'][0]['interface']       = 'test';
        $config['service_listener_options'][0]['method']          = 'test';

        $this->sm->expects($this->once())
                 ->method('get')
                 ->will($this->returnValue($config));

        $this->factory->__invoke($this->sm, 'ServiceListener');
    }

    /**
     * @expectedException        Zend\ServiceManager\Exception\ServiceNotCreatedException
     * @expectedExceptionMessage Invalid service listener options detected, 0 array must contain config_key key.
     */
    public function testMissingConfigKey()
    {
        $config['service_listener_options'][0]['service_manager'] = 'test';
        $config['service_listener_options'][0]['config_key']      = null;
        $config['service_listener_options'][0]['interface']       = 'test';
        $config['service_listener_options'][0]['method']          = 'test';

        $this->sm->expects($this->once())
                 ->method('get')
                 ->will($this->returnValue($config));

        $this->factory->__invoke($this->sm, 'ServiceListener');
    }

    /**
     * @expectedException        Zend\ServiceManager\Exception\ServiceNotCreatedException
     * @expectedExceptionMessage Invalid service listener options detected, config_key must be a string, integer given.
     */
    public function testInvalidTypeConfigKey()
    {
        $config['service_listener_options'][0]['service_manager'] = 'test';
        $config['service_listener_options'][0]['config_key']      = 1;
        $config['service_listener_options'][0]['interface']       = 'test';
        $config['service_listener_options'][0]['method']          = 'test';

        $this->sm->expects($this->once())
                 ->method('get')
                 ->will($this->returnValue($config));

        $this->factory->__invoke($this->sm, 'ServiceListener');
    }

    /**
     * @expectedException        Zend\ServiceManager\Exception\ServiceNotCreatedException
     * @expectedExceptionMessage Invalid service listener options detected, 0 array must contain interface key.
     */
    public function testMissingInterface()
    {
        $config['service_listener_options'][0]['service_manager'] = 'test';
        $config['service_listener_options'][0]['config_key']      = 'test';
        $config['service_listener_options'][0]['interface']       = null;
        $config['service_listener_options'][0]['method']          = 'test';

        $this->sm->expects($this->once())
                 ->method('get')
                 ->will($this->returnValue($config));

        $this->factory->__invoke($this->sm, 'ServiceListener');
    }

    /**
     * @expectedException        Zend\ServiceManager\Exception\ServiceNotCreatedException
     * @expectedExceptionMessage Invalid service listener options detected, interface must be a string, integer given.
     */
    public function testInvalidTypeInterface()
    {
        $config['service_listener_options'][0]['service_manager'] = 'test';
        $config['service_listener_options'][0]['config_key']      = 'test';
        $config['service_listener_options'][0]['interface']       = 1;
        $config['service_listener_options'][0]['method']          = 'test';

        $this->sm->expects($this->once())
                 ->method('get')
                 ->will($this->returnValue($config));

        $this->factory->__invoke($this->sm, 'ServiceListener');
    }

    /**
     * @expectedException        Zend\ServiceManager\Exception\ServiceNotCreatedException
     * @expectedExceptionMessage Invalid service listener options detected, 0 array must contain method key.
     */
    public function testMissingMethod()
    {
        $config['service_listener_options'][0]['service_manager'] = 'test';
        $config['service_listener_options'][0]['config_key']      = 'test';
        $config['service_listener_options'][0]['interface']       = 'test';
        $config['service_listener_options'][0]['method']          = null;

        $this->sm->expects($this->once())
                 ->method('get')
                 ->will($this->returnValue($config));

        $this->factory->__invoke($this->sm, 'ServiceListener');
    }

    /**
     * @expectedException        Zend\ServiceManager\Exception\ServiceNotCreatedException
     * @expectedExceptionMessage Invalid service listener options detected, method must be a string, integer given.
     */
    public function testInvalidTypeMethod()
    {
        $config['service_listener_options'][0]['service_manager'] = 'test';
        $config['service_listener_options'][0]['config_key']      = 'test';
        $config['service_listener_options'][0]['interface']       = 'test';
        $config['service_listener_options'][0]['method']          = 1;

        $this->sm->expects($this->once())
                 ->method('get')
                 ->will($this->returnValue($config));

        $this->factory->__invoke($this->sm, 'ServiceListener');
    }

    public function testDefinesExpectedAliasesForConsole()
    {
        $r = new ReflectionProperty($this->factory, 'defaultServiceConfig');
        $r->setAccessible(true);
        $config = $r->getValue($this->factory);

        $this->assertArrayHasKey('aliases', $config, 'Missing aliases from default service config');
        $this->assertArrayHasKey('console', $config['aliases'], 'Missing "console" alias from default service config');
        $this->assertArrayHasKey('Console', $config['aliases'], 'Missing "Console" alias from default service config');
    }

    public function testDefinesExpectedApplicationAliasesUnderV3()
    {
        if (! $this->isServiceManagerV3()) {
            $this->markTestSkipped('Application aliases are only defined under zend-servicemanager v3');
        }

        $r = new ReflectionProperty($this->factory, 'defaultServiceConfig');
        $r->setAccessible(true);
        $config = $r->getValue($this->factory);

        // @codingStandardsIgnoreStart
        $this->assertArrayHasKey('aliases', $config, 'Missing aliases from default service config');
        $this->assertArrayHasKey('application', $config['aliases'], 'Missing "application" alias from default service config');
        // @codingStandardsIgnoreEnd
    }

    public function testDefinesExpectedConfigAliasesUnderV3()
    {
        if (! $this->isServiceManagerV3()) {
            $this->markTestSkipped('Config aliases are only defined under zend-servicemanager v3');
        }

        $r = new ReflectionProperty($this->factory, 'defaultServiceConfig');
        $r->setAccessible(true);
        $config = $r->getValue($this->factory);

        $this->assertArrayHasKey('aliases', $config, 'Missing aliases from default service config');
        $this->assertArrayHasKey('Config', $config['aliases'], 'Missing "Config" alias from default service config');
    }

    public function testDefinesExpectedRequestAliasesUnderV3()
    {
        if (! $this->isServiceManagerV3()) {
            $this->markTestSkipped('Request aliases are only defined under zend-servicemanager v3');
        }

        $r = new ReflectionProperty($this->factory, 'defaultServiceConfig');
        $r->setAccessible(true);
        $config = $r->getValue($this->factory);

        $this->assertArrayHasKey('aliases', $config, 'Missing aliases from default service config');
        $this->assertArrayHasKey('request', $config['aliases'], 'Missing "request" alias from default service config');
    }

    public function testDefinesExpectedResponseFactories()
    {
        if (! $this->isServiceManagerV3()) {
            $this->markTestSkipped('Response aliases are only defined under zend-servicemanager v3');
        }

        $r = new ReflectionProperty($this->factory, 'defaultServiceConfig');
        $r->setAccessible(true);
        $config = $r->getValue($this->factory);

        // @codingStandardsIgnoreStart
        $this->assertArrayHasKey('aliases', $config, 'Missing aliases from default service config');
        $this->assertArrayHasKey('response', $config['aliases'], 'Missing "response" alias from default service config');
        // @codingStandardsIgnoreEnd
    }
}
