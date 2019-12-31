<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Service;

use ArrayObject;
use Laminas\Mvc\Service\InjectTemplateListenerFactory;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * Tests for {@see \Laminas\Mvc\Service\InjectTemplateListenerFactory}
 *
 * @covers \Laminas\Mvc\Service\InjectTemplateListenerFactory
 */
class InjectTemplateListenerFactoryTest extends TestCase
{
    public function testFactoryCanCreateInjectTemplateListener()
    {
        $this->buildInjectTemplateListenerWithConfig(array());
    }

    public function testFactoryCanSetControllerMap()
    {
        $listener = $this->buildInjectTemplateListenerWithConfig(array(
            'view_manager' => array(
                'controller_map' => array(
                    'SomeModule' => 'some/module',
                ),
            ),
        ));

        $this->assertEquals('some/module', $listener->mapController("SomeModule"));
    }

    public function testFactoryCanSetControllerMapViaArrayAccessVM()
    {
        $listener = $this->buildInjectTemplateListenerWithConfig(array(
            'view_manager' => new ArrayObject(array(
                'controller_map' => array(
                    // must be an array due to type hinting on setControllerMap()
                    'SomeModule' => 'some/module',
                ),
            ))
        ));

        $this->assertEquals('some/module', $listener->mapController("SomeModule"));
    }

    /**
     * @param mixed $config
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\Laminas\Mvc\View\Http\InjectTemplateListener
     */
    private function buildInjectTemplateListenerWithConfig($config)
    {
        /* @var $serviceLocator \Laminas\ServiceManager\ServiceLocatorInterface|\PHPUnit_Framework_MockObject_MockObject */
        $serviceLocator = $this->getMock('Laminas\ServiceManager\ServiceLocatorInterface');

        $serviceLocator->expects($this->any())->method('get')->with('Config')->will($this->returnValue($config));

        $factory  = new InjectTemplateListenerFactory();
        $listener = $factory->createService($serviceLocator);

        $this->assertInstanceOf('Laminas\Mvc\View\Http\InjectTemplateListener', $listener);

        return $listener;
    }
}
