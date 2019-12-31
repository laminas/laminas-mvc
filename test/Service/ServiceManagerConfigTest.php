<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Service;

use Laminas\EventManager\EventManager;
use Laminas\Mvc\Service\ServiceManagerConfig;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit_Framework_TestCase as TestCase;

class ServiceManagerConfigTest extends TestCase
{
    public function setUp()
    {
        $this->config = new ServiceManagerConfig();
        $this->services = new ServiceManager();
        $this->config->configureServiceManager($this->services);
    }

    /**
     * @group 3786
     */
    public function testEventManagerAwareInterfaceIsNotInjectedIfPresentButSharedManagerIs()
    {
        $events = new EventManager();
        TestAsset\EventManagerAwareObject::$defaultEvents = $events;

        $this->services->setInvokableClass('EventManagerAwareObject', __NAMESPACE__ . '\TestAsset\EventManagerAwareObject');

        $instance = $this->services->get('EventManagerAwareObject');
        $this->assertInstanceOf(__NAMESPACE__ . '\TestAsset\EventManagerAwareObject', $instance);
        $this->assertSame($events, $instance->getEventManager());
        $this->assertSame($this->services->get('SharedEventManager'), $events->getSharedManager());
    }
}
