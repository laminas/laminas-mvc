<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\Service;

use Interop\Container\ContainerInterface;
use Laminas\Mvc\Service\HttpMethodListenerFactory;
use Laminas\ServiceManager\ServiceLocatorInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @covers Laminas\Mvc\Service\HttpMethodListenerFactory
 */
class HttpMethodListenerFactoryTest extends TestCase
{
    use ProphecyTrait;

    /** @var ServiceLocatorInterface|MockObject */
    protected $serviceLocator;

    public function setUp(): void
    {
        $this->serviceLocator = $this->prophesize(ServiceLocatorInterface::class);
        $this->serviceLocator->willImplement(ContainerInterface::class);
    }

    public function testCreateWithDefaults()
    {
        $factory  = new HttpMethodListenerFactory();
        $listener = $factory($this->serviceLocator->reveal(), 'HttpMethodListener');
        $this->assertTrue($listener->isEnabled());
        $this->assertNotEmpty($listener->getAllowedMethods());
    }

    public function testCreateWithConfig()
    {
        $config['http_methods_listener'] = [
            'enabled'         => false,
            'allowed_methods' => ['FOO', 'BAR'],
        ];

        $this->serviceLocator->get('config')->willReturn($config);

        $factory  = new HttpMethodListenerFactory();
        $listener = $factory($this->serviceLocator->reveal(), 'HttpMethodListener');

        $listenerConfig = $config['http_methods_listener'];

        $this->assertSame($listenerConfig['enabled'], $listener->isEnabled());
        $this->assertSame($listenerConfig['allowed_methods'], $listener->getAllowedMethods());
    }
}
