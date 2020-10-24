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
    public function setUp(): void
    {
        $sm = $this->sm = $this->getMockBuilder(ServiceManager::class)
                               ->setMethods(['get'])
                               ->getMock();

        $this->factory  = new ServiceListenerFactory();
    }

    public function testInvalidOptionType()
    {
        $this->expectExceptionMessage("The value of service_listener_options must be an array, string given.");
        $this->expectException(ServiceNotCreatedException::class);
        $this->sm->expects($this->once())
                 ->method('get')
                 ->will($this->returnValue(['service_listener_options' => 'array']));

        $this->factory->__invoke($this->sm, 'ServiceListener');
    }

    public function testMissingServiceManager()
    {
        $this->expectExceptionMessage(
            "Invalid service listener options detected, 0 array must contain service_manager key."
        );
        $this->expectException(ServiceNotCreatedException::class);
        $config['service_listener_options'] = [
            0 => [
                'service_manager' => null,
                'config_key' => 'test',
                'interface' => 'test',
                'method' => 'test',
            ],
        ];

        $this->sm->expects($this->once())
                 ->method('get')
                 ->will($this->returnValue($config));

        $this->factory->__invoke($this->sm, 'ServiceListener');
    }

    public function testInvalidTypeServiceManager()
    {
        $this->expectExceptionMessage(
            "Invalid service listener options detected, service_manager must be a string, integer given."
        );
        $this->expectException(ServiceNotCreatedException::class);
        $config['service_listener_options'] = [
            0 => [
                'service_manager' => 1,
                'config_key' => 'test',
                'interface' => 'test',
                'method' => 'test',
            ],
        ];

        $this->sm->expects($this->once())
                 ->method('get')
                 ->will($this->returnValue($config));

        $this->factory->__invoke($this->sm, 'ServiceListener');
    }

    public function testMissingConfigKey()
    {
        $this->expectExceptionMessage(
            "Invalid service listener options detected, 0 array must contain config_key key."
        );
        $this->expectException(ServiceNotCreatedException::class);
        $config['service_listener_options'] = [
            0 => [
                'service_manager' => 'test',
                'config_key' => null,
                'interface' => 'test',
                'method' => 'test',
            ],
        ];

        $this->sm->expects($this->once())
                 ->method('get')
                 ->will($this->returnValue($config));

        $this->factory->__invoke($this->sm, 'ServiceListener');
    }

    public function testInvalidTypeConfigKey()
    {
        $this->expectExceptionMessage(
            "Invalid service listener options detected, config_key must be a string, integer given."
        );
        $this->expectException(ServiceNotCreatedException::class);
        $config['service_listener_options'] = [
            0 => [
                'service_manager' => 'test',
                'config_key' => 1,
                'interface' => 'test',
                'method' => 'test',
            ],
        ];

        $this->sm->expects($this->once())
                 ->method('get')
                 ->will($this->returnValue($config));

        $this->factory->__invoke($this->sm, 'ServiceListener');
    }

    public function testMissingInterface()
    {
        $this->expectExceptionMessage(
            "Invalid service listener options detected, 0 array must contain interface key."
        );
        $this->expectException(ServiceNotCreatedException::class);
        $config['service_listener_options'] = [
            0 => [
                'service_manager' => 'test',
                'config_key' => 'test',
                'interface' => null,
                'method' => 'test',
            ],
        ];

        $this->sm->expects($this->once())
                 ->method('get')
                 ->will($this->returnValue($config));

        $this->factory->__invoke($this->sm, 'ServiceListener');
    }

    public function testInvalidTypeInterface()
    {
        $this->expectExceptionMessage(
            "Invalid service listener options detected, interface must be a string, integer given."
        );
        $this->expectException(ServiceNotCreatedException::class);
        $config['service_listener_options'] = [
            0 => [
                'service_manager' => 'test',
                'config_key' => 'test',
                'interface' => 1,
                'method' => 'test',
            ],
        ];

        $this->sm->expects($this->once())
                 ->method('get')
                 ->will($this->returnValue($config));

        $this->factory->__invoke($this->sm, 'ServiceListener');
    }

    public function testMissingMethod()
    {
        $this->expectExceptionMessage(
            "Invalid service listener options detected, 0 array must contain method key."
        );
        $this->expectException(ServiceNotCreatedException::class);
        $config['service_listener_options'] = [
            0 => [
                'service_manager' => 'test',
                'config_key' => 'test',
                'interface' => 'test',
                'method' => null,
            ],
        ];

        $this->sm->expects($this->once())
                 ->method('get')
                 ->will($this->returnValue($config));

        $this->factory->__invoke($this->sm, 'ServiceListener');
    }

    public function testInvalidTypeMethod()
    {
        $this->expectExceptionMessage(
            "Invalid service listener options detected, method must be a string, integer given."
        );
        $this->expectException(ServiceNotCreatedException::class);
        $config['service_listener_options'] = [
            0 => [
                'service_manager' => 'test',
                'config_key' => 'test',
                'interface' => 'test',
                'method' => 1,
            ],
        ];

        $this->sm->expects($this->once())
                 ->method('get')
                 ->will($this->returnValue($config));

        $this->factory->__invoke($this->sm, 'ServiceListener');
    }
}
