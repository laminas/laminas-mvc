<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\Service;

// phpcs:ignore
use Interop\Container\ContainerInterface;
use Laminas\Mvc\Service\ViewFeedStrategyFactory;
use Laminas\View\Renderer\FeedRenderer;
use Laminas\View\Strategy\FeedStrategy;
use PHPUnit\Framework\TestCase;

class ViewFeedStrategyFactoryTest extends TestCase
{
    private function createContainer(): ContainerInterface
    {
        $renderer  = $this->createMock(FeedRenderer::class);
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->with('ViewFeedRenderer')->willReturn($renderer);
        return $container;
    }

    public function testReturnsFeedStrategy()
    {
        $factory = new ViewFeedStrategyFactory();
        $result  = $factory($this->createContainer(), 'ViewFeedStrategy');
        $this->assertInstanceOf(FeedStrategy::class, $result);
    }
}
