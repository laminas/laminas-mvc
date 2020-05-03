<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Service;

use Laminas\Mvc\Service\ServiceListenerFactory;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;

class ServiceListenerFactoryTest extends TestCase
{
    protected function setUp() : void
    {
        $sm = $this->sm = $this->getMockBuilder(ServiceManager::class)
                               ->setMethods(['get'])
                               ->getMock();

        $this->factory  = new ServiceListenerFactory();
    }

    public function testInvalidOptionType()
    {
        $this->expectException(ServiceNotCreatedException::class);
        $this->expectExceptionMessage('The value of service_listener_options must be an array, string given.');
        $this->sm->expects($this->once())
                 ->method('get')
                 ->will($this->returnValue(['service_listener_options' => 'string']));

        $this->factory->__invoke($this->sm, 'ServiceListener');
    }

    public function testMissingServiceManager()
    {
        $this->expectException(ServiceNotCreatedException::class);
        $this->expectExceptionMessage(
            'Invalid service listener options detected, 0 array must contain service_manager key.'
        );
        $config['service_listener_options'][0]['service_manager'] = null;
        $config['service_listener_options'][0]['config_key']      = 'test';
        $config['service_listener_options'][0]['interface']       = 'test';
        $config['service_listener_options'][0]['method']          = 'test';

        $this->sm->expects($this->once())
                 ->method('get')
                 ->will($this->returnValue($config));

        $this->factory->__invoke($this->sm, 'ServiceListener');
    }

    public function testInvalidTypeServiceManager()
    {
        $this->expectException(
            ServiceNotCreatedException::class
        );
        $this->expectExceptionMessage(
            'Invalid service listener options detected, service_manager must be a string, integer given.'
        );
        $config['service_listener_options'][0]['service_manager'] = 1;
        $config['service_listener_options'][0]['config_key']      = 'test';
        $config['service_listener_options'][0]['interface']       = 'test';
        $config['service_listener_options'][0]['method']          = 'test';

        $this->sm->expects($this->once())
                 ->method('get')
                 ->will($this->returnValue($config));

        $this->factory->__invoke($this->sm, 'ServiceListener');
    }

    public function testMissingConfigKey()
    {
        $this->expectException(ServiceNotCreatedException::class);
        $this->expectExceptionMessage(
            'Invalid service listener options detected, 0 array must contain config_key key.'
        );
        $config['service_listener_options'][0]['service_manager'] = 'test';
        $config['service_listener_options'][0]['config_key']      = null;
        $config['service_listener_options'][0]['interface']       = 'test';
        $config['service_listener_options'][0]['method']          = 'test';

        $this->sm->expects($this->once())
                 ->method('get')
                 ->will($this->returnValue($config));

        $this->factory->__invoke($this->sm, 'ServiceListener');
    }

    public function testInvalidTypeConfigKey()
    {
        $this->expectException(ServiceNotCreatedException::class);
        $this->expectExceptionMessage(
            'Invalid service listener options detected, config_key must be a string, integer given.'
        );
        $config['service_listener_options'][0]['service_manager'] = 'test';
        $config['service_listener_options'][0]['config_key']      = 1;
        $config['service_listener_options'][0]['interface']       = 'test';
        $config['service_listener_options'][0]['method']          = 'test';

        $this->sm->expects($this->once())
                 ->method('get')
                 ->will($this->returnValue($config));

        $this->factory->__invoke($this->sm, 'ServiceListener');
    }

    public function testMissingInterface()
    {
        $this->expectException(ServiceNotCreatedException::class);
        $this->expectExceptionMessage('Invalid service listener options detected, 0 array must contain interface key.');
        $config['service_listener_options'][0]['service_manager'] = 'test';
        $config['service_listener_options'][0]['config_key']      = 'test';
        $config['service_listener_options'][0]['interface']       = null;
        $config['service_listener_options'][0]['method']          = 'test';

        $this->sm->expects($this->once())
                 ->method('get')
                 ->will($this->returnValue($config));

        $this->factory->__invoke($this->sm, 'ServiceListener');
    }

    public function testInvalidTypeInterface()
    {
        $this->expectException(ServiceNotCreatedException::class);
        $this->expectExceptionMessage(
            'Invalid service listener options detected, interface must be a string, integer given.'
        );
        $config['service_listener_options'][0]['service_manager'] = 'test';
        $config['service_listener_options'][0]['config_key']      = 'test';
        $config['service_listener_options'][0]['interface']       = 1;
        $config['service_listener_options'][0]['method']          = 'test';

        $this->sm->expects($this->once())
                 ->method('get')
                 ->will($this->returnValue($config));

        $this->factory->__invoke($this->sm, 'ServiceListener');
    }

    public function testMissingMethod()
    {
        $this->expectException(ServiceNotCreatedException::class);
        $this->expectExceptionMessage('Invalid service listener options detected, 0 array must contain method key.');
        $config['service_listener_options'][0]['service_manager'] = 'test';
        $config['service_listener_options'][0]['config_key']      = 'test';
        $config['service_listener_options'][0]['interface']       = 'test';
        $config['service_listener_options'][0]['method']          = null;

        $this->sm->expects($this->once())
                 ->method('get')
                 ->will($this->returnValue($config));

        $this->factory->__invoke($this->sm, 'ServiceListener');
    }

    public function testInvalidTypeMethod()
    {
        $this->expectException(ServiceNotCreatedException::class);
        $this->expectExceptionMessage(
            'Invalid service listener options detected, method must be a string, integer given.'
        );
        $config['service_listener_options'][0]['service_manager'] = 'test';
        $config['service_listener_options'][0]['config_key']      = 'test';
        $config['service_listener_options'][0]['interface']       = 'test';
        $config['service_listener_options'][0]['method']          = 1;

        $this->sm->expects($this->once())
                 ->method('get')
                 ->will($this->returnValue($config));

        $this->factory->__invoke($this->sm, 'ServiceListener');
    }
}
