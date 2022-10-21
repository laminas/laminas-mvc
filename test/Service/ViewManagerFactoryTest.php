<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\Service;

use Laminas\Mvc\Service\ViewManagerFactory;
use Laminas\Mvc\View\Http\ViewManager as HttpViewManager;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;

class ViewManagerFactoryTest extends TestCase
{
    use ProphecyTrait;

    private function createContainer(): ContainerInterface
    {
        $http      = $this->prophesize(HttpViewManager::class);
        $container = $this->prophesize(ContainerInterface::class);
        $container->get('HttpViewManager')->will(static fn(): object => $http->reveal());
        return $container->reveal();
    }

    public function testReturnsHttpViewManager(): void
    {
        $factory = new ViewManagerFactory();
        $result  = $factory($this->createContainer(), 'ViewManager');
        $this->assertInstanceOf(HttpViewManager::class, $result);
    }
}
