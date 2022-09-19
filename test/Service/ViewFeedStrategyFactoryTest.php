<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\Service;

use Laminas\Mvc\Service\ViewFeedStrategyFactory;
use Laminas\View\Renderer\FeedRenderer;
use Laminas\View\Strategy\FeedStrategy;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;

class ViewFeedStrategyFactoryTest extends TestCase
{
    use ProphecyTrait;

    private function createContainer(): ContainerInterface
    {
        $renderer  = $this->prophesize(FeedRenderer::class);
        $container = $this->prophesize(ContainerInterface::class);
        $container->get('ViewFeedRenderer')->will(fn() => $renderer->reveal());
        return $container->reveal();
    }

    public function testReturnsFeedStrategy(): void
    {
        $factory = new ViewFeedStrategyFactory();
        $result  = $factory($this->createContainer(), 'ViewFeedStrategy');
        $this->assertInstanceOf(FeedStrategy::class, $result);
    }
}
