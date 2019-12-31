<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Service;

use Interop\Container\ContainerInterface;
use Laminas\Hydrator\HydratorPluginManager;
use Laminas\Mvc\Service\HydratorManagerFactory;
use PHPUnit_Framework_TestCase as TestCase;

class HydratorManagerFactoryTest extends TestCase
{
    public function setUp()
    {
        $this->factory = new HydratorManagerFactory();
        $this->services = $this->prophesize(ServiceLocatorInterface::class);
        $this->services->willImplement(ContainerInterface::class);
        $this->services->get('config')->willReturn([]);
    }

    public function testFactoryReturnsLaminasHydratorManagerInstance()
    {
        $hydrators = $this->factory->__invoke($this->services->reveal(), null);
        $this->assertInstanceOf(HydratorPluginManager::class, $hydrators);
    }
}
