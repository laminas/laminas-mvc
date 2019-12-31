<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Service;

use Laminas\Mvc\Service\ViewPrefixPathStackResolverFactory;

class ViewPrefixPathStackResolverFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateService()
    {
        /* @var $serviceLocator \Laminas\ServiceManager\ServiceLocatorInterface|\PHPUnit_Framework_MockObject_MockObject */
        $serviceLocator = $this->getMock('Laminas\ServiceManager\ServiceLocatorInterface');

        $serviceLocator->expects($this->once())
            ->method('get')
            ->with('Config')
            ->will($this->returnValue(array(
                'view_manager' => array(
                    'prefix_template_path_stack' => array(
                        'album/' => array(),
                    ),
                ),
            )));

        $factory  = new ViewPrefixPathStackResolverFactory();
        $resolver = $factory->createService($serviceLocator);

        $this->assertInstanceOf('Laminas\View\Resolver\PrefixPathStackResolver', $resolver);
    }
}
