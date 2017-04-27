<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Mvc\Service;

use Interop\Container\ContainerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use Zend\Mvc\Service\HttpMethodListenerFactory;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * @covers Zend\Mvc\Service\HttpMethodListenerFactory
 */
class HttpMethodListenerFactoryTest extends TestCase
{
    /**
     * @var ServiceLocatorInterface|MockObject
     */
    protected $serviceLocator;

    public function setUp()
    {
        $this->serviceLocator = $this->prophesize(ServiceLocatorInterface::class);
        $this->serviceLocator->willImplement(ContainerInterface::class);
    }

    public function testCreateWithDefaults()
    {
        $factory = new HttpMethodListenerFactory();
        $listener = $factory($this->serviceLocator->reveal(), 'HttpMethodListener');
        $this->assertTrue($listener->isEnabled());
        $this->assertNotEmpty($listener->getAllowedMethods());
    }

    public function testCreateWithConfig()
    {
        $config['http_methods_listener'] = [
            'enabled' => false,
            'allowed_methods' => ['FOO', 'BAR']
        ];

        $this->serviceLocator->get('config')->willReturn($config);

        $factory = new HttpMethodListenerFactory();
        $listener = $factory($this->serviceLocator->reveal(), 'HttpMethodListener');

        $listenerConfig = $config['http_methods_listener'];

        $this->assertSame($listenerConfig['enabled'], $listener->isEnabled());
        $this->assertSame($listenerConfig['allowed_methods'], $listener->getAllowedMethods());
    }
}
