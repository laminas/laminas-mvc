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
use PHPUnit_Framework_TestCase as TestCase;
use Zend\Hydrator\HydratorPluginManager;
use Zend\Mvc\Service\HydratorManagerFactory;

class HydratorManagerFactoryTest extends TestCase
{
    public function setUp()
    {
        $this->factory = new HydratorManagerFactory();
        $this->services = $this->prophesize(ContainerInterface::class);
    }

    public function testFactoryReturnsZendHydratorManagerInstance()
    {
        $hydrators = $this->factory->__invoke($this->services->reveal(), null);
        $this->assertInstanceOf(HydratorPluginManager::class, $hydrators);
    }
}
