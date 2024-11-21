<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\Service;

// phpcs:ignore
use Interop\Container\ContainerInterface;
use Laminas\Mvc\Service\ViewJsonStrategyFactory;
use Laminas\View\Renderer\JsonRenderer;
use Laminas\View\Strategy\JsonStrategy;
use PHPUnit\Framework\TestCase;

class ViewJsonStrategyFactoryTest extends TestCase
{
    private function createContainer(): ContainerInterface
    {
        $renderer  = $this->createMock(JsonRenderer::class);
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->with('ViewJsonRenderer')->willReturn($renderer);
        return $container;
    }

    public function testReturnsJsonStrategy()
    {
        $factory = new ViewJsonStrategyFactory();
        $result  = $factory($this->createContainer(), 'ViewJsonStrategy');
        $this->assertInstanceOf(JsonStrategy::class, $result);
    }
}
