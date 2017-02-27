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
use Zend\Mvc\Service\ViewFeedStrategyFactory;
use Zend\View\Renderer\FeedRenderer;
use Zend\View\Strategy\FeedStrategy;

class ViewFeedStrategyFactoryTest extends TestCase
{
    private function createContainer()
    {
        $renderer  = $this->prophesize(FeedRenderer::class);
        $container = $this->prophesize(ContainerInterface::class);
        $container->get('ViewFeedRenderer')->will(function () use ($renderer) {
            return $renderer->reveal();
        });
        return $container->reveal();
    }

    public function testReturnsFeedStrategy()
    {
        $factory = new ViewFeedStrategyFactory();
        $result  = $factory($this->createContainer(), 'ViewFeedStrategy');
        $this->assertInstanceOf(FeedStrategy::class, $result);
    }
}
