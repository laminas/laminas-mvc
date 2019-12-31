<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Mvc\Service;

use Laminas\Console\Request as ConsoleRequest;
use Laminas\Http\Request as HttpRequest;
use Laminas\Mvc\Service\RequestFactory;
use PHPUnit_Framework_TestCase as TestCase;

class RequestFactoryTest extends TestCase
{
    use FactoryEnvironmentTrait;

    public function tearDown()
    {
        $this->setConsoleEnvironment(true);
    }

    public function testFactoryCreatesConsoleRequestInConsoleEnvironment()
    {
        $this->setConsoleEnvironment(true);
        $factory = new RequestFactory();
        $request = $factory($this->createContainer(), 'Request');
        $this->assertInstanceOf(ConsoleRequest::class, $request);
    }

    public function testFactoryCreatesHttpRequestInNonConsoleEnvironment()
    {
        $this->setConsoleEnvironment(false);
        $factory = new RequestFactory();
        $request = $factory($this->createContainer(), 'Request');
        $this->assertInstanceOf(HttpRequest::class, $request);
    }
}
