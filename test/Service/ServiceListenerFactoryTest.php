<?php

namespace LaminasTest\Mvc\Service;

use Laminas\Mvc\Service\ServiceListenerFactory;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;

class ServiceListenerFactoryTest extends TestCase
{
    public function setUp(): void
    {
        $this->sm = $this->getMockBuilder(ServiceManager::class)
                               ->setMethods(['get'])
                               ->getMock();

        $this->factory  = new ServiceListenerFactory();
    }

    public function testInvalidOptionType()
    {
        $this->sm->expects($this->once())
                 ->method('get')
                 ->will($this->returnValue(['service_listener_options' => 'string']));

        $this->expectException(ServiceNotCreatedException::class);
        $this->expectExceptionMessage("The value of service_listener_options must be an array, string given.");

        $this->factory->__invoke($this->sm, 'ServiceListener');
    }

    public function testMissingServiceManager()
    {
        $config['service_listener_options'][0]['service_manager'] = null;
        $config['service_listener_options'][0]['config_key']      = 'test';
        $config['service_listener_options'][0]['interface']       = 'test';
        $config['service_listener_options'][0]['method']          = 'test';

        $this->sm->expects($this->once())
                 ->method('get')
                 ->will($this->returnValue($config));

        $this->expectException(ServiceNotCreatedException::class);
        $this->expectExceptionMessage(
            'Invalid service listener options detected, 0 array must contain service_manager key.',
        );

        $this->factory->__invoke($this->sm, 'ServiceListener');
    }

    public function testInvalidTypeServiceManager()
    {
        $config['service_listener_options'][0]['service_manager'] = 1;
        $config['service_listener_options'][0]['config_key']      = 'test';
        $config['service_listener_options'][0]['interface']       = 'test';
        $config['service_listener_options'][0]['method']          = 'test';

        $this->sm->expects($this->once())
                 ->method('get')
                 ->will($this->returnValue($config));

        $this->expectExceptionMessage(
            'Invalid service listener options detected, service_manager must be a string, integer given.',
        );
        $this->expectException(ServiceNotCreatedException::class);

        $this->factory->__invoke($this->sm, 'ServiceListener');
    }

    public function testMissingConfigKey()
    {
        $config['service_listener_options'][0]['service_manager'] = 'test';
        $config['service_listener_options'][0]['config_key']      = null;
        $config['service_listener_options'][0]['interface']       = 'test';
        $config['service_listener_options'][0]['method']          = 'test';

        $this->sm->expects($this->once())
                 ->method('get')
                 ->will($this->returnValue($config));

        $this->expectException(ServiceNotCreatedException::class);
        $this->expectExceptionMessage(
            'Invalid service listener options detected, 0 array must contain config_key key.',
        );

        $this->factory->__invoke($this->sm, 'ServiceListener');
    }

    public function testInvalidTypeConfigKey()
    {
        $config['service_listener_options'][0]['service_manager'] = 'test';
        $config['service_listener_options'][0]['config_key']      = 1;
        $config['service_listener_options'][0]['interface']       = 'test';
        $config['service_listener_options'][0]['method']          = 'test';

        $this->sm->expects($this->once())
                 ->method('get')
                 ->will($this->returnValue($config));

        $this->expectException(ServiceNotCreatedException::class);
        $this->expectExceptionMessage(
            'Invalid service listener options detected, config_key must be a string, integer given.',
        );

        $this->factory->__invoke($this->sm, 'ServiceListener');
    }

    public function testMissingInterface()
    {
        $config['service_listener_options'][0]['service_manager'] = 'test';
        $config['service_listener_options'][0]['config_key']      = 'test';
        $config['service_listener_options'][0]['interface']       = null;
        $config['service_listener_options'][0]['method']          = 'test';

        $this->sm->expects($this->once())
                 ->method('get')
                 ->will($this->returnValue($config));

        $this->expectExceptionMessage("Invalid service listener options detected, 0 array must contain interface key.");
        $this->expectException(ServiceNotCreatedException::class);

        $this->factory->__invoke($this->sm, 'ServiceListener');
    }

    public function testInvalidTypeInterface()
    {
        $config['service_listener_options'][0]['service_manager'] = 'test';
        $config['service_listener_options'][0]['config_key']      = 'test';
        $config['service_listener_options'][0]['interface']       = 1;
        $config['service_listener_options'][0]['method']          = 'test';

        $this->sm->expects($this->once())
                 ->method('get')
                 ->will($this->returnValue($config));

        $this->expectExceptionMessage(
            'Invalid service listener options detected, interface must be a string, integer given.',
        );
        $this->expectException(ServiceNotCreatedException::class);

        $this->factory->__invoke($this->sm, 'ServiceListener');
    }

    public function testMissingMethod()
    {
        $config['service_listener_options'][0]['service_manager'] = 'test';
        $config['service_listener_options'][0]['config_key']      = 'test';
        $config['service_listener_options'][0]['interface']       = 'test';
        $config['service_listener_options'][0]['method']          = null;

        $this->sm->expects($this->once())
                 ->method('get')
                 ->will($this->returnValue($config));

        $this->expectException(ServiceNotCreatedException::class);
        $this->expectExceptionMessage("Invalid service listener options detected, 0 array must contain method key.");

        $this->factory->__invoke($this->sm, 'ServiceListener');
    }

    public function testInvalidTypeMethod()
    {
        $config['service_listener_options'][0]['service_manager'] = 'test';
        $config['service_listener_options'][0]['config_key']      = 'test';
        $config['service_listener_options'][0]['interface']       = 'test';
        $config['service_listener_options'][0]['method']          = 1;

        $this->sm->expects($this->once())
                 ->method('get')
                 ->will($this->returnValue($config));

        $this->expectExceptionMessage(
            'Invalid service listener options detected, method must be a string, integer given.',
        );
        $this->expectException(ServiceNotCreatedException::class);

        $this->factory->__invoke($this->sm, 'ServiceListener');
    }
}
