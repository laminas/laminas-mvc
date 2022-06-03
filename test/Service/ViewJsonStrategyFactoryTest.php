<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\Service;

use Interop\Container\ContainerInterface;
use Laminas\Mvc\Service\ViewJsonStrategyFactory;
use Laminas\View\Renderer\JsonRenderer;
use Laminas\View\Strategy\JsonStrategy;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class ViewJsonStrategyFactoryTest extends TestCase
{
    use ProphecyTrait;

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
