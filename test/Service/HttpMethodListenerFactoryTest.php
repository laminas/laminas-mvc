<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\Service;

use Laminas\Mvc\Service\HttpMethodListenerFactory;
use Laminas\ServiceManager\ServiceLocatorInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers Laminas\Mvc\Service\HttpMethodListenerFactory
 */
class HttpMethodListenerFactoryTest extends TestCase
{
    public function testCreateWithDefaults()
    {
        $serviceLocator = $this->createMock(ServiceLocatorInterface::class);
        $factory        = new HttpMethodListenerFactory();
        $listener       = $factory($serviceLocator, 'HttpMethodListener');
        self::assertTrue($listener->isEnabled());
        self::assertNotEmpty($listener->getAllowedMethods());
    }

    public function testCreateWithConfig()
    {
        $config = [
            'http_methods_listener' => [
                'enabled'         => false,
                'allowed_methods' => ['FOO', 'BAR'],
            ],
        ];

        $serviceLocator = $this->createMock(ServiceLocatorInterface::class);
        $serviceLocator->method('get')->with('config')->willReturn($config);

        $factory  = new HttpMethodListenerFactory();
        $listener = $factory($serviceLocator, 'HttpMethodListener');

        $listenerConfig = $config['http_methods_listener'];

        self::assertSame($listenerConfig['enabled'], $listener->isEnabled());
        self::assertSame($listenerConfig['allowed_methods'], $listener->getAllowedMethods());
    }
}
