<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Service;

use Laminas\Mvc\Service\HttpMethodListenerFactory;
use Laminas\ServiceManager\ServiceLocatorInterface;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * @covers Laminas\Mvc\Service\HttpMethodListenerFactory
 */
class HttpMethodListenerFactoryTest extends TestCase
{
    /**
     * @var ServiceLocatorInterface|MockObject
     */
    protected $serviceLocator;

    public function setUp()
    {
        $this->serviceLocator = $this->getMock('Laminas\ServiceManager\ServiceLocatorInterface');
    }

    public function testCreateWithDefaults()
    {
        $factory = new HttpMethodListenerFactory();
        $listener = $factory->createService($this->serviceLocator);
        $this->assertTrue($listener->isEnabled());
        $this->assertNotEmpty($listener->getAllowedMethods());
    }

    public function testCreateWithConfig()
    {
        $config['http_methods_listener'] = [
            'enabled' => false,
            'allowed_methods' => ['FOO', 'BAR']
        ];

        $this->serviceLocator->expects($this->atLeastOnce())
            ->method('get')
            ->with('config')
            ->willReturn($config);

        $factory = new HttpMethodListenerFactory();
        $listener = $factory->createService($this->serviceLocator);

        $listenerConfig = $config['http_methods_listener'];

        $this->assertSame($listenerConfig['enabled'], $listener->isEnabled());
        $this->assertSame($listenerConfig['allowed_methods'], $listener->getAllowedMethods());
    }
}
