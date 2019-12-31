<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Service;

use Interop\Container\ContainerInterface;
use Laminas\Http\Response as HttpResponse;
use Laminas\Mvc\Service\ResponseFactory;
use PHPUnit_Framework_TestCase as TestCase;

class ResponseFactoryTest extends TestCase
{
    public function testFactoryCreatesHttpResponse()
    {
        $factory = new ResponseFactory();
        $response = $factory($this->prophesize(ContainerInterface::class)->reveal(), 'Response');
        $this->assertInstanceOf(HttpResponse::class, $response);
    }
}
