<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Service;

use Laminas\Hydrator\HydratorPluginManager as LaminasHydratorManager;
use Laminas\Mvc\Service\HydratorManagerFactory;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\Stdlib\Hydrator\HydratorPluginManager;
use PHPUnit_Framework_TestCase as TestCase;

class HydratorManagerFactoryTest extends TestCase
{
    public function setUp()
    {
        $this->factory = new HydratorManagerFactory();
        $this->services = $this->prophesize(ServiceLocatorInterface::class);
        $this->services->get('Config')->willReturn([]);
    }

    public function testFactoryReturnsLaminasHydratorManagerInstance()
    {
        $hydrators = $this->factory->createService($this->services->reveal());
        $this->assertInstanceOf(LaminasHydratorManager::class, $hydrators);
        return $hydrators;
    }

    /**
     * @todo Remove for 3.0
     * @depends testFactoryReturnsLaminasHydratorManagerInstance
     */
    public function testFactoryReturnsStdlibHydratorManagerInstance($hydrators)
    {
        $this->assertInstanceOf(HydratorPluginManager::class, $hydrators);
    }
}
