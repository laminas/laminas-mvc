<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Mvc\Service;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;
use Zend\EventManager\EventManager;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\Mvc\Controller\PluginManager;
use Zend\Mvc\Service\ServiceManagerConfig;
use Zend\ServiceManager\Factory\InvokableFactory;
use Zend\ServiceManager\ServiceManager;
use ZendTest\Mvc\Service\TestAsset\EventManagerAwareObject;

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

    /**
     * @group 3786
     */
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

        $sm = new ServiceManager();
        (new ServiceManagerConfig($custom))->configureServiceManager($sm);

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

        $sm = new ServiceManager();
        (new ServiceManagerConfig($custom))->configureServiceManager($sm);

        $this->assertTrue($sm->has('foo'));
        $this->assertTrue($sm->has('ModuleManager'));

        $this->assertInstanceOf(stdClass::class, $sm->get('ModuleManager'));
    }

    /**
     * @group 6266
     */
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

    /**
     * @group 6266
     */
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

    /**
     * @group 101
     */
    public function testCreatesAFactoryForTheServiceManagerThatReturnsIt()
    {
        $serviceManager = new ServiceManager();
        $config         = new ServiceManagerConfig();
        $config->configureServiceManager($serviceManager);

        $this->assertTrue($serviceManager->has('ServiceManager'), 'Missing ServiceManager service!');
        $this->assertSame($serviceManager, $serviceManager->get('ServiceManager'));
    }
}
