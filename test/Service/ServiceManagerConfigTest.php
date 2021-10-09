<?php

namespace LaminasTest\Mvc\Service;

use Laminas\EventManager\EventManager;
use Laminas\EventManager\EventManagerAwareInterface;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\Mvc\Service\ServiceManagerConfig;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Laminas\ServiceManager\ServiceManager;
use LaminasTest\Mvc\Service\TestAsset\EventManagerAwareObject;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @covers \Laminas\Mvc\Service\ServiceManagerConfig
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
    protected function setUp(): void
    {
        $this->config   = new ServiceManagerConfig();
        $this->services = new ServiceManager();
        $this->config->configureServiceManager($this->services);
    }

    /**
     * @param null|SharedEventManagerInterface
     * @return EventManager
     */
    protected function createEventManager(SharedEventManagerInterface $sharedManager = null)
    {
        return new EventManager($sharedManager ?: $this->services->get('SharedEventManager'));
    }

    public function testEventManagerAwareInterfaceIsNotInjectedIfPresentButSharedManagerIs()
    {
        $events = $this->createEventManager();
        EventManagerAwareObject::$defaultEvents = $events;

        $this->services->setAlias('EventManagerAwareObject', EventManagerAwareObject::class);
        $this->services->setFactory(EventManagerAwareObject::class, InvokableFactory::class);

        $instance = $this->services->get('EventManagerAwareObject');
        $this->assertInstanceOf(EventManagerAwareObject::class, $instance);
        $this->assertSame($events, $instance->getEventManager());
        $this->assertSame($this->services->get('SharedEventManager'), $events->getSharedManager());
    }

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

        $sm = new ServiceManager();
        (new ServiceManagerConfig($custom))->configureServiceManager($sm);

        $this->assertTrue($sm->has('foo'));
        $this->assertTrue($sm->has('bar'));
        $this->assertTrue($sm->has('ModuleManager'));
    }

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

        $sm = new ServiceManager();
        (new ServiceManagerConfig($custom))->configureServiceManager($sm);

        $this->assertTrue($sm->has('foo'));
        $this->assertTrue($sm->has('ModuleManager'));

        $this->assertInstanceOf(stdClass::class, $sm->get('ModuleManager'));
    }

    public function testCanAddDelegators()
    {
        /*
         * Create delegator closure
         */
        $delegator = function ($container, $name, $callback, array $options = null) {
            $service = $callback();
            $service->bar = 'baz';
            return $service;
        };

        $config = [
            'aliases' => [
                'foo' => stdClass::class,
            ],
            'factories' => [
                stdClass::class => InvokableFactory::class,
            ],
            'delegators' => [
                stdClass::class => [ $delegator ],
            ],
        ];

        $sm = new ServiceManager();
        (new ServiceManagerConfig($config))->configureServiceManager($sm);

        $std = $sm->get('foo');
        $this->assertInstanceOf(stdClass::class, $std);
        $this->assertEquals('baz', $std->bar);
    }

    public function testEventManagerInitializerCanBeReplaced()
    {
        $instance       = $this->createMock(EventManagerAwareInterface::class);
        $initializer    = $this->getMockBuilder(stdClass::class)
            ->setMethods(['__invoke'])
            ->getMock();
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
        $serviceManager = new ServiceManager();
        $config->configureServiceManager($serviceManager);

        $initializer->expects($this->once())->method('__invoke')->with($serviceManager, $instance);

        $instance->expects($this->never())->method('getEventManager');
        $instance->expects($this->never())->method('setEventManager');

        $serviceManager->get('EventManagerAware');
    }

    public function testCreatesAFactoryForTheServiceManagerThatReturnsIt()
    {
        $serviceManager = new ServiceManager();
        $config         = new ServiceManagerConfig();
        $config->configureServiceManager($serviceManager);

        $this->assertTrue($serviceManager->has('ServiceManager'), 'Missing ServiceManager service!');
        $this->assertSame($serviceManager, $serviceManager->get('ServiceManager'));
    }
}
