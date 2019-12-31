<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Service;

use Laminas\Console\Response as ConsoleResponse;
use Laminas\Http\Response as HttpResponse;
use Laminas\Mvc\Service\ResponseFactory;
use PHPUnit_Framework_TestCase as TestCase;

class ResponseFactoryTest extends TestCase
{
    use FactoryEnvironmentTrait;

    public function tearDown()
    {
        $this->setConsoleEnvironment(true);
    }

    public function testFactoryCreatesConsoleResponseInConsoleEnvironment()
    {
        $this->setConsoleEnvironment(true);
        $factory = new ResponseFactory();
        $response = $factory($this->createContainer(), 'Response');
        $this->assertInstanceOf(ConsoleResponse::class, $response);
    }

    public function testFactoryCreatesHttpResponseInNonConsoleEnvironment()
    {
        $this->setConsoleEnvironment(false);
        $factory = new ResponseFactory();
        $response = $factory($this->createContainer(), 'Response');
        $this->assertInstanceOf(HttpResponse::class, $response);
    }
}
