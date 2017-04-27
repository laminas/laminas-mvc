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
use PHPUnit\Framework\TestCase;
use Zend\Mvc\Service\ViewJsonStrategyFactory;
use Zend\View\Renderer\JsonRenderer;
use Zend\View\Strategy\JsonStrategy;

class ViewJsonStrategyFactoryTest extends TestCase
{
    private function createContainer()
    {
        $renderer  = $this->prophesize(JsonRenderer::class);
        $container = $this->prophesize(ContainerInterface::class);
        $container->get('ViewJsonRenderer')->will(function () use ($renderer) {
            return $renderer->reveal();
        });
        return $container->reveal();
    }

    public function testReturnsJsonStrategy()
    {
        $factory = new ViewJsonStrategyFactory();
        $result  = $factory($this->createContainer(), 'ViewJsonStrategy');
        $this->assertInstanceOf(JsonStrategy::class, $result);
    }
}
