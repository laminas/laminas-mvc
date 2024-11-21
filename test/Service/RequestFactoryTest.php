<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\Service;

// phpcs:ignore
use Interop\Container\ContainerInterface;
use Laminas\Http\Request as HttpRequest;
use Laminas\Mvc\Service\RequestFactory;
use PHPUnit\Framework\TestCase;

class RequestFactoryTest extends TestCase
{
    public function testFactoryCreatesHttpRequest()
    {
        $factory   = new RequestFactory();
        $container = $this->createMock(ContainerInterface::class);
        $request   = $factory($container, 'Request');
        $this->assertInstanceOf(HttpRequest::class, $request);
    }
}
