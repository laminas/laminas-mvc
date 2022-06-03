<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\Service;

use Interop\Container\ContainerInterface;
use Laminas\Mvc\Service\ViewManagerFactory;
use Laminas\Mvc\View\Http\ViewManager as HttpViewManager;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class ViewManagerFactoryTest extends TestCase
{
    use ProphecyTrait;

    private function createContainer()
    {
        $http      = $this->prophesize(HttpViewManager::class);
        $container = $this->prophesize(ContainerInterface::class);
        $container->get('HttpViewManager')->will(function () use ($http) {
            return $http->reveal();
        });
        return $container->reveal();
    }

    public function testReturnsHttpViewManager()
    {
        $factory = new ViewManagerFactory();
        $result  = $factory($this->createContainer(), 'ViewManager');
        $this->assertInstanceOf(HttpViewManager::class, $result);
    }
}
