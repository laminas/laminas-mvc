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
use stdClass;
use Zend\EventManager\EventManager;
use Zend\Mvc\Service\ServiceManagerConfig;
use Zend\ServiceManager\Factory\InvokableFactory;
use Zend\ServiceManager\ServiceManager;

/**
 * @covers \Zend\Mvc\Service\ServiceManagerConfig
 */
class ServiceManagerConfigTest extends TestCase
{
    /**
     * @var ServiceManagerConfig
     */
    private $config;

    /**
     * @var ServiceManager
     */
    private $services;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->config   = new ServiceManagerConfig();
        $this->services = $this->config->configureServiceManager(new ServiceManager());
    }

    /**
     * @group 3786
     */
    public function testEventManagerAwareInterfaceIsNotInjectedIfPresentButSharedManagerIs()
    {
        $events = new EventManager($this->services->get('SharedEventManager'));
        TestAsset\EventManagerAwareObject::$defaultEvents = $events;

        $services = $this->services->withConfig(['invokables' => [
            'EventManagerAwareObject' => TestAsset\EventManagerAwareObject::class,
        ]]);

        $instance = $services->get('EventManagerAwareObject');
        $this->assertInstanceOf(TestAsset\EventManagerAwareObject::class, $instance);
        $this->assertSame($events, $instance->getEventManager());
        $this->assertSame($services->get('SharedEventManager'), $events->getSharedManager());
    }

    /**
     * @group 6266
     */
    public function testCanMergeCustomConfigWithDefaultConfig()
    {
        $custom = [
            'invokables' => [
                'foo' => stdClass::class,
            ],
            'factories' => [
                'bar' => function () {
                    return new stdClass();
                },
            ],
        ];

        $config = new ServiceManagerConfig($custom);
        $sm = $config->configureServiceManager(new ServiceManager());

        $this->assertTrue($sm->has('foo'));
        $this->assertTrue($sm->has('bar'));
        $this->assertTrue($sm->has('ModuleManager'));
    }

    /**
     * @group 6266
     */
    public function testCanOverrideDefaultConfigWithCustomConfig()
    {
        $custom = [
            'invokables' => [
                'foo' => stdClass::class,
            ],
            'factories' => [
                'ModuleManager' => function () {
                    return new stdClass();
                },
            ],
        ];

        $config = new ServiceManagerConfig($custom);
        $sm     = $config->configureServiceManager(new ServiceManager());

        $this->assertTrue($sm->has('foo'));
        $this->assertTrue($sm->has('ModuleManager'));

        $this->assertInstanceOf(stdClass::class, $sm->get('ModuleManager'));
    }

    /**
     * @group 6266
     */
    public function testCanAddDelegators()
    {
        $config = [
            'aliases' => [
                'foo' => stdClass::class,
            ],
            'factories' => [
                stdClass::class => InvokableFactory::class,
            ],
            'delegators' => [
                stdClass::class => [
                    function ($container, $name, $callback, array $options = null) {
                        $service = $callback();
                        $service->bar = 'baz';

                        return $service;
                    },
                ],
            ],
        ];

        $sm  = new ServiceManager((new ServiceManagerConfig($config))->toArray());
        $std = $sm->get('foo');
        $this->assertInstanceOf(stdClass::class, $std);
        $this->assertEquals('baz', $std->bar);
    }

    /**
     * @group 6266
     */
    public function testEventManagerInitializerCanBeReplaced()
    {
        $instance       = $this->getMock('Zend\EventManager\EventManagerAwareInterface');
        $initializer    = $this->getMock(stdClass::class, ['__invoke']);
        $config         = new ServiceManagerConfig([
            'initializers' => [
                'EventManagerAwareInitializer' => $initializer,
            ],
            'factories' => [
                'EventManagerAware' => function () use ($instance) {
                    return $instance;
                },
            ],
        ]);
        $serviceManager = $config->configureServiceManager(new ServiceManager());

        $initializer->expects($this->once())->method('__invoke')->with($serviceManager, $instance);
        $instance->expects($this->never())->method('getEventManager');
        $instance->expects($this->never())->method('setEventManager');

        $serviceManager->get('EventManagerAware');
    }
}
