<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Mvc\Service;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\Console\Request as ConsoleRequest;
use Zend\Http\Request as HttpRequest;
use Zend\Mvc\Service\RequestFactory;

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
