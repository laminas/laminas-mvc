<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Mvc\Service;

use Interop\Container\ContainerInterface;
use PHPUnit\Framework\TestCase;
use Zend\Mvc\Service\ViewPrefixPathStackResolverFactory;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\Resolver\PrefixPathStackResolver;

class ViewPrefixPathStackResolverFactoryTest extends TestCase
{
    public function testCreateService()
    {
        $serviceLocator = $this->prophesize(ServiceLocatorInterface::class);
        $serviceLocator->willImplement(ContainerInterface::class);

        $serviceLocator->get('config')->willReturn([
            'view_manager' => [
                'prefix_template_path_stack' => [
                    'album/' => [],
                ],
            ],
        ]);

        $factory  = new ViewPrefixPathStackResolverFactory();
        $resolver = $factory($serviceLocator->reveal(), 'ViewPrefixPathStackResolver');

        $this->assertInstanceOf(PrefixPathStackResolver::class, $resolver);
    }
}
