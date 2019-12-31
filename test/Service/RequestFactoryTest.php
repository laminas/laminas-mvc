<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Service;

use Interop\Container\ContainerInterface;
use Laminas\Http\Request as HttpRequest;
use Laminas\Mvc\Service\RequestFactory;
use PHPUnit\Framework\TestCase;

class RequestFactoryTest extends TestCase
{
    public function testFactoryCreatesHttpRequest()
    {
        $factory = new RequestFactory();
        $request = $factory($this->prophesize(ContainerInterface::class)->reveal(), 'Request');
        $this->assertInstanceOf(HttpRequest::class, $request);
    }
}
