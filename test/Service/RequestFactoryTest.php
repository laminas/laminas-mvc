<?php

declare(strict_types=1);

namespace LaminasTest\Mvc\Service;

use Interop\Container\ContainerInterface;
use Laminas\Http\Request as HttpRequest;
use Laminas\Mvc\Service\RequestFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class RequestFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testFactoryCreatesHttpRequest()
    {
        $factory = new RequestFactory();
        $request = $factory($this->prophesize(ContainerInterface::class)->reveal(), 'Request');
        $this->assertInstanceOf(HttpRequest::class, $request);
    }
}
