<?php

namespace LaminasTest\Mvc\Service;

use Interop\Container\ContainerInterface;
use Laminas\Mvc\Service\ViewFeedStrategyFactory;
use Laminas\View\Renderer\FeedRenderer;
use Laminas\View\Strategy\FeedStrategy;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class ViewFeedStrategyFactoryTest extends TestCase
{
    use ProphecyTrait;

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
