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
use Zend\Hydrator\HydratorPluginManager as ZendHydratorManager;
use Zend\Mvc\Service\HydratorManagerFactory;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Stdlib\Hydrator\HydratorPluginManager;

class HydratorManagerFactoryTest extends TestCase
{
    public function setUp()
    {
        $this->factory = new HydratorManagerFactory();
        $this->services = $this->prophesize(ServiceLocatorInterface::class);
        $this->services->get('Config')->willReturn([]);
    }

    public function testFactoryReturnsZendHydratorManagerInstance()
    {
        $hydrators = $this->factory->createService($this->services->reveal());
        $this->assertInstanceOf(ZendHydratorManager::class, $hydrators);
        return $hydrators;
    }

    /**
     * @todo Remove for 3.0
     * @depends testFactoryReturnsZendHydratorManagerInstance
     */
    public function testFactoryReturnsStdlibHydratorManagerInstance($hydrators)
    {
        $this->assertInstanceOf(HydratorPluginManager::class, $hydrators);
    }
}
